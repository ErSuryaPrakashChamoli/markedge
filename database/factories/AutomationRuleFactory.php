<?php

namespace Database\Factories;

use App\Enums\AutomationTrigger;
use App\Models\AutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Rule '.fake()->word(),
            'trigger' => AutomationTrigger::LeadCreated,
            'conditions' => [],
            'actions' => [['type' => 'set_priority', 'priority' => 'high']],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
