<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_features', function (Blueprint $table) {
            $table->foreignId('product_module_id')->nullable()->after('product_id')->constrained('product_modules')->nullOnDelete();
        });

        Schema::create('product_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_feature_id')->constrained('product_features')->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_feature_id', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('deployment')->nullable()->after('integrations');
            $table->json('security')->nullable()->after('deployment');
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('section', 80)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'slug']);
            $table->index(['product_id', 'status', 'sort_order'], 'product_documents_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['deployment', 'security']));
        Schema::dropIfExists('product_capabilities');
        Schema::table('product_features', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_module_id'));
    }
};
