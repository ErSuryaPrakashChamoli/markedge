<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('submission_token', 64)->nullable()->unique()->after('id');
            $table->string('consent_text', 500)->nullable()->after('consent_given_at');
            $table->index('first_medium');
            $table->index('first_campaign');
            $table->index('last_medium');
            $table->index('last_campaign');
            $table->index('submitted_from_url');
        });

        Schema::table('cta_clicks', function (Blueprint $table) {
            $table->uuid('visitor_id')->nullable()->index()->after('path');
            $table->string('target')->nullable()->after('visitor_id');
            $table->string('source')->nullable()->after('target');
            $table->string('medium')->nullable()->after('source');
            $table->string('campaign')->nullable()->after('medium');
            $table->index('created_at');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->string('consent_text', 500)->nullable()->after('requires_consent');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('consent_text');
        });

        Schema::table('cta_clicks', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['visitor_id', 'target', 'source', 'medium', 'campaign']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['first_medium']);
            $table->dropIndex(['first_campaign']);
            $table->dropIndex(['last_medium']);
            $table->dropIndex(['last_campaign']);
            $table->dropIndex(['submitted_from_url']);
            $table->dropUnique(['submission_token']);
            $table->dropColumn(['submission_token', 'consent_text']);
        });
    }
};
