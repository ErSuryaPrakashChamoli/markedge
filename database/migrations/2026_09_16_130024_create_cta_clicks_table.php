<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cta_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('path')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->json('utm')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cta_clicks');
    }
};
