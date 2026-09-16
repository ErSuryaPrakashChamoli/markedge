<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Video extends Block
{
    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'Video';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPresentationChartLine;
    }

    public function fields(): array
    {
        return [
            Select::make('provider')->options(['youtube' => 'YouTube', 'vimeo' => 'Vimeo'])->default('youtube')->required()->native(false),
            TextInput::make('source')->label('Video URL')->url()->required()->maxLength(500),
            TextInput::make('caption')->maxLength(255),
            Fields::image('poster', 'Poster image'),
            Fields::heading(),
        ];
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:youtube,vimeo'],
            'source' => ['required', 'url', 'max:500', 'regex:#^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/#'],
            'caption' => ['nullable', 'string', 'max:255'],
            'poster' => Fields::imageRule(),
            'heading' => ['nullable', 'string', 'max:255'],
        ];
    }
}
