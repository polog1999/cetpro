<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Filament\Notifications\Notification;
use Throwable;
use Illuminate\Http\RedirectResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistoryMiddleware::class,
            'alumno' => \App\Http\Middleware\EnsureIsAlumno::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejar errores de autenticación - redirigir al login
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null; // Dejar que Livewire maneje sus propias peticiones
            }
            
            return redirect()->guest(route('filament.admin.auth.login'));
        });
        
        // Manejar errores 404 - mostrar página de error o redirigir
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null; // Dejar que la API/Livewire maneje su propia respuesta
            }
            
            // Si el usuario no está autenticado, redirigir al login
            if (!auth()->check()) {
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Debes iniciar sesión para acceder al sistema.');
            }
            
            // Si está autenticado, mostrar la página 404 personalizada
            return response()->view('errors.404', [], 404);
        });
        
        // Manejar errores 403 (acceso denegado y autorización)
        $exceptions->render(function (Throwable $e, Request $request) {
            $is403 = false;

            if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                $is403 = true;
            } elseif ($e instanceof HttpException && $e->getStatusCode() === 403) {
                $is403 = true;
            }   

            if (!$is403) {
                return null;
            }

            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }
            
            // Si no está autenticado, redirigir al login
            if (!auth()->check()) {
                return new RedirectResponse(route('filament.admin.auth.login'));
            }
            
            // Si está autenticado, enviar notificación y redirigir al dashboard
            Notification::make()
                ->warning()
                ->title('Acceso denegado')
                ->body('No tienes los permisos necesarios para acceder a este módulo.')
                ->send();
                
            return new RedirectResponse(route('filament.admin.pages.dashboard'));
        });
        
        // Manejar otros errores HTTP (500, etc)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }
            
            $statusCode = $e->getStatusCode();
            
            // Si existe una vista personalizada para este código, usarla
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [], $statusCode);
            }
            
            // Fallback a la página 500
            return response()->view('errors.500', [], 500);
        });
    })->create();

