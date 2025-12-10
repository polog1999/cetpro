<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MatriculaPdfController;
use App\Http\Controllers\MatriculaCursosPdfController;
use App\Http\Controllers\EvidenciaPagoController;

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

Route::get('/matriculas/{matricula}/cursos-pdf', [MatriculaCursosPdfController::class, 'show'])
    ->name('matriculas.cursos-pdf');

// Ruta raíz
Route::get('/', function () {
    return redirect('/admin');
});

