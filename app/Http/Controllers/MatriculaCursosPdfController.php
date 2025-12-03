<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class MatriculaCursosPdfController extends Controller
{
    public function show(Matricula $matricula)
    {
        // Cargamos relaciones necesarias
        $matricula->load(['estudiante', 'horario.programa.cursos', 'curso']);

        $pdf = Pdf::loadView('matriculas.cursos-pdf', [
                'matricula' => $matricula,
            ])
            ->setPaper('A4', 'portrait');

        $fileName = 'cursos-matricula-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

        return $pdf->download($fileName);
    }
}
