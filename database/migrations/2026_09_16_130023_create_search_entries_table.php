<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('searchable');
            $table->string('kind')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('url');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['searchable_type', 'searchable_id']);

            // Full-text search is MySQL-only; the SQLite test connection falls back to LIKE.
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'summary', 'body_text']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_entries');
    }
};
