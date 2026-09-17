<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversion_events', function (Blueprint $table) {
            $table->uuid('session_id')->nullable()->after('visitor_id')->index();
            $table->index(['type', 'path', 'created_at'], 'conversion_events_type_path_created_index');
            $table->index(['session_id', 'type'], 'conversion_events_session_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversion_events', function (Blueprint $table) {
            $table->dropIndex('conversion_events_type_path_created_index');
            $table->dropIndex('conversion_events_session_type_index');
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
