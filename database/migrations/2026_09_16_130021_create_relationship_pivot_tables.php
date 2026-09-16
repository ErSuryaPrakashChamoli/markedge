<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private array $pivots = [
        'industry_product' => ['industry_id', 'product_id'],
        'product_service' => ['product_id', 'service_id'],
        'service_solution' => ['service_id', 'solution_id'],
        'product_solution' => ['product_id', 'solution_id'],
        'industry_solution' => ['industry_id', 'solution_id'],
        'industry_service' => ['industry_id', 'service_id'],
        'case_study_service' => ['case_study_id', 'service_id'],
        'case_study_product' => ['case_study_id', 'product_id'],
    ];

    public function up(): void
    {
        foreach ($this->pivots as $table => [$left, $right]) {
            Schema::create($table, function (Blueprint $blueprint) use ($left, $right) {
                $blueprint->foreignId($left)->constrained()->cascadeOnDelete();
                $blueprint->foreignId($right)->constrained()->cascadeOnDelete();
                $blueprint->unsignedInteger('sort_order')->default(0);

                $blueprint->primary([$left, $right]);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys($this->pivots)) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
