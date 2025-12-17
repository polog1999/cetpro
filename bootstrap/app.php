<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Filament\Notifications\Notification;

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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Renderizar errores personalizados para peticiones web
        $exceptions->render(function (Throwable $e, Request $request) {
            // Excluir errores de validación (estos ya se manejan bien)
            if ($e instanceof ValidationException) {
                return null; // Dejar que Laravel maneje esto normalmente
            }

            // Excluir errores de autenticación (redirigen al login)
            if ($e instanceof AuthenticationException) {
                return null;
            }

            // Solo procesar para peticiones web (no API)
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Las API manejan sus propios errores
            }

            // Registrar el error para debugging
            \Log::error('Error capturado: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            // Determinar el mensaje amigable según el tipo de error
            $mensaje = match (true) {
                $e instanceof NotFoundHttpException => 'El recurso solicitado no fue encontrado.',
                $e instanceof QueryException => 'Ocurrió un error al procesar la información. Por favor, intente nuevamente.',
                $e instanceof HttpException => match ($e->getStatusCode()) {
                    403 => 'No tiene permisos para realizar esta acción.',
                    404 => 'La página solicitada no existe.',
                    419 => 'Su sesión ha expirado. Por favor, recargue la página e intente nuevamente.',
                    429 => 'Demasiadas solicitudes. Por favor, espere un momento antes de intentar nuevamente.',
                    500 => 'Ocurrió un error interno. Nuestro equipo ha sido notificado.',
                    503 => 'El servicio no está disponible temporalmente. Por favor, intente más tarde.',
                    default => 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                },
                default => 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
            };

            // Si la petición viene de Filament, usar notificación y redirección
            if (str_contains($request->path(), 'admin')) {
                Notification::make()
                    ->title('¡Oops! Algo salió mal')
                    ->body($mensaje)
                    ->danger()
                    ->persistent()
                    ->send();

                // Redirigir a la página anterior o al dashboard
                $previousUrl = url()->previous();
                $currentUrl = $request->fullUrl();
                
                // Evitar loop infinito si la URL anterior es la misma
                if ($previousUrl === $currentUrl) {
                    return redirect()->route('filament.admin.pages.dashboard');
                }

                return redirect()->to($previousUrl);
            }

            // Para otras peticiones web, redirigir con mensaje de error
            return redirect()->back()->with('error', $mensaje);
        });
    })->create();
