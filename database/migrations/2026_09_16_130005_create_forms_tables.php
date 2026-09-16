<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('type');
            $table->string('heading')->nullable();
            $table->text('intro')->nullable();
            $table->string('submit_label')->default('Submit');
            $table->string('success_mode')->default('message');
            $table->text('success_message')->nullable();
            // Constrained to pages in the pages migration (pages reference forms too).
            $table->unsignedBigInteger('success_page_id')->nullable();
            $table->json('notify_emails')->nullable();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_subject')->nullable();
            $table->text('auto_reply_body')->nullable();
            $table->json('core_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('honeypot_enabled')->default(true);
            $table->boolean('requires_consent')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type')->default('text');
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation')->nullable();
            $table->string('width')->default('full');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('maps_to')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
