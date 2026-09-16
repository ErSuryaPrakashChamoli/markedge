<?php

use App\Models\Campaign;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;

it('keeps the lead when its campaign is deleted', function () {
    $campaign = Campaign::factory()->create();
    $lead = Lead::factory()->for($campaign)->create();

    $campaign->forceDelete();

    expect($lead->fresh())->not->toBeNull()
        ->and($lead->fresh()->campaign_id)->toBeNull();
});

it('keeps the lead when its form is deleted', function () {
    $form = Form::factory()->create();
    $lead = Lead::factory()->for($form)->create();

    $form->forceDelete();

    expect($lead->fresh()->form_id)->toBeNull();
});

it('excludes spam and closed leads from the open scope', function () {
    $open = Lead::factory()->create();
    Lead::factory()->spam()->create();
    Lead::factory()->converted()->create();

    expect(Lead::open()->pluck('id')->all())->toBe([$open->id]);
});

it('stores the product interest and attribution snapshot', function () {
    $product = Product::factory()->create();

    $lead = Lead::factory()->for($product)->create([
        'first_source' => 'google',
        'last_source' => 'linkedin',
        'last_campaign' => 'lms-launch',
        'custom_fields' => ['team_size' => '11-50'],
    ]);

    expect($lead->fresh())
        ->product->id->toBe($product->id)
        ->first_source->toBe('google')
        ->last_campaign->toBe('lms-launch')
        ->custom_fields->toBe(['team_size' => '11-50']);
});
