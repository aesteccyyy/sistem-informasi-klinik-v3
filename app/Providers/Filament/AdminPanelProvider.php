<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
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
            ->topNavigation()
            ->brandLogo(asset('images/nusa-homelab-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>
                        .fi-topbar nav { column-gap: .5rem !important; padding-inline: clamp(1.25rem, 3vw, 2.5rem) !important; }
                        .fi-topbar nav > ul { column-gap: .15rem !important; margin-inline-end: .35rem !important; }
                        .fi-topbar nav .fi-logo { width: 7.5rem; object-fit: contain; }
                        .fi-topbar-item-button { column-gap: .25rem !important; padding: .3rem .4rem !important; }
                        .fi-topbar-item-label { font-size: .7rem !important; line-height: .9rem !important; }
                        .fi-topbar-item-icon, .fi-topbar-group-toggle-icon { width: .9rem !important; height: .9rem !important; }
                    .fi-topbar nav .fi-dropdown-list-item { column-gap: .4rem !important; padding: .4rem .55rem !important; font-size: .8rem !important; }
                    .fi-topbar nav .fi-dropdown-list-item-icon { width: 1rem !important; height: 1rem !important; }
                    .fi-topbar nav > ul > .topnav-order-1 { order: 1; }
                    .fi-topbar nav > ul > .topnav-order-2 { order: 2; }
                    .fi-topbar nav > ul > li:has(a[href*="/admin/emr"]) { order: 3; }
                    .fi-topbar nav > ul > .topnav-order-4 { order: 4; }
                    .fi-topbar nav > ul > .topnav-order-5 { order: 5; }
                    .fi-topbar nav > ul > .topnav-order-6 { order: 6; }
                </style>',
            )
            ->brandName('SIMKLINIK · Nusahome Lab')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\RegistrationDesk::class,
                \App\Filament\Pages\QueueBoard::class,
                \App\Filament\Pages\QueueDashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Appointment Management')->extraTopbarAttributes(['class' => 'topnav-order-1']),
                NavigationGroup::make('Registration Counter')->extraTopbarAttributes(['class' => 'topnav-order-2']),
                NavigationGroup::make('Medication Management')->extraTopbarAttributes(['class' => 'topnav-order-4']),
                NavigationGroup::make('SATUSEHAT')->extraTopbarAttributes(['class' => 'topnav-order-5']),
                NavigationGroup::make('Clinic Management')->extraTopbarAttributes(['class' => 'topnav-order-6']),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\PatientVisitsChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
            ])
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Indigo,
                'primary' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ]);
    }
}
