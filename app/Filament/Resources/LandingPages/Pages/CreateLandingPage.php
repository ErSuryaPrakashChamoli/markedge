<?php

namespace App\Filament\Resources\LandingPages\Pages;

use App\Filament\Resources\LandingPages\LandingPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;

    /**
     * Paid landing pages are not indexed unless an editor turns indexing on (architecture §22).
     */
    protected function afterCreate(): void
    {
        $this->record->seo()->firstOrCreate([], ['robots_index' => false, 'include_in_sitemap' => false]);
    }
}
