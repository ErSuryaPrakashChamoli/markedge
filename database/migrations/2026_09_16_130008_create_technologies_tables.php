<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('technologyables', function (Blueprint $table) {
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->morphs('technologyable');
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['technology_id', 'technologyable_type', 'technologyable_id'], 'technologyables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technologyables');
        Schema::dropIfExists('technologies');
    }
};
