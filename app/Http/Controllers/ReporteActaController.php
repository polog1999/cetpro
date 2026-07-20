<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Curso;
use App\Models\Unidad;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Str;

class ReporteActaController extends Controller
{
    public function stream($horario_id, $anio, $curso_id)
    {
        $horario = Horario::with(['programa', 'docente', 'programa.cursos'])->findOrFail($horario_id);
        $curso = Curso::findOrFail($curso_id);
        
        // Lógica dinámica: Si es Formación Continua usa los cursos, si es Programa usa las unidades
        $tipoProg = $horario->programa->tipo_programa;
        $esFormacionContinua = ($tipoProg == 'FORMACION_CONTINUA' || (is_object($tipoProg) && $tipoProg->value == 'FORMACION_CONTINUA'));

        if ($esFormacionContinua) {
            $columnas = $horario->programa->cursos()->orderBy('fecha_inicio')->get();
        } else {
            $columnas = Unidad::where('id_curso', $curso_id)->orderBy('id_unidad')->get();
        }

        $matriculas = Matricula::with('estudiante')
            ->where('horario_id', $horario_id)
            ->where(function($q) use ($curso_id) {
                $q->where('id_curso', $curso_id)->orWhereNull('id_curso');
            })
            ->where('codigo_inscripcion', 'like', $anio . '%')
            ->whereIn('estado', [EstadoMatricula::ENPROCESO->value, EstadoMatricula::CULMINADO->value])
            ->get()
            ->unique('estudiante_id')
            ->sortBy('estudiante.apellido_paterno')
            ->values();

        $templatePath = public_path('plantillas/acta.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        // Cabeceras
        $templateProcessor->setValue('cetpro', 'LA MOLINA');
        $templateProcessor->setValue('programa', mb_strtoupper($horario->programa->nombre_programa));
        $templateProcessor->setValue('modulo', mb_strtoupper($curso->nombre_curso));
        $templateProcessor->setValue('anio', $anio);
        $templateProcessor->setValue('docente', $horario->docente ? mb_strtoupper($horario->docente->nombre_completo) : 'NO ASIGNADO');

        // Títulos de columnas (u1_nombre hasta u8_nombre)
        for ($j = 1; $j <= 8; $j++) {
            $col = $columnas->get($j - 1);
            $nombreCol = $col ? ($esFormacionContinua ? $col->nombre_curso : $col->nombre_unidad) : '';
            $templateProcessor->setValue("titulo_u{$j}", mb_strtoupper($nombreCol));
        }

        // Filas alumnos
        for ($i = 1; $i <= 40; $i++) {
            $mat = $matriculas->get($i - 1);
            $templateProcessor->setValue("n_{$i}", $mat ? $i : '');
            $templateProcessor->setValue("cod_{$i}", $mat ? $mat->estudiante->nro_documento : '');
            $templateProcessor->setValue("nom_{$i}", $mat ? mb_strtoupper("{$mat->estudiante->apellido_paterno} {$mat->estudiante->apellido_materno}, {$mat->estudiante->nombres}") : '');

            $suma = 0; $conNota = 0; $aprobadas = 0; $desaprobadas = 0;

            for ($j = 1; $j <= 8; $j++) {
                $item = $columnas->get($j - 1);
                $notaVal = '';
                
                if ($mat && $item) {
                    $queryNota = Nota::where('matricula_id', $mat->id)
                                     ->where('curso_id', $curso_id);
                    
                    // Lógica corregida: 
                    // Si es Formación Continua, el ID es del CURSO.
                    // Si es Programa, el ID es de la UNIDAD.
                    if ($esFormacionContinua) {
                        $queryNota->where('curso_id', $item->id_curso); 
                    } else {
                        $queryNota->where('unidad_id', $item->id_unidad);
                    }
                    
                    $nota = $queryNota->value('nota_numerica');
                    
                    if ($nota !== null) {
                        $notaVal = str_pad($nota, 2, '0', STR_PAD_LEFT);
                        $suma += $nota; 
                        $conNota++;
                        ($nota >= 13) ? $aprobadas++ : $desaprobadas++;
                    }
                }
                $templateProcessor->setValue("u{$j}_{$i}", $notaVal);
            }
            $templateProcessor->setValue("logro_{$i}", $conNota > 0 ? str_pad(round($suma/$conNota), 2, '0', STR_PAD_LEFT) : '');
            $templateProcessor->setValue("aprob_{$i}", $mat ? str_pad($aprobadas, 2, '0', STR_PAD_LEFT) : '');
            $templateProcessor->setValue("desap_{$i}", $mat ? str_pad($desaprobadas, 2, '0', STR_PAD_LEFT) : '');
        }

        $tempFile = storage_path('app/temp/Acta_Generada_' . time() . '.docx');
        $templateProcessor->saveAs($tempFile);
        exec('"C:\Program Files\LibreOffice\program\soffice.exe" --headless --convert-to pdf --outdir ' . storage_path('app/temp/') . ' ' . $tempFile);

        return response()->download(str_replace('.docx', '.pdf', $tempFile));
    }
}