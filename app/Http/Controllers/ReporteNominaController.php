<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Matricula;
use App\Enums\EstadoMatricula;
use App\Enums\TipoGenero;
use App\Enums\TipoPrograma;
use Barryvdh\DomPDF\Facade\Pdf;
use \Illuminate\Support\Str;

class ReporteNominaController extends Controller
{
    public function stream($horario_id, $anio, $curso_id)
    {
        $horario = Horario::with(['programa', 'docente'])->findOrFail($horario_id);

        // 1. Buscamos el nombre del curso/módulo
        $modulo = \App\Models\Curso::find($curso_id);
        $nombreModulo = $modulo ? $modulo->nombre_curso : '';
        $tipoProg = $horario->programa->tipo_programa;
        $esFormacionContinua = ($tipoProg == TipoPrograma::FORMACION_CONTINUA);
        $matriculas = Matricula::with('estudiante')
            //MATRICULA DE PRUEBA CON ESTUDIANTE TEST
            ->where('id', '!=', 42)
            ->where('horario_id', $horario_id)
            ->where(function ($q) use ($curso_id, $esFormacionContinua) {
                if ($esFormacionContinua) {
                    // SI ES FORMACIÓN CONTINUA:
                    // Incluimos al que se matriculó al paquete completo (null) 
                    // Y al que se matriculó específicamente a este curso ($curso_id)
                    $q->whereNotNull('id_curso')
                        ->orWhereNull('id_curso');
                } else {
                    // SI ES PROGRAMA DE ESTUDIOS:
                    // Incluimos al que está en este módulo específico ($curso_id)
                    // Y al del programa completo (null)
                    $q->where('id_curso', $curso_id)
                        ->orWhereNull('id_curso');
                }
            })
            ->where('codigo_inscripcion', 'like', $anio . '%')
            ->whereIn('estado', [EstadoMatricula::ENPROCESO->value, EstadoMatricula::CULMINADO->value])
            ->get()
            ->unique('estudiante_id')
            ->sortBy('estudiante.apellido_paterno')
            ->values();

        $totalHombres = 0;
        $totalMujeres = 0;

        $alumnosProcesados = $matriculas->map(function ($mat) use (&$totalHombres, &$totalMujeres) {
            $est = $mat->estudiante;
            $generoLabel = $est->genero instanceof TipoGenero ? $est->genero->value : $est->genero;
            $letraSexo = ($generoLabel === TipoGenero::MASCULINO->value) ? 'H' : 'M';

            if ($letraSexo === 'H') $totalHombres++;
            else $totalMujeres++;

            return [
                'codigo' => $mat->estudiante->nro_documento,
                'apellidos_nombres' => Str::upper("{$est->apellido_paterno} {$est->apellido_materno}, ") . trim(Str::title(Str::lower($est->nombres))),
                'sexo' => $letraSexo,
                'edad' => $est->fecha_nacimiento ? \Carbon\Carbon::parse($est->fecha_nacimiento)->age : '-',
                'condicion' => 'P'
            ];
        });

        $pdf = Pdf::loadView('reportes.nomina-pdf', [
            'horario' => $horario,
            'anio' => $anio,
            'curso_id' => $curso_id, // 👈 ASEGÚRATE DE AÑADIR ESTA LÍNEA
            'alumnos' => $alumnosProcesados,
            'nombreModulo' => $nombreModulo, // 👈 Pasamos el nombre del módulo aquí
            'resumen' => [
                'hombres' => $totalHombres,
                'mujeres' => $totalMujeres,
                'total' => $totalHombres + $totalMujeres,
                'pagantes' => $totalHombres + $totalMujeres,
            ]
            // Ojo: Cambié a 'landscape' porque tu blade decía que el MINEDU lo pide vertical
        ])->setPaper('A4', 'portrait');

        $nombreArchivo = "Nomina_{$anio}_{$horario->programa->nombre_programa}.pdf";

        return $pdf->stream($nombreArchivo);
    }
}
