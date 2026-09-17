<?php

use App\Bi\BusinessReport;
use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Filament\Pages\BusinessIntelligence;
use App\Filament\Pages\Operations;
use App\Filament\Resources\AutomationRules\Pages\CreateAutomationRule;
use App\Models\AutomationRule;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('builds the business funnel and aligned trend from marketing and sales data', function () {
    $product = Product::factory()->active()->create(['name' => 'LMS']);
    $session = (string) Str::uuid();
    ConversionEvent::factory()->count(2)->create(['type' => ConversionEventType::PageViewed, 'path' => '/products/lms', 'entity_type' => 'product', 'entity_id' => $product->id, 'visitor_id' => (string) Str::uuid(), 'session_id' => $session, 'created_at' => now()->subDay()]);
    Lead::factory()->create(['product_id' => $product->id, 'status' => LeadStatus::Qualified, 'created_at' => now()->subDay()]);
    Lead::factory()->converted()->create(['product_id' => $product->id, 'created_at' => now()->subDay(), 'closed_at' => now()->subDay()]);

    $report = new BusinessReport('last_7');
    $funnel = $report->businessFunnel();

    expect($funnel['sessions']['count'])->toBe(1)
        ->and($funnel['leads']['count'])->toBe(2)
        ->and($funnel['qualified'])->toBe(['count' => 2, 'rate' => 100.0])
        ->and($funnel['won'])->toBe(['count' => 1, 'rate' => 50.0])
        ->and($report->trend('daily')->sum('won'))->toBe(1)
        ->and($report->trend('monthly')->sum('leads'))->toBe(2)
        ->and($report->productPerformance()->first())->toBe(['label' => 'LMS', 'views' => 2, 'leads' => 2, 'won' => 1]);

    Lead::query()->forceDelete();
    expect((new BusinessReport('last_7'))->businessFunnel()['won']['rate'])->toBeNull();
});

it('shows business intelligence sections by role', function () {
    $this->actingAs(adminUser('Sales'));
    expect(BusinessIntelligence::sections())->toBe(['executive' => false, 'marketing' => false, 'sales' => true, 'product' => false]);
    Livewire::test(BusinessIntelligence::class)->assertSee('Sales performance')->assertDontSee('Business funnel')->assertDontSee('Product performance');

    $this->actingAs(adminUser('Marketing Manager'));
    expect(BusinessIntelligence::sections()['marketing'])->toBeTrue();
    Livewire::test(BusinessIntelligence::class)->assertSee('Business funnel')->assertSee('Source performance');

    $this->actingAs(adminUser('Product Manager'));
    Livewire::test(BusinessIntelligence::class)->assertSee('Product performance')->assertDontSee('Business funnel');

    $this->actingAs(adminUser());
    expect(BusinessIntelligence::sections()['executive'])->toBeTrue();

    $this->actingAs(adminUser('Editor'));
    expect(BusinessIntelligence::canAccess())->toBeFalse();
});

it('shows operations facts to super admins only and lets managers configure rules', function () {
    $this->actingAs(adminUser('Sales Manager'));
    expect(Operations::canAccess())->toBeFalse();

    Livewire::test(CreateAutomationRule::class)
        ->fillForm(['name' => 'Flag google leads', 'trigger' => 'lead_created', 'is_active' => true, 'sort_order' => 0,
            'conditions' => [['field' => 'last_source', 'operator' => 'in', 'value' => 'google, bing']],
            'actions' => [['type' => 'set_priority', 'priority' => 'high']]])
        ->call('create')->assertHasNoFormErrors();

    $rule = AutomationRule::query()->sole();
    expect($rule->conditions[0]['value'])->toBe(['google', 'bing'])->and($rule->actions)->toBe([['type' => 'set_priority', 'priority' => 'high']]);

    $this->actingAs(adminUser());
    Livewire::test(Operations::class)->assertSee('Dependencies')->assertSee('NOT CONFIGURED')->assertSee('Rules (active / total)')->assertSee('1 / 1');
});
