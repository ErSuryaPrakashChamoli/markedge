<?php

namespace App\Filament\Pages;

use App\Models\Cta;
use App\Models\Setting;
use App\Services\Cms\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Global settings editor. Values are public configuration; secrets never live here (architecture §26).
 */
class GlobalSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Global Settings';

    protected string $view = 'filament.pages.global-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, array{group: string, type: string}> */
    public const array FIELDS = [
        'company.name' => ['group' => 'company', 'type' => 'text'],
        'company.tagline' => ['group' => 'company', 'type' => 'text'],
        'company.legal_name' => ['group' => 'company', 'type' => 'text'],
        'company.description' => ['group' => 'company', 'type' => 'textarea'],
        'company.logo' => ['group' => 'company', 'type' => 'image'],
        'company.favicon' => ['group' => 'company', 'type' => 'image'],
        'contact.email' => ['group' => 'contact', 'type' => 'email'],
        'contact.phone' => ['group' => 'contact', 'type' => 'phone'],
        'contact.whatsapp' => ['group' => 'contact', 'type' => 'phone'],
        'contact.address' => ['group' => 'contact', 'type' => 'textarea'],
        'contact.city' => ['group' => 'contact', 'type' => 'text'],
        'contact.country' => ['group' => 'contact', 'type' => 'text'],
        'seo.title_suffix' => ['group' => 'seo', 'type' => 'text'],
        'seo.default_title' => ['group' => 'seo', 'type' => 'text'],
        'seo.default_description' => ['group' => 'seo', 'type' => 'textarea'],
        'seo.default_og_image' => ['group' => 'seo', 'type' => 'image'],
        'seo.canonical_host' => ['group' => 'seo', 'type' => 'url'],
        'seo.twitter_handle' => ['group' => 'seo', 'type' => 'text'],
        'schema.local_business_enabled' => ['group' => 'schema', 'type' => 'boolean'],
        'tracking.gtm_container_id' => ['group' => 'tracking', 'type' => 'text'],
        'tracking.ga4_measurement_id' => ['group' => 'tracking', 'type' => 'text'],
        'tracking.meta_pixel_id' => ['group' => 'tracking', 'type' => 'text'],
        'tracking.linkedin_partner_id' => ['group' => 'tracking', 'type' => 'text'],
        'tracking.google_site_verification' => ['group' => 'tracking', 'type' => 'text'],
        'cta.header' => ['group' => 'cta', 'type' => 'text'],
        'cta.default' => ['group' => 'cta', 'type' => 'text'],
        'cta.default_service' => ['group' => 'cta', 'type' => 'text'],
        'cta.default_product' => ['group' => 'cta', 'type' => 'text'],
        'cta.default_article' => ['group' => 'cta', 'type' => 'text'],
        'leads.notify_emails' => ['group' => 'leads', 'type' => 'json'],
        'privacy.attribution_requires_consent' => ['group' => 'privacy', 'type' => 'boolean'],
        'privacy.cookie_banner_enabled' => ['group' => 'privacy', 'type' => 'boolean'],
        'footer.copyright' => ['group' => 'footer', 'type' => 'text'],
    ];

    public static function canAccess(): bool
    {
        return Gate::allows('settings.view_any');
    }

    public function mount(): void
    {
        $values = Setting::query()->whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key')->all();

        $this->form->fill(collect($values)->mapWithKeys(fn ($value, string $key) => [self::stateKey($key) => $value])->all());
    }

    public function form(Schema $schema): Schema
    {
        $ctaOptions = fn (): array => Cta::query()->orderBy('name')->pluck('name', 'key')->all();

        return $schema->components([
            Tabs::make('Settings')->tabs([
                Tab::make('Company')->icon(Heroicon::OutlinedBuildingOffice2)->schema([
                    TextInput::make('company__name')->label('Company name')->required()->maxLength(120),
                    TextInput::make('company__tagline')->label('Tagline')->maxLength(120),
                    TextInput::make('company__legal_name')->label('Legal name')->maxLength(160),
                    Textarea::make('company__description')->label('Short description')->rows(3)->maxLength(600)->columnSpanFull(),
                    $this->imageUpload('company__logo', 'Logo', 'PNG or WebP, transparent background, at least 320px wide.'),
                    $this->imageUpload('company__favicon', 'Favicon', 'Square PNG, 512×512.'),
                ])->columns(2),
                Tab::make('Contact')->icon(Heroicon::OutlinedPhone)->schema([
                    TextInput::make('contact__email')->label('Email')->email()->maxLength(255),
                    TextInput::make('contact__phone')->label('Phone')->tel()->maxLength(40),
                    TextInput::make('contact__whatsapp')->label('WhatsApp number')->tel()->maxLength(40)->helperText('International format, e.g. +91 98765 43210. Used by WhatsApp CTAs.'),
                    TextInput::make('contact__city')->label('City')->maxLength(80),
                    TextInput::make('contact__country')->label('Country')->maxLength(80),
                    Textarea::make('contact__address')->label('Address')->rows(3)->columnSpanFull(),
                ])->columns(2),
                Tab::make('SEO defaults')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    TextInput::make('seo__default_title')->label('Default title')->helperText('Used when a page has no title of its own. No length limit; roughly 50–60 characters is advisory.'),
                    TextInput::make('seo__title_suffix')->label('Title suffix')->helperText('Appended to entity titles, e.g. " | Markedge Technologies".'),
                    Textarea::make('seo__default_description')->label('Default meta description')->rows(3)->helperText('Advisory length 150–160 characters. Longer text is allowed.')->columnSpanFull(),
                    $this->imageUpload('seo__default_og_image', 'Default sharing image', '1200×630 JPG, PNG or WebP.'),
                    TextInput::make('seo__canonical_host')->label('Canonical host')->url()->helperText('e.g. https://www.markedge.example. Leave empty to use the app URL.'),
                    TextInput::make('seo__twitter_handle')->label('X / Twitter handle')->maxLength(40),
                    Toggle::make('schema__local_business_enabled')->label('Emit LocalBusiness schema (only when the address and phone are complete)')->inline(false)->columnSpanFull(),
                ])->columns(2),
                Tab::make('Tracking')->icon(Heroicon::OutlinedPresentationChartLine)->visible(fn (): bool => Gate::allows('settings.update'))->schema([
                    Section::make()->description('Public identifiers only. API secrets and server keys stay in the environment file.')->schema([
                        TextInput::make('tracking__gtm_container_id')->label('Google Tag Manager container')->placeholder('GTM-XXXXXXX')->regex('/^GTM-[A-Z0-9]+$/')->maxLength(20),
                        TextInput::make('tracking__ga4_measurement_id')->label('GA4 measurement ID')->placeholder('G-XXXXXXXXXX')->regex('/^G-[A-Z0-9]+$/')->maxLength(20),
                        TextInput::make('tracking__meta_pixel_id')->label('Meta Pixel ID')->numeric()->maxLength(30),
                        TextInput::make('tracking__linkedin_partner_id')->label('LinkedIn Insight partner ID')->numeric()->maxLength(30),
                        TextInput::make('tracking__google_site_verification')->label('Google Search Console verification')->maxLength(120),
                    ])->columns(2),
                ]),
                Tab::make('CTAs')->icon(Heroicon::OutlinedCursorArrowRays)->schema([
                    Select::make('cta__header')->label('Header button')->options($ctaOptions)->native(false)->searchable(),
                    Select::make('cta__default')->label('Default CTA band')->options($ctaOptions)->native(false)->searchable(),
                    Select::make('cta__default_service')->label('Default for services')->options($ctaOptions)->native(false)->searchable(),
                    Select::make('cta__default_product')->label('Default for products')->options($ctaOptions)->native(false)->searchable(),
                    Select::make('cta__default_article')->label('Default for articles')->options($ctaOptions)->native(false)->searchable(),
                ])->columns(2),
                Tab::make('Leads and privacy')->icon(Heroicon::OutlinedShieldCheck)->schema([
                    TagsInput::make('leads__notify_emails')->label('Lead notification emails')->placeholder('Add an address and press enter')->nestedRecursiveRules(['email'])->columnSpanFull(),
                    Toggle::make('privacy__attribution_requires_consent')->label('Only store attribution after cookie consent')->inline(false),
                    Toggle::make('privacy__cookie_banner_enabled')->label('Show the cookie banner')->inline(false),
                ])->columns(2),
                Tab::make('Footer')->icon(Heroicon::OutlinedBars3)->schema([
                    TextInput::make('footer__copyright')->label('Copyright line')->helperText('Defaults to "© {year} {company name}. All rights reserved." when empty.')->columnSpanFull(),
                ]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ])->statePath('data');
    }

    protected function imageUpload(string $name, string $label, string $helper): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory('settings')
            ->visibility('public')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(2048)
            ->helperText($helper);
    }

    public function save(): void
    {
        abort_unless(Gate::allows('settings.update'), 403);

        $state = $this->form->getState();

        foreach (self::FIELDS as $key => $definition) {
            $stateKey = self::stateKey($key);

            if (! array_key_exists($stateKey, $state)) {
                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], [
                'group' => $definition['group'],
                'type' => $definition['type'],
                'value' => $state[$stateKey],
            ]);
        }

        app(Settings::class)->forget();

        Notification::make()->title('Settings saved.')->success()->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save settings')->action('save')->visible(fn (): bool => Gate::allows('settings.update')),
        ];
    }

    public static function stateKey(string $settingKey): string
    {
        return str_replace('.', '__', $settingKey);
    }
}
