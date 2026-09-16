<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * The shared SEO panel (architecture §15). No field has a blocking length limit; counters are advisory.
 */
class SeoFields
{
    /** @var array<string, string> */
    public const array SCHEMA_TYPES = [
        'WebPage' => 'WebPage',
        'CollectionPage' => 'CollectionPage',
        'Article' => 'Article',
        'BlogPosting' => 'BlogPosting',
        'TechArticle' => 'TechArticle',
        'Service' => 'Service',
        'Product' => 'Product',
        'SoftwareApplication' => 'SoftwareApplication',
        'FAQPage' => 'FAQPage',
    ];

    public static function make(): Section
    {
        return Section::make('SEO')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->description('Every field is optional. Empty fields fall back to the page content and then to the global defaults.')
            ->collapsible()
            ->collapsed()
            ->schema([
                Group::make([
                    TextInput::make('title')
                        ->label('SEO title')
                        ->live(onBlur: true)
                        ->hint(fn (?string $state): string => static::counter($state, 50, 60))
                        ->helperText('Advisory only: roughly 50–60 characters displays fully in most results. Longer titles are allowed; search engines decide how to truncate.'),
                    Textarea::make('description')
                        ->label('Meta description')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->hint(fn (?string $state): string => static::counter($state, 150, 160))
                        ->helperText('Advisory only: roughly 150–160 characters. Longer descriptions are allowed.'),
                    Grid::make(3)->schema([
                        TextInput::make('canonical_url')->label('Canonical URL')->url()->helperText('Only set when another URL is the original.'),
                        Toggle::make('robots_index')->label('Allow indexing')->default(true)->inline(false),
                        Toggle::make('robots_follow')->label('Follow links')->default(true)->inline(false),
                    ]),
                    Section::make('Social sharing')->collapsible()->collapsed()->schema([
                        TextInput::make('og_title')->label('Open Graph title'),
                        Textarea::make('og_description')->label('Open Graph description')->rows(2),
                        SpatieMediaLibraryFileUpload::make('og_image')->label('Open Graph image (1200×630)')->collection('og_image')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096),
                        TextInput::make('twitter_title')->label('X / Twitter title'),
                        Textarea::make('twitter_description')->label('X / Twitter description')->rows(2),
                        SpatieMediaLibraryFileUpload::make('twitter_image')->label('X / Twitter image')->collection('twitter_image')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096),
                    ])->columns(2),
                    Section::make('Schema and sitemap')->collapsible()->collapsed()->schema([
                        Select::make('schema_type')->label('Schema type override')->options(self::SCHEMA_TYPES)->native(false)->helperText('Schema is generated from real content; only override the primary type when needed.'),
                        Textarea::make('schema_overrides')
                            ->label('Schema overrides (JSON object)')
                            ->rows(4)
                            ->helperText('Merged into the generated schema. @context, @type and @id cannot be overridden.')
                            ->rule('nullable')
                            ->rules([static fn (): \Closure => static::jsonObjectRule()])
                            ->formatStateUsing(fn ($state): ?string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $state)
                            ->dehydrateStateUsing(fn ($state): ?array => static::decodeOverrides($state)),
                        Toggle::make('include_in_sitemap')->label('Include in sitemap')->default(true)->inline(false),
                        Select::make('sitemap_changefreq')->label('Sitemap change frequency')->options(array_combine(
                            ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'],
                            ['Always', 'Hourly', 'Daily', 'Weekly', 'Monthly', 'Yearly', 'Never'],
                        ))->native(false),
                        Select::make('sitemap_priority')->label('Sitemap priority')->options(array_combine(
                            ['0.1', '0.3', '0.5', '0.7', '0.9', '1.0'],
                            ['0.1', '0.3', '0.5', '0.7', '0.9', '1.0'],
                        ))->native(false)->dehydrateStateUsing(fn ($state): ?float => filled($state) ? (float) $state : null),
                    ])->columns(2),
                ])->relationship('seo'),
            ]);
    }

    public static function counter(?string $state, int $min, int $max): string
    {
        $length = mb_strlen((string) $state);

        if ($length === 0) {
            return "0 characters · suggested {$min}–{$max}";
        }

        $note = $length > $max ? ' · longer than suggested (still allowed)' : '';

        return "{$length} characters · suggested {$min}–{$max}{$note}";
    }

    public static function jsonObjectRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (blank($value)) {
                return;
            }

            $decoded = is_array($value) ? $value : json_decode((string) $value, true);

            if (! is_array($decoded) || array_is_list($decoded)) {
                $fail('Schema overrides must be a JSON object.');

                return;
            }

            foreach (['@context', '@type', '@id'] as $protected) {
                if (array_key_exists($protected, $decoded)) {
                    $fail("Schema overrides may not set {$protected}.");
                }
            }
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decodeOverrides(mixed $state): ?array
    {
        if (blank($state)) {
            return null;
        }

        if (is_array($state)) {
            return $state;
        }

        $decoded = json_decode((string) $state, true);

        return is_array($decoded) ? $decoded : null;
    }
}
