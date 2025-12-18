<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Rate Limiting para API - 60 peticiones por minuto
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate Limiting para login - 5 intentos por minuto (previene brute-force)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Registrar Observer para actualizar estado de matrícula automáticamente
        \App\Models\Pago::observe(\App\Observers\PagoObserver::class);

        // Registrar Policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Usuario::class, \App\Policies\UsuarioPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Role::class, \App\Policies\RolePolicy::class);
    }
}
