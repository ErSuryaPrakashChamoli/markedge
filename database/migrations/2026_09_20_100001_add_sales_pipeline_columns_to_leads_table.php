<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('priority', 16)->default('normal')->after('assigned_to');
            $table->string('team', 80)->nullable()->after('priority');
            $table->string('lost_reason', 80)->nullable()->after('team');
            $table->decimal('deal_value', 14, 2)->nullable()->after('lost_reason');
            $table->json('qualification')->nullable()->after('deal_value');
            $table->timestamp('stage_entered_at')->nullable()->after('qualification');
            $table->timestamp('next_follow_up_at')->nullable()->after('stage_entered_at');
            $table->timestamp('last_activity_at')->nullable()->after('next_follow_up_at');

            $table->index(['assigned_to', 'status'], 'leads_owner_status_index');
            $table->index('next_follow_up_at', 'leads_next_follow_up_index');
            $table->index(['status', 'stage_entered_at'], 'leads_status_stage_index');
        });

        DB::table('leads')->whereNull('stage_entered_at')->update(['stage_entered_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_owner_status_index');
            $table->dropIndex('leads_next_follow_up_index');
            $table->dropIndex('leads_status_stage_index');
            $table->dropColumn(['priority', 'team', 'lost_reason', 'deal_value', 'qualification', 'stage_entered_at', 'next_follow_up_at', 'last_activity_at']);
        });
    }
};
