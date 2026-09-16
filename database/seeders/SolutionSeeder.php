<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Solution;
use Illuminate\Database\Seeder;

class SolutionSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Business Automation', 'Sales Management', 'Recruitment Automation', 'Digital Growth',
            'IT Infrastructure Modernisation', 'Custom Software', 'Product Development', 'Customer Experience',
        ];

        foreach ($names as $index => $name) {
            $solution = Solution::query()->withTrashed()->firstOrNew(['slug' => str($name)->slug()->toString()]);
            $solution->fill(['name' => $name, 'sort_order' => $index]);
            $solution->status ??= PublishStatus::Draft;
            $solution->save();
        }
    }
}
