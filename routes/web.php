<?php

use Illuminate\Support\Facades\Route;// routes/web.php
// routes/web.php
use App\Http\Controllers\MatriculaPdfController;
use App\Http\Controllers\MatriculaCursosPdfController;

use App\Models\Pago;

use Illuminate\Support\Facades\Storage;

Route::get('/pagos/{pago}/evidencia', function (Pago $pago) {
    // Si no tiene archivo, 404
    abort_unless($pago->evidencia_path, 404);

    // Ruta física del archivo en el disco public
    $path = Storage::disk('public')->path($pago->evidencia_path);

    // Si no existe el archivo, 404
    abort_unless(file_exists($path), 404);

    // Devuelve el archivo (PDF o imagen)
    return response()->file($path);
})
    ->name('pagos.evidencia.show')
    ->middleware(['web', 'auth']);   // mismo auth que tu panel

   

Route::get('/matriculas/{matricula}/pdf', [MatriculaPdfController::class, 'show'])
    ->name('matriculas.pdf');

Route::get('/matriculas/{matricula}/cursos-pdf', [MatriculaCursosPdfController::class, 'show'])
    ->name('matriculas.cursos-pdf');



Route::get(' ',function () { #Configurado para que la ruta por defecto sea la principal
    return redirect('/admin');
});


