<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('platform');
            $table->string('url', 500);
            $table->text('caption')->nullable();
            $table->boolean('is_vertical')->default(false);
            $table->date('posted_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_visible', 'sort_order']);
            $table->index(['platform', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_videos');
    }
};
