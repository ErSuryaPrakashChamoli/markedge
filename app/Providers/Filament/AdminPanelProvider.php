<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\LeadsOverview;
use App\Filament\Widgets\MyEditorialQueue;
use App\Filament\Widgets\PublishingQueue;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\RecentLeads;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('Markedge')
            ->colors([
                'primary' => Color::hex('#f26522'),
                'gray' => Color::hex('#3a414b'),
            ])
            ->strictAuthorization()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make('Editorial')->icon(Heroicon::OutlinedClipboardDocumentCheck),
                NavigationGroup::make('Website')->icon(Heroicon::OutlinedGlobeAlt),
                NavigationGroup::make('Services')->icon(Heroicon::OutlinedWrenchScrewdriver),
                NavigationGroup::make('Solutions')->icon(Heroicon::OutlinedLightBulb),
                NavigationGroup::make('Products')->icon(Heroicon::OutlinedCube),
                NavigationGroup::make('Industries')->icon(Heroicon::OutlinedBuildingOffice2),
                NavigationGroup::make('Work')->icon(Heroicon::OutlinedBriefcase),
                NavigationGroup::make('Insights')->icon(Heroicon::OutlinedNewspaper),
                NavigationGroup::make('Marketing')->icon(Heroicon::OutlinedMegaphone),
                NavigationGroup::make('Leads')->icon(Heroicon::OutlinedInboxArrowDown),
                NavigationGroup::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass),
                NavigationGroup::make('Media')->icon(Heroicon::OutlinedPhoto),
                NavigationGroup::make('System')->icon(Heroicon::OutlinedCog6Tooth)->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                ContentOverview::class,
                MyEditorialQueue::class,
                PublishingQueue::class,
                LeadsOverview::class,
                RecentLeads::class,
                RecentActivity::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
