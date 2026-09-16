<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Industry;
use Illuminate\Database\Seeder;

/**
 * Industry names only, left as drafts so no experience is claimed until confirmed.
 */
class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'BFSI', 'Healthcare', 'Education', 'Real Estate', 'Manufacturing', 'Retail', 'Logistics',
            'Professional Services', 'Recruitment', 'Technology', 'Startups', 'SMEs', 'Enterprises',
        ];

        foreach ($names as $index => $name) {
            $industry = Industry::query()->withTrashed()->firstOrNew(['slug' => str($name)->slug()->toString()]);
            $industry->fill(['name' => $name, 'sort_order' => $index]);
            $industry->status ??= PublishStatus::Draft;
            $industry->save();
        }
    }
}
