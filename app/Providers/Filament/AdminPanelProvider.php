<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;

use App\Filament\Auth\Pages\Login as CustomLogin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->login(CustomLogin::class)
            ->colors([
                'primary' => Color::Green,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
             ->sidebarCollapsibleOnDesktop()
            #Fuente tipográfica
            ->font('Lato')

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class, // Custom dashboard con filtros
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Widgets se registran en Dashboard.php personalizado
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Plugin deshabilitado - removido panel lateral verde
            // ->plugins([
            //     AuthUIEnhancerPlugin::make()
            //         ->formPanelPosition('left'),
            //         // ->emptyPanelBackgroundImageUrl(asset('imagen/mdlm.png')),
            // ])
            ->navigationGroups([
                'Gestión estudiantil',
                'Gestión académica',
                'Settings',
            ])
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName('Plataforma de gestión documentaria')
            ->brandLogo(asset('imagen/cetpro.png'))
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Cambiar Contraseña')
                    ->url(fn (): string => \App\Filament\Pages\Auth\ChangePassword::getUrl())
                    ->icon('heroicon-o-key'),
            ]);
            // ->spa(hasPrefetching: true);
    }
}