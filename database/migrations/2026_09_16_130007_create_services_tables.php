<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('overview')->nullable();
            $table->json('benefits')->nullable();
            $table->json('features')->nullable();
            $table->json('process')->nullable();
            $table->json('deliverables')->nullable();
            $table->json('blocks')->nullable();
            $table->foreignId('cta_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_category_id', 'status', 'sort_order']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('service_service', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('related_service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['service_id', 'related_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_service');
        Schema::dropIfExists('services');
    }
};
