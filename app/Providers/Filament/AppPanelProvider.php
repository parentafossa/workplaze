<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\FontProviders\GoogleFontProvider;
 
use Filament\Support\Enums\MaxWidth;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\CustomAuthorizationHandler;


use App\Http\Middleware\CheckApprovalState;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

use App\Http\Middleware\FilamentCustomAuth; 
use App\Http\Middleware\ShieldPermissionDebugMiddleware;

use Filament\Navigation\NavigationGroup;
use App\Filament\Widgets\AttLogAnalysisWidget;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->passwordReset()
            ->emailVerification()
            ->domain('workplaze.logisteed.id')  // add this if not present
            ->authGuard('web')
            ->colors([
                //'primary' => Color::Amber,
                'primary' => '#3672b7', 
                //'#1654A4',
            ])
            ->favicon(asset('images/ld_stripe.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
                AttLogAnalysisWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
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
                CheckApprovalState::class,

                //CustomAuthorizationHandler::class,
                //\Filament\Http\Middleware\Authenticate::class,
                //ShieldPermissionDebugMiddleware::class, 
            ])
            //->notifications()
            ->databaseNotifications()
            ->spa()
            ->profile()
            //->topNavigation()
            ->maxContentWidth('full')
            ->brandLogo(asset('images/logo.png'))
            ->authMiddleware([
                Authenticate::class,
                //FilamentCustomAuth::class,
            ])
            /* ->navigationGroups([
                NavigationGroup::make()
                    ->label('Basic Data')
                    ->icon('heroicon-o-cube')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Pine Lines')
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make()
                    ->label('Customer Management')
                    ->icon('heroicon-o-users'),
            ]) */
            ->font('Roboto Condensed', provider: GoogleFontProvider::class)
            ->collapsibleNavigationGroups(true)
            ->viteTheme('resources/css/filament/app/theme.css');
    }

}
