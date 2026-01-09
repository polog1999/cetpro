<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    /**
     * Dashboard del estudiante.
     */
    public function dashboard(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        return view('portal.dashboard', [
            'estudiante' => $estudiante,
            'matriculasActivas' => $estudiante->matriculas()
                ->where('estado', '!=', 'anulado')
                ->count(),
            'ultimaMatricula' => $estudiante->matriculas()
                ->latest()
                ->first(),
        ]);
    }

    /**
     * Lista de pagos del estudiante.
     */
    public function pagos(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        // Obtener todos los pagos del estudiante a través de sus matrículas
        $pagos = \App\Models\Pago::whereHas('cronograma.matricula', function ($query) use ($estudiante) {
            $query->where('estudiante_id', $estudiante->id);
        })
        ->with(['cronograma.matricula.horario.programa'])
        ->orderBy('nro_cuota', 'asc')
        ->get();
        
        return view('portal.pagos', [
            'estudiante' => $estudiante,
            'pagos' => $pagos,
        ]);
    }

    /**
     * Lista de matrículas del estudiante.
     */
    public function matriculas(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        $matriculas = $estudiante->matriculas()
            ->with(['horario.programa', 'curso.programa', 'cronograma.pagos'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('portal.matriculas', [
            'estudiante' => $estudiante,
            'matriculas' => $matriculas,
        ]);
    }

    /**
     * Horarios del estudiante.
     */
    public function horarios(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        $horarios = $estudiante->matriculas()
            ->where('estado', '!=', 'anulado')
            ->with(['horario.programa', 'horario.docente'])
            ->get()
            ->pluck('horario')
            ->filter();
        
        return view('portal.horarios', [
            'estudiante' => $estudiante,
            'horarios' => $horarios,
        ]);
    }

    /**
     * Notas del estudiante.
     */
    public function notas(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        $notas = $estudiante->notas()
            ->with(['matricula.horario.programa'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('portal.notas', [
            'estudiante' => $estudiante,
            'notas' => $notas,
        ]);
    }

    /**
     * Documentos del estudiante (PDFs subidos en sus matrículas).
     */
    public function documentos(Request $request): View
    {
        $estudiante = $request->user()->estudiante;
        
        // Obtener matrículas que tienen documento subido
        $matriculasConDocumento = $estudiante->matriculas()
            ->whereNotNull('documento_path')
            ->with(['horario.programa'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('portal.documentos', [
            'estudiante' => $estudiante,
            'matriculas' => $matriculasConDocumento,
        ]);
    }

    /**
     * Cerrar sesión del estudiante.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('filament.admin.auth.login');
    }

    /**
     * Mostrar formulario de cambio de contraseña.
     */
    public function cambiarPasswordForm(Request $request): View
    {
        return view('portal.cambiar-password', [
            'estudiante' => $request->user()->estudiante,
        ]);
    }

    /**
     * Procesar cambio de contraseña.
     */
    public function cambiarPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_actual' => 'required',
            'password_nuevo' => 'required|min:6|confirmed',
        ], [
            'password_actual.required' => 'La contraseña actual es obligatoria.',
            'password_nuevo.required' => 'La nueva contraseña es obligatoria.',
            'password_nuevo.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password_nuevo.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = $request->user();

        // Verificar contraseña actual
        if (!password_verify($request->password_actual, $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual es incorrecta.']);
        }

        // Actualizar contraseña
        $user->password = $request->password_nuevo;
        $user->save();

        return redirect()->route('portal.dashboard')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
