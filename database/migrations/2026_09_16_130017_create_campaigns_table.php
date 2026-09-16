<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->unique();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('channel')->default('other');
            $table->string('status')->default('planned');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Constrained in the landing_pages migration (landing pages reference campaigns too).
            $table->unsignedBigInteger('landing_page_id')->nullable();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cta_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('tracking')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
