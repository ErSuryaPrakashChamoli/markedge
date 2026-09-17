<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('trigger', 40);
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['trigger', 'is_active', 'sort_order']);
        });

        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('idempotency_key', 160)->unique();
            $table->string('status', 20);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('result')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['automation_rule_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('key_hash', 64)->unique();
            $table->string('prefix', 12);
            $table->json('abilities');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 30);
            $table->string('status', 20);
            $table->string('subject', 200);
            $table->string('recipient', 200)->nullable();
            $table->string('idempotency_key', 160)->nullable()->unique();
            $table->string('error', 500)->nullable();
            $table->string('source', 80)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['channel', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_rules');
    }
};
