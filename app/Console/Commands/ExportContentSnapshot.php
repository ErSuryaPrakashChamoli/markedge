<?php

namespace App\Console\Commands;

use Database\Seeders\ContentSnapshotSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ExportContentSnapshot extends Command
{
    protected $signature = 'markedge:export-content';

    protected $description = 'Export the current site content and uploaded files into database/seeders/data for ContentSnapshotSeeder';

    public function handle(): int
    {
        $snapshot = [];

        foreach (ContentSnapshotSeeder::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $userColumns = $this->userForeignKeyColumns($table);

            $snapshot[$table] = DB::table($table)->get()
                ->map(function (object $row) use ($userColumns): array {
                    $row = (array) $row;

                    foreach ($userColumns as $column) {
                        $row[$column] = null;
                    }

                    return $row;
                })
                ->all();

            $this->line(sprintf('  %-22s %5d rows', $table, count($snapshot[$table])));
        }

        $snapshotFile = ContentSnapshotSeeder::snapshotFile();
        File::ensureDirectoryExists(dirname($snapshotFile));
        File::put($snapshotFile, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $fileCount = $this->exportUploadedFiles();

        $this->info("Snapshot written to {$snapshotFile} with {$fileCount} uploaded files.");

        return self::SUCCESS;
    }

    /**
     * Columns pointing at users are cleared, because local user ids do not exist on other servers.
     *
     * @return list<string>
     */
    private function userForeignKeyColumns(string $table): array
    {
        return collect(Schema::getForeignKeys($table))
            ->filter(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'users')
            ->flatMap(fn (array $foreignKey): array => $foreignKey['columns'])
            ->values()
            ->all();
    }

    private function exportUploadedFiles(): int
    {
        $target = ContentSnapshotSeeder::storageDirectory();
        File::deleteDirectory($target);

        $disk = Storage::disk('public');
        $files = collect($disk->allFiles())->reject(fn (string $path): bool => basename($path) === '.gitignore');

        foreach ($files as $path) {
            File::ensureDirectoryExists(dirname("{$target}/{$path}"));
            File::put("{$target}/{$path}", $disk->get($path));
        }

        return $files->count();
    }
}
