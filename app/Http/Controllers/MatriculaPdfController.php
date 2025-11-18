<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class MatriculaPdfController extends Controller
{
    public function show(Matricula $matricula)
    {
        // Cargamos relaciones necesarias
        $matricula->load(['estudiante', 'seccion.programa', 'curso']);

        $pdf = Pdf::loadView('matriculas.pdf', [
                'matricula' => $matricula,
            ])
            ->setPaper('A4', 'portrait');

        $fileName = 'ficha-matricula-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

        return $pdf->download($fileName);
    }
}
