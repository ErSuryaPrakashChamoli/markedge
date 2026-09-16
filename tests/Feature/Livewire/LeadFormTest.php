<?php

use App\Enums\FormFieldType;
use App\Livewire\LeadForm;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use Livewire\Livewire;

it('renders the configured core and extra fields', function () {
    $form = Form::factory()->create();
    $form->fields()->create(['key' => 'team_size', 'label' => 'Team size', 'type' => FormFieldType::Select, 'options' => ['values' => ['1-10', '11-50']]]);

    Livewire::test(LeadForm::class, ['form' => $form])
        ->assertSee('Team size')
        ->assertSee('1-10')
        ->assertSee($form->submit_label);
});

it('validates required fields and shows errors', function () {
    $form = Form::factory()->create();

    Livewire::test(LeadForm::class, ['form' => $form])
        ->set('data.name', '')
        ->set('data.email', 'not-an-email')
        ->call('submit')
        ->assertHasErrors(['data.name', 'data.email'])
        ->assertSet('submitted', false);

    expect(Lead::count())->toBe(0);
});

it('stores a lead with core fields, mapped product and custom answers', function () {
    $product = Product::factory()->active()->create();
    $form = Form::factory()->create(['requires_consent' => true]);
    $form->fields()->create(['key' => 'product', 'label' => 'Product', 'type' => FormFieldType::Select, 'options' => ['source' => 'products'], 'maps_to' => 'product_id', 'is_required' => true]);
    $form->fields()->create(['key' => 'team_size', 'label' => 'Team size', 'type' => FormFieldType::Text]);

    Livewire::test(LeadForm::class, ['form' => $form, 'context' => ['service_id' => null]])
        ->set('data.name', 'Priya')
        ->set('data.email', 'priya@example.test')
        ->set('data.phone', '+91 98765 43210')
        ->set('data.product', (string) $product->id)
        ->set('data.team_size', '11-50')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee($form->success_message);

    $lead = Lead::query()->first();

    expect($lead)->not->toBeNull()
        ->and($lead->name)->toBe('Priya')
        ->and($lead->product_id)->toBe($product->id)
        ->and($lead->form_id)->toBe($form->id)
        ->and($lead->custom_fields)->toBe(['Team size' => '11-50'])
        ->and($lead->consent_given_at)->not->toBeNull()
        ->and($lead->first_source)->toBeNull();
});

it('requires consent when the form asks for it', function () {
    $form = Form::factory()->create(['requires_consent' => true]);

    Livewire::test(LeadForm::class, ['form' => $form])
        ->set('data.name', 'Someone')
        ->set('data.email', 'someone@example.test')
        ->call('submit')
        ->assertHasErrors(['consent']);
});

it('silently discards honeypot submissions', function () {
    $form = Form::factory()->create();

    Livewire::test(LeadForm::class, ['form' => $form])
        ->set('data.name', 'Bot')
        ->set('data.email', 'bot@example.test')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Lead::count())->toBe(0);
});

it('never stores anything in preview mode', function () {
    $form = Form::factory()->create();

    Livewire::test(LeadForm::class, ['form' => $form, 'preview' => true])
        ->set('data.name', 'Previewer')
        ->set('data.email', 'preview@example.test')
        ->call('submit')
        ->assertSet('submitted', false);

    expect(Lead::count())->toBe(0);
});

it('rejects option values that are not offered', function () {
    $form = Form::factory()->create();
    $form->fields()->create(['key' => 'size', 'label' => 'Size', 'type' => FormFieldType::Select, 'options' => ['values' => ['small']]]);

    Livewire::test(LeadForm::class, ['form' => $form])
        ->set('data.name', 'Someone')
        ->set('data.email', 'someone@example.test')
        ->set('data.size', 'injected')
        ->call('submit')
        ->assertHasErrors(['data.size']);
});
