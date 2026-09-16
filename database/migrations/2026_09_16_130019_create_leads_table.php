<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('requirement')->nullable();
            $table->text('message')->nullable();

            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('solution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cta_id')->nullable()->constrained()->nullOnDelete();
            $table->string('submitted_from_url')->nullable();

            $table->string('status')->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('custom_fields')->nullable();

            $table->string('first_source')->nullable()->index();
            $table->string('first_medium')->nullable();
            $table->string('first_campaign')->nullable();
            $table->string('first_term')->nullable();
            $table->string('first_content')->nullable();
            $table->string('first_referrer')->nullable();
            $table->string('first_landing_page')->nullable();
            $table->timestamp('first_visited_at')->nullable();

            $table->string('last_source')->nullable()->index();
            $table->string('last_medium')->nullable();
            $table->string('last_campaign')->nullable();
            $table->string('last_term')->nullable();
            $table->string('last_content')->nullable();
            $table->string('last_referrer')->nullable();
            $table->string('last_landing_page')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->uuid('visitor_id')->nullable()->index();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('locale', 12)->nullable();
            $table->timestamp('consent_given_at')->nullable();

            $table->foreignId('duplicate_of_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->unsignedTinyInteger('spam_score')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
