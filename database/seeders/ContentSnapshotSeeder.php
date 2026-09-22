<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Replaces the site content with the snapshot exported by `markedge:export-content`,
 * so a fresh server shows exactly what the local site shows. Also seeds roles and the admin.
 *
 * Run explicitly: php artisan db:seed --class=ContentSnapshotSeeder --force
 */
class ContentSnapshotSeeder extends Seeder
{
    /**
     * Content tables in insert order. Operational data (leads, analytics, logs, users) is never included.
     *
     * @var list<string>
     */
    public const array TABLES = [
        'settings', 'social_links', 'ctas', 'forms', 'form_fields',
        'service_categories', 'services', 'service_service', 'solutions', 'service_solution',
        'products', 'product_modules', 'product_features', 'product_capabilities', 'product_documents',
        'product_service', 'product_solution',
        'industries', 'industry_service', 'industry_solution', 'industry_product',
        'technologies', 'technologyables',
        'article_categories', 'authors', 'tags', 'articles', 'article_tag', 'article_links',
        'clients', 'testimonials', 'case_studies', 'case_study_product', 'case_study_service',
        'pages', 'landing_pages', 'menus', 'menu_items', 'faqs', 'seo_meta', 'redirects',
        'announcements', 'media', 'search_entries',
    ];

    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, AdminUserSeeder::class]);

        $snapshotFile = self::snapshotFile();

        if (! File::exists($snapshotFile)) {
            $this->command?->warn('No content snapshot found. Run `php artisan markedge:export-content` locally first.');

            return;
        }

        /** @var array<string, list<array<string, mixed>>> $snapshot */
        $snapshot = json_decode(File::get($snapshotFile), true, flags: JSON_THROW_ON_ERROR);

        Schema::withoutForeignKeyConstraints(function () use ($snapshot): void {
            $tables = array_values(array_filter(self::TABLES, fn (string $table): bool => Schema::hasTable($table)));

            foreach (array_reverse($tables) as $table) {
                DB::table($table)->delete();
            }

            foreach ($tables as $table) {
                foreach (array_chunk($snapshot[$table] ?? [], 200) as $rows) {
                    DB::table($table)->insert($rows);
                }
            }
        });

        $this->restoreUploadedFiles();

        $this->command?->info('Content snapshot imported.');
    }

    public static function snapshotFile(): string
    {
        return config('markedge.content_snapshot_path', database_path('seeders/data')).'/content-snapshot.json';
    }

    public static function storageDirectory(): string
    {
        return config('markedge.content_snapshot_path', database_path('seeders/data')).'/storage';
    }

    /**
     * Copies the exported public-disk files (block images, media) back onto the public disk.
     */
    private function restoreUploadedFiles(): void
    {
        $source = self::storageDirectory();

        if (! File::isDirectory($source)) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (File::allFiles($source) as $file) {
            $disk->put($file->getRelativePathname(), $file->getContents());
        }
    }
}
