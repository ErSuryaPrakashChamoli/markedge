<?php

use App\Attribution\Attribution;
use App\Attribution\Touch;
use App\Enums\FormFieldType;
use App\Events\LeadCreated;
use App\Livewire\LeadForm;
use App\Mail\NewLeadNotification;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Cta;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function journeyAttribution(): Attribution
{
    $attribution = Attribution::fresh();
    $attribution->first = new Touch('google', 'cpc', 'q4-launch', 'seo agency', null, 'www.google.com', '/services/seo', now()->subDays(3)->toIso8601String());
    $attribution->last = new Touch('linkedin', 'social', 'lms-push', null, 'post-a', 'www.linkedin.com', '/lp/lead-management', now()->subHour()->toIso8601String());
    $attribution->visits = 2;

    return $attribution;
}

function submitLeadForm(Form $form, array $context = [], ?Attribution $attribution = null, string $path = '/contact', array $data = []): Testable
{
    $attribution ??= journeyAttribution();

    return Livewire::withQueryParams([])
        ->withCookie('mk_attr', json_encode($attribution->toArray()))
        ->test(LeadForm::class, ['form' => $form, 'context' => $context, 'sourcePath' => $path])
        ->set('data.name', $data['name'] ?? 'Priya Nair')
        ->set('data.email', $data['email'] ?? 'priya@example.test')
        ->set('data.phone', $data['phone'] ?? '+91 98765 43210')
        ->set('data.message', $data['message'] ?? 'We need an LMS.');
}

it('stores a lead with form identity, conversion page, first and last touch through the central service', function () {
    Mail::fake();
    $campaign = Campaign::factory()->create(['utm_campaign' => 'lms-push']);
    $form = Form::factory()->create(['requires_consent' => true, 'consent_text' => 'You may contact me about this enquiry.', 'notify_emails' => ['sales@example.test']]);

    Livewire::withCookie('mk_attr', json_encode(journeyAttribution()->toArray()))
        ->test(LeadForm::class, ['form' => $form, 'sourcePath' => '/contact'])
        ->set('data.name', 'Priya Nair')->set('data.email', 'priya@example.test')->set('data.phone', '+91 98765 43210')->set('data.message', 'We need an LMS.')
        ->set('consent', true)
        ->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    $lead = Lead::query()->firstOrFail();

    expect($lead->form_id)->toBe($form->id)
        ->and($lead->submitted_from_url)->toBe('/contact')
        ->and($lead->first_source)->toBe('google')
        ->and($lead->first_medium)->toBe('cpc')
        ->and($lead->first_campaign)->toBe('q4-launch')
        ->and($lead->first_term)->toBe('seo agency')
        ->and($lead->first_referrer)->toBe('www.google.com')
        ->and($lead->first_landing_page)->toBe('/services/seo')
        ->and($lead->first_visited_at)->not->toBeNull()
        ->and($lead->last_source)->toBe('linkedin')
        ->and($lead->last_medium)->toBe('social')
        ->and($lead->last_campaign)->toBe('lms-push')
        ->and($lead->last_content)->toBe('post-a')
        ->and($lead->last_landing_page)->toBe('/lp/lead-management')
        ->and($lead->campaign_id)->toBe($campaign->id)
        ->and($lead->visitor_id)->not->toBeNull()
        ->and($lead->consent_given_at)->not->toBeNull()
        ->and($lead->consent_text)->toBe('You may contact me about this enquiry.')
        ->and($lead->ip)->toBeNull()
        ->and($lead->submission_token)->not->toBeNull();

    expect(ConversionEvent::query()->pluck('type')->map->value->sort()->values()->all())->toBe(['form_submitted', 'lead_created'])
        ->and(ConversionEvent::query()->where('type', 'lead_created')->first())->lead_id->toBe($lead->id)
        ->and(ConversionEvent::query()->first()->meta)->toBeNull();

    Mail::assertSent(NewLeadNotification::class, fn (NewLeadNotification $mail) => $mail->hasTo('sales@example.test') && $mail->lead->is($lead));
});

it('captures the real conversion page from the rendering request, not a hidden field', function () {
    $page = Page::factory()->published()->create(['slug' => 'request-quote', 'form_id' => Form::factory()->create()->id]);
    $form = Form::query()->findOrFail($page->form_id);

    $this->get('/request-quote')->assertOk();
    Livewire::test(LeadForm::class, ['form' => $form])->assertSet('sourcePath', null);

    submitLeadForm($form, path: '/request-quote')->call('submit')->assertHasNoErrors();
    expect(Lead::query()->firstOrFail()->submitted_from_url)->toBe('/request-quote');

    submitLeadForm(Form::factory()->create(), path: 'https://evil.example/phish')->call('submit')->assertHasNoErrors();
    expect(Lead::query()->latest('id')->firstOrFail()->submitted_from_url)->toBeNull();
});

