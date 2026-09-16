<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['pages', 'service_categories', 'services', 'solutions', 'industries', 'case_studies', 'articles', 'landing_pages'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('owner_id')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
                $blueprint->foreignId('reviewer_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
                $blueprint->timestamp('submitted_at')->nullable()->after('reviewer_id');
                $blueprint->timestamp('approved_at')->nullable()->after('submitted_at');
                $blueprint->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
                $blueprint->timestamp('unpublish_at')->nullable()->after('published_at')->index();
                $blueprint->timestamp('expiry_reminded_at')->nullable()->after('unpublish_at');
            });
        }

        Schema::table('campaigns', function (Blueprint $blueprint): void {
            $blueprint->boolean('personalize_cta')->default(false)->after('cta_id');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $blueprint) => $blueprint->dropColumn('personalize_cta'));

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('owner_id');
                $blueprint->dropConstrainedForeignId('reviewer_id');
                $blueprint->dropConstrainedForeignId('approved_by');
                $blueprint->dropIndex(['unpublish_at']);
                $blueprint->dropColumn(['submitted_at', 'approved_at', 'unpublish_at', 'expiry_reminded_at']);
            });
        }
    }
};
