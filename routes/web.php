<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MatriculaPdfController;
use App\Http\Controllers\MatriculaCursosPdfController;
use App\Http\Controllers\EvidenciaPagoController;
use App\Http\Controllers\StudentPortalController;

// Rutas seguras para evidencias de pago (con control de acceso)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pagos/{pago}/evidencia/descargar', [EvidenciaPagoController::class, 'descargar'])
        ->name('pagos.evidencia.descargar');
    
    Route::get('/pagos/{pago}/evidencia/visualizar', [EvidenciaPagoController::class, 'visualizar'])
        ->name('pagos.evidencia.visualizar');
});

// Rutas para PDFs de matrículas
Route::get('/matriculas/{matricula}/pdf', [MatriculaPdfController::class, 'show'])
    ->name('matriculas.pdf');

Route::get('/matriculas/{matricula}/cronograma-pdf', [MatriculaPdfController::class, 'downloadCronograma'])
    ->name('matriculas.cronograma-pdf');

Route::get('/matriculas/{matricula}/cursos-pdf', [MatriculaCursosPdfController::class, 'show'])
    ->name('matriculas.cursos-pdf');

// Portal de Estudiantes (Alumnos)
Route::middleware(['web', 'auth', 'alumno'])->prefix('portal')->group(function () {
    Route::get('/', [StudentPortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::get('/pagos', [StudentPortalController::class, 'pagos'])->name('portal.pagos');
    Route::get('/matriculas', [StudentPortalController::class, 'matriculas'])->name('portal.matriculas');
    Route::get('/horarios', [StudentPortalController::class, 'horarios'])->name('portal.horarios');
    Route::get('/notas', [StudentPortalController::class, 'notas'])->name('portal.notas');
    Route::get('/documentos', [StudentPortalController::class, 'documentos'])->name('portal.documentos');
    Route::get('/cambiar-password', [StudentPortalController::class, 'cambiarPasswordForm'])->name('portal.cambiar-password');
    Route::post('/cambiar-password', [StudentPortalController::class, 'cambiarPassword'])->name('portal.cambiar-password.update');
    Route::post('/logout', [StudentPortalController::class, 'logout'])->name('portal.logout');
});

// Ruta raíz
Route::get('/', function () {
    return redirect('/admin');
});


// Redirección para el middleware auth si intenta ir a 'login'
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');
