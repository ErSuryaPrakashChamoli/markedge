<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * System and structural data only. Never seeds clients, testimonials, case studies or metrics.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            CtaSeeder::class,
            FormSeeder::class,
            ServiceCatalogueSeeder::class,
            ProductSeeder::class,
            SolutionSeeder::class,
            IndustrySeeder::class,
            ArticleCategorySeeder::class,
            PageSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
