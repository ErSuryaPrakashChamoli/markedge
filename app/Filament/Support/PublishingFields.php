<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Status, publish date, featured flag and ordering. Users without the publish permission
 * only see Draft and Review, so the workflow is enforced by the form as well as the policy.
 */
class PublishingFields
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function make(string $model, bool $featured = true, bool $sortOrder = true): Section
    {
        $fields = [
            Select::make('status')
                ->options(fn (?Model $record): array => static::statusOptions($record ?? $model))
                ->default(PublishStatus::Draft)
                ->required()
                ->native(false)
                ->helperText('Draft → Review → Scheduled or Published. Use the header actions to publish with validation.'),
            DateTimePicker::make('published_at')
                ->label('Publish date')
                ->native(false)
                ->seconds(false)
                ->requiredIf('status', PublishStatus::Scheduled->value)
                ->validationMessages(['required_if' => 'Scheduled content needs a publish date.'])
                ->helperText('Leave empty to publish immediately. A future date with status Scheduled publishes automatically.'),
            DateTimePicker::make('unpublish_at')
                ->label('Unpublish date')
                ->native(false)
                ->seconds(false)
                ->after('published_at')
                ->validationMessages(['after' => 'The unpublish date must be after the publish date.'])
                ->helperText('Optional. The content returns to Draft automatically at this time (it is not archived).'),
        ];

        if ($featured) {
            $fields[] = Toggle::make('is_featured')->label('Featured')->inline(false);
        }

        if ($sortOrder) {
            $fields[] = TextInput::make('sort_order')->numeric()->default(0)->minValue(0);
        }

        return Section::make('Publishing')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->schema($fields)
            ->columns(2);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(Model|string $subject): array
    {
        $canPublish = Gate::allows('publish', $subject);

        return collect(PublishStatus::cases())
            ->filter(fn (PublishStatus $status): bool => $canPublish || in_array($status, [PublishStatus::Draft, PublishStatus::Review], true))
            ->mapWithKeys(fn (PublishStatus $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
