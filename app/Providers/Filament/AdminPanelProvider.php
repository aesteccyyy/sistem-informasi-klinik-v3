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
            ->darkMode(false)
            ->sidebarWidth('14.5rem')
            ->brandLogo(asset('images/nusa-homelab-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<script>
                    try {
                        if (window.matchMedia("(min-width: 1024px)").matches) {
                            window.localStorage.setItem("isOpen", JSON.stringify(true));
                        }
                    } catch (error) {}
                </script><style>
                    :root { --care-accent: #3b82f6; --care-accent-strong: #2563eb; --care-teal: #14b8a6; --care-ink: #1e293b; --care-muted: #64748b; --care-border: #e2e8f0; --care-canvas: #f5f8fc; }
                    body.fi-body { background: #f5f8fc !important; color: #1e293b !important; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important; font-size: 14px; line-height: 1.5; }
                    .fi-layout, .fi-main-ctn { background: var(--care-canvas) !important; }
                    .fi-topbar { background: #fff !important; border-bottom: 1px solid var(--care-border) !important; box-shadow: 0 1px 3px #1e3a5f0a !important; }
                    .fi-sidebar { background: #fff !important; color: var(--care-ink) !important; border-right: 1px solid var(--care-border) !important; box-shadow: 2px 0 12px #1e3a5f08 !important; }
                    .fi-sidebar-header, .fi-sidebar-nav { background: #fff !important; }
                    .fi-sidebar-header { border-bottom: 1px solid #edf1f6 !important; }
                    .fi-sidebar-item-button { color: #475569 !important; border-radius: .55rem !important; margin-inline: .35rem !important; font-size: 13px !important; transition: background-color .15s ease, color .15s ease; }
                    .fi-sidebar-item-button:hover { color: #2563eb !important; background: #f1f6ff !important; }
                    .fi-sidebar-item-active > .fi-sidebar-item-button, .fi-sidebar-item-button[aria-current="page"] { color: #2563eb !important; background: #eaf2ff !important; font-weight: 650 !important; }
                    .fi-sidebar-group-label { color: #7c8ba1 !important; font-size: .72rem !important; font-weight: 700 !important; letter-spacing: .055em !important; text-transform: uppercase !important; }
                    .fi-sidebar-item-icon { color: #8091a7 !important; }
                    .fi-sidebar-item-active .fi-sidebar-item-icon, .fi-sidebar-item-button[aria-current="page"] .fi-sidebar-item-icon { color: #2563eb !important; }
                    .fi-page-header-heading { color: var(--care-ink) !important; letter-spacing: -.02em !important; }
                    .fi-page:has(.care-app) > section { padding-block: .75rem !important; gap: .75rem !important; }
                    .fi-section { border: 1px solid #e5ebf3 !important; border-radius: .8rem !important; box-shadow: 0 2px 10px #1e3a5f08 !important; }
                    .fi-fo-field-wrp-label { color: #34445b !important; font-size: .85rem !important; font-weight: 600 !important; }
                    .fi-input-wrp { border-color: #d8e1ec !important; border-radius: .55rem !important; box-shadow: none !important; }
                    .fi-input-wrp:focus-within { border-color: var(--care-accent) !important; box-shadow: 0 0 0 3px #3b82f61c !important; }
                    .fi-input-wrp input, .fi-input-wrp select { min-height: 2.8rem !important; font-size: .9rem !important; }
                    .fi-fo-textarea textarea { min-height: 9rem !important; line-height: 1.65 !important; font-size: .9rem !important; }
                    .fi-btn-color-primary { background: var(--care-accent) !important; }
                    .fi-btn-color-primary:hover { background: var(--care-accent-strong) !important; }
                </style>',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => <<<'HTML'
                    <script>
                        (() => {
                            if (window.__clinicSidebarHoverInstalled) return;
                            window.__clinicSidebarHoverInstalled = true;

                            const install = () => {
                                const alpine = window.Alpine;
                                const sidebarStore = alpine?.store('sidebar');
                                const sidebar = document.querySelector('.fi-sidebar');

                                if (!sidebarStore || !sidebar) {
                                    window.setTimeout(install, 60);
                                    return;
                                }

                                const desktop = window.matchMedia('(min-width: 1024px)');
                                if (desktop.matches) sidebarStore.open();
                                const keepSidebarVisible = () => {
                                    if (desktop.matches) sidebarStore.open();
                                };

                                keepSidebarVisible();
                                desktop.addEventListener('change', keepSidebarVisible);
                            };

                            install();
                        })();
                    </script>
                    HTML,
            )
            ->sidebarCollapsibleOnDesktop(false)
            ->brandName('SIMKLINIK · Nusahome Lab')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\RegistrationDesk::class,
                \App\Filament\Pages\QueueBoard::class,
                \App\Filament\Pages\QueueDashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Poliklinik'),
                NavigationGroup::make('Pendaftaran & Antrian'),
                NavigationGroup::make('Farmasi'),
                NavigationGroup::make('Integrasi SATUSEHAT'),
                NavigationGroup::make('Administrasi Klinik'),
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
