<?php

namespace App\Models\Concerns;

use App\Models\Technology;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTechnologies
{
    public function technologies(): MorphToMany
    {
        return $this->morphToMany(Technology::class, 'technologyable')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
