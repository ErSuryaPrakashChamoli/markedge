<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revisionable_type');
            $table->unsignedBigInteger('revisionable_id');
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('checksum', 64);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['revisionable_type', 'revisionable_id', 'version'], 'content_revisions_subject_version_unique');
            $table->index(['revisionable_type', 'revisionable_id', 'created_at'], 'content_revisions_subject_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
