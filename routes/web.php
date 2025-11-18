<?php

use Illuminate\Support\Facades\Route;// routes/web.php
// routes/web.php
use App\Http\Controllers\MatriculaPdfController;



   

Route::get('/matriculas/{matricula}/pdf', [MatriculaPdfController::class, 'show'])
    ->name('matriculas.pdf');



Route::get(' ',function () { #Configurado para que la ruta por defecto sea la principal
    return redirect('/admin');
});


