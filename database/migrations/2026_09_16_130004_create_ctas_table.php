<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ctas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('headline')->nullable();
            $table->text('body')->nullable();
            $table->string('primary_label');
            $table->string('primary_action')->default('route');
            $table->string('primary_value')->nullable();
            $table->string('secondary_label')->nullable();
            $table->string('secondary_action')->nullable();
            $table->string('secondary_value')->nullable();
            $table->text('whatsapp_message')->nullable();
            $table->string('variant')->default('band');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ctas');
    }
};
