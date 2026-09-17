<?php

use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Models\ApiKey;
use App\Models\ConversionEvent;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductModule;
use Illuminate\Support\Facades\DB;

function apiKey(array $abilities = ApiKey::ABILITIES): array
{
    return ApiKey::issue('Test integration', $abilities);
}

it('serves health without a key and rejects missing, revoked, expired or under-privileged keys', function () {
    $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('version', 'v1');
    $this->getJson('/api/v1/products')->assertUnauthorized();
    $this->withToken('mk_not-a-real-key-at-all-xxxxxxxxxxxxxxxx')->getJson('/api/v1/products')->assertUnauthorized();

    $issued = apiKey(['products:read']);
    $this->withToken($issued['plain'])->getJson('/api/v1/products')->assertOk();
    $this->withToken($issued['plain'])->getJson('/api/v1/leads')->assertForbidden();

    $issued['key']->update(['is_active' => false]);
    $this->withToken($issued['plain'])->getJson('/api/v1/products')->assertUnauthorized();

    $expired = ApiKey::issue('Expired', ['products:read'], null, now()->subMinute());
    $this->withToken($expired['plain'])->getJson('/api/v1/products')->assertUnauthorized();

    expect(ApiKey::query()->where('name', 'Test integration')->value('key_hash'))->not->toBe($issued['plain'])
        ->and(strlen(ApiKey::query()->where('name', 'Test integration')->value('key_hash')))->toBe(64);
});

it('lists visible products with their hierarchy and never drafts', function () {
    $product = Product::factory()->active()->create(['name' => 'LMS', 'slug' => 'lms']);
    ProductModule::factory()->create(['product_id' => $product->id, 'name' => 'Capture']);
    Product::factory()->create(['name' => 'Secret', 'slug' => 'secret']);
    $issued = apiKey(['products:read']);

    $this->withToken($issued['plain'])->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.modules.0.name', 'Capture');
    $this->withToken($issued['plain'])->getJson('/api/v1/products/secret')->assertNotFound();
});

it('creates leads through the canonical pipeline with attribution, idempotency and auditing', function () {
    $form = Form::factory()->create(['key' => 'api-contact', 'requires_consent' => true]);
    $product = Product::factory()->active()->create();
    $issued = apiKey(['leads:write', 'leads:read']);
    $payload = ['form_key' => 'api-contact', 'name' => 'Asha Verma', 'email' => 'asha@example.test', 'product_id' => $product->id, 'utm_source' => 'Partner-Site', 'utm_medium' => 'referral', 'utm_campaign' => 'Q4', 'source_url' => '/partners/landing', 'consent_given' => true, 'custom' => ['Budget' => 'Unknown']];

    $this->withToken($issued['plain'])->postJson('/api/v1/leads', ['form_key' => 'api-contact', 'name' => 'No consent', 'email' => 'x@example.test'])->assertStatus(422)->assertJsonValidationErrors(['consent_given']);

    $first = $this->withToken($issued['plain'])->withHeader('Idempotency-Key', 'order-123')->postJson('/api/v1/leads', $payload)->assertCreated();
    $replay = $this->withToken($issued['plain'])->withHeader('Idempotency-Key', 'order-123')->postJson('/api/v1/leads', $payload)->assertOk();

    $lead = Lead::query()->sole();

    expect($replay->json('data.id'))->toBe($first->json('data.id'))
        ->and($lead->form_id)->toBe($form->id)
        ->and($lead->product_id)->toBe($product->id)
        ->and($lead->first_source)->toBe('partner-site')->and($lead->last_campaign)->toBe('q4')
        ->and($lead->submitted_from_url)->toBe('/partners/landing')
        ->and($lead->consent_given_at)->not->toBeNull()
        ->and($lead->custom_fields)->toBe(['Budget' => 'Unknown'])
        ->and(ConversionEvent::query()->where('lead_id', $lead->id)->where('type', ConversionEventType::LeadCreated->value)->count())->toBe(1)
        ->and(DB::table('activity_log')->where('log_name', 'api')->where('description', 'API leads.store')->count())->toBe(1)
        ->and(DB::table('activity_log')->where('log_name', 'api')->where('description', 'API leads.store.replayed')->count())->toBe(1);

    $this->withToken($issued['plain'])->getJson('/api/v1/leads?status=new')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attribution.last.source', 'partner-site');
    $this->withToken($issued['plain'])->getJson("/api/v1/leads/{$lead->id}")->assertOk()->assertJsonPath('data.form', 'api-contact');
    Lead::factory()->spam()->create();
    $this->withToken($issued['plain'])->getJson('/api/v1/leads')->assertOk()->assertJsonCount(1, 'data');
});

it('returns aggregate analytics only', function () {
    Lead::factory()->create(['status' => LeadStatus::Qualified]);
    $issued = apiKey(['analytics:read']);

    $this->withToken($issued['plain'])->getJson('/api/v1/analytics/summary?range=last_7')->assertOk()
        ->assertJsonPath('range', 'last_7')->assertJsonPath('funnel.leads.count', 1)->assertJsonPath('sales.pipeline.qualified.count', 1)
        ->assertJsonMissingPath('data.0.email');
});
