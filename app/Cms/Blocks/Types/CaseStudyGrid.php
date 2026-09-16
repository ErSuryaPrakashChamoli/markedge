<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\CaseStudy;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class CaseStudyGrid extends Block
{
    public function key(): string
    {
        return 'case_study_grid';
    }

    public function label(): string
    {
        return 'Case studies';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedClipboardDocumentList;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['latest' => 'Latest published', 'featured' => 'Featured', 'ids' => 'Selected case studies'], 'latest'),
            Fields::records('case_study_ids', 'Case studies', CaseStudy::class, 'title')->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(3, 12),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:latest,featured,ids'],
            'case_study_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'case_study_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
