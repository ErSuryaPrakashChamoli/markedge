<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_entries', function (Blueprint $table) {
            $table->string('category_slug')->nullable()->index()->after('kind');
            $table->string('category_label')->nullable()->after('category_slug');
            $table->text('keywords')->nullable()->after('body_text');
            $table->unsignedTinyInteger('weight')->default(0)->after('keywords');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('search_entries', function (Blueprint $table) {
                $table->dropFullText(['title', 'summary', 'body_text']);
                $table->fullText(['title', 'summary', 'body_text', 'keywords']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('search_entries', function (Blueprint $table) {
                $table->dropFullText(['title', 'summary', 'body_text', 'keywords']);
                $table->fullText(['title', 'summary', 'body_text']);
            });
        }

        Schema::table('search_entries', function (Blueprint $table) {
            $table->dropColumn(['category_slug', 'category_label', 'keywords', 'weight']);
        });
    }
};
