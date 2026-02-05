<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class MatriculaPdfController extends Controller
{
    public function show(Matricula $matricula)
    {
        // Cargamos relaciones necesarias
        $matricula->load(['estudiante', 'horario.programa.cursos', 'curso']);

        $pdf = Pdf::loadView('matriculas.pdf', [
                'matricula' => $matricula,
            ])
            ->setPaper('A4', 'portrait');

        $fileName = 'ficha-matricula-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

        return $pdf->download($fileName);
    }
    public function downloadCronograma(Matricula $matricula)
    {
        $matricula->load(['estudiante', 'horario.programa', 'curso', 'cronograma.pagos' => function ($query) {
            $query->orderBy('nro_cuota', 'asc');
        }]);

        $pdf = Pdf::loadView('matriculas.cronograma-pdf', [
                'matricula' => $matricula,
            ])
            ->setPaper('A4', 'portrait');

        $fileName = 'cronograma-pagos-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
