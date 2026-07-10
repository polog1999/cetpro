<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class MatriculaCursosPdfController extends Controller
{
    public function show(Matricula $matricula)
    {
        // Cargamos relaciones necesarias, incluyendo cronograma y pagos
        $matricula->load(['estudiante', 'horario.programa.cursos', 'curso', 'cronograma.pagos']);

        // Obtenemos los IDs de los cursos correspondientes a sus meses de estudio contratados
        $cursosActivosIds = $matricula->obtenerCursosActivos()->pluck('id_curso')->toArray();

        $pdf = Pdf::loadView('matriculas.cursos-pdf', [
                'matricula'        => $matricula,
                'cursosActivosIds' => $cursosActivosIds, // Enviamos los IDs activos a la vista
            ])
            ->setPaper('A4', 'portrait');

        $fileName = 'cursos-matricula-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

        return $pdf->download($fileName);
    }
}