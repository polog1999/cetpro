<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\EstadoMatricula;

class EnsureIsAlumno
{
    /**
     * Verifica que el usuario autenticado sea un alumno (estudiante) con matrícula activa.
     * Si no es alumno o no tiene matrículas, redirige según corresponda.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no está autenticado, redirigir al login
        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Verificar que sea alumno
        if (!$user->esAlumno()) {
            // Si no es alumno pero está autenticado, redirigir al admin
            return redirect('/admin');
        }

        // Verificar que el estudiante tenga al menos una matrícula activa
        $estudiante = $user->estudiante;
        
        if (!$estudiante) {
            // No tiene estudiante vinculado
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'No se encontró información del estudiante.');
        }

        // Verificar si tiene al menos una matrícula que no esté anulada
        $tieneMatriculaActiva = $estudiante->matriculas()
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->exists();

        if (!$tieneMatriculaActiva) {
            // No tiene matrículas activas, redirigir al login con mensaje
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Aún no tienes matrículas registradas. Contacta a secretaría.');
        }

        return $next($request);
    }
}