it('locks context so hidden-field tampering cannot change the service or product', function () {
    $service = Service::factory()->published()->create();
    $other = Service::factory()->published()->create();
    $form = Form::factory()->create();

    submitLeadForm($form, ['service_id' => $service->id])->call('submit')->assertHasNoErrors();
    expect(Lead::query()->firstOrFail()->service_id)->toBe($service->id);

    expect(fn () => Livewire::test(LeadForm::class, ['form' => $form, 'context' => ['service_id' => $service->id]])->set('context.service_id', $other->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('associates a product from a validated option and records the product entity on the event', function () {
    $product = Product::factory()->active()->create();
    $draft = Product::factory()->create();
    $form = Form::factory()->create();
    $form->fields()->create(['key' => 'product', 'label' => 'Product', 'type' => FormFieldType::Select, 'options' => ['source' => 'products'], 'maps_to' => 'product_id']);

    submitLeadForm($form)->set('data.product', (string) $draft->id)->call('submit')->assertHasErrors(['data.product']);
    submitLeadForm($form)->set('data.product', (string) $product->id)->call('submit')->assertHasNoErrors();

    $lead = Lead::query()->firstOrFail();
    expect($lead->product_id)->toBe($product->id)
        ->and(ConversionEvent::query()->where('type', 'lead_created')->first())->entity_type->toBe('product')
        ->and(ConversionEvent::query()->where('type', 'lead_created')->first()->entity_id)->toBe($product->id);
});

it('credits a recent CTA click and ignores an old one', function () {
    $cta = Cta::factory()->create();
    $recent = journeyAttribution();
    $recent->recordCtaClick($cta->id);
    submitLeadForm(Form::factory()->create(), attribution: $recent)->call('submit');
    expect(Lead::query()->latest('id')->firstOrFail()->cta_id)->toBe($cta->id);

    $stale = journeyAttribution();
    $stale->lastCtaId = $cta->id;
    $stale->lastCtaAt = now()->subHours(3)->toIso8601String();
    submitLeadForm(Form::factory()->create(), attribution: $stale)->call('submit');
    expect(Lead::query()->latest('id')->firstOrFail()->cta_id)->toBeNull();
});

it('treats a retried submission with the same token as idempotent', function () {
    $form = Form::factory()->create();
    $component = submitLeadForm($form);

    $component->call('submit')->assertSet('submitted', true);
    $component->set('submitted', false)->call('submit')->assertSet('submitted', true);

    expect(Lead::count())->toBe(1)->and(ConversionEvent::count())->toBe(2);
});

it('keeps a repeat enquiry but links it to the earlier lead within the duplicate window', function () {
    Mail::fake();
    $form = Form::factory()->create(['notify_emails' => ['sales@example.test']]);

    submitLeadForm($form)->call('submit');
    submitLeadForm($form, data: ['message' => 'Second enquiry, same person'])->call('submit');
    $this->travel(25)->hours();
    submitLeadForm($form, data: ['message' => 'Much later'])->call('submit');
    submitLeadForm(Form::factory()->create())->call('submit');

    $leads = Lead::query()->orderBy('id')->get();

    expect($leads)->toHaveCount(4)
        ->and($leads[0]->duplicate_of_lead_id)->toBeNull()
        ->and($leads[1]->duplicate_of_lead_id)->toBe($leads[0]->id)
        ->and($leads[2]->duplicate_of_lead_id)->toBeNull()
        ->and($leads[3]->duplicate_of_lead_id)->toBeNull();

    // Two notifications: the first lead and the one after the window; the duplicate and the other form (no recipients) send nothing.
    Mail::assertSent(NewLeadNotification::class, 2);
});

it('rate limits bursts of submissions', function () {
    $form = Form::factory()->create();
    RateLimiter::clear('lead-form:127.0.0.1');

    foreach (range(1, 5) as $i) {
        submitLeadForm($form, data: ['email' => "person{$i}@example.test"])->call('submit')->assertHasNoErrors();
    }

    submitLeadForm($form, data: ['email' => 'person6@example.test'])->call('submit')->assertHasErrors(['data.name']);
    expect(Lead::count())->toBe(5);
    RateLimiter::clear('lead-form:127.0.0.1');
});

it('still stores the lead when the notification fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));
    $form = Form::factory()->create(['notify_emails' => ['sales@example.test']]);

    submitLeadForm($form)->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    expect(Lead::count())->toBe(1);
});

it('dispatches the LeadCreated hook after commit and sends nothing without recipients', function () {
    Event::fake([LeadCreated::class]);
    submitLeadForm(Form::factory()->create())->call('submit');
    Event::assertDispatched(LeadCreated::class, fn (LeadCreated $event) => $event->leadId === Lead::query()->firstOrFail()->id && ! $event->duplicate);

    Mail::fake();
    Event::fake([]);
    submitLeadForm(Form::factory()->create(['notify_emails' => []]))->call('submit');
    Mail::assertNothingSent();
});

it('escapes stored answers and never executes injected input', function () {
    $form = Form::factory()->create();
    submitLeadForm($form, data: ['name' => "Robert'); DROP TABLE leads;--", 'message' => '<script>alert(1)</script>'])->call('submit')->assertHasNoErrors();

    $lead = Lead::query()->firstOrFail();
    expect($lead->name)->toBe("Robert'); DROP TABLE leads;--")->and($lead->message)->toBe('<script>alert(1)</script>');

    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->actingAs(adminUser('Super Admin'));
    $content = $this->get('/admin/leads/'.$lead->id)->assertOk()->getContent();
    expect($content)->not->toContain('<script>alert(1)</script>');
});

it('rejects inactive forms and oversized input', function () {
    $inactive = Form::factory()->inactive()->create();
    submitLeadForm($inactive)->call('submit')->assertSet('submitted', false);
    expect(Lead::count())->toBe(0);

    submitLeadForm(Form::factory()->create(), data: ['message' => str_repeat('x', 6000)])->call('submit')->assertHasErrors(['data.message']);
});
