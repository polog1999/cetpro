<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Curso;
use App\Models\Unidad;
use App\Enums\EstadoMatricula;
use App\Enums\TipoPrograma;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use \Illuminate\Support\Str;

class ReporteActaController extends Controller
{
    public function stream($horario_id, $anio, $curso_id)
    {
        $horario = Horario::with(['programa', 'docente', 'programa.cursos'])->findOrFail($horario_id);

        if ($curso_id != 0) {
            $curso = Curso::findOrFail($curso_id);
        }

        $tipoProg = $horario->programa->tipo_programa;
        $esFormacionContinua = ($tipoProg == TipoPrograma::FORMACION_CONTINUA);

        if ($esFormacionContinua) {
            $columnas = $horario->programa->cursos()->orderBy('fecha_inicio')->get();
            $nombrePrograma = 'FORMACIÓN CONTINUA';
            $nombreModulo = $horario->programa->nombre_programa;
        } else {
            $columnas = Unidad::where('id_curso', $curso_id)->orderBy('orden')->get();
            $nombrePrograma = $horario->programa->nombre_programa;
            $nombreModulo = mb_strtoupper($curso->nombre_curso);
        }

        // 1. OBTENEMOS LAS MATRÍCULAS CRUDAS (Agrupamos por estudiante para unificar duplicados)
        $matriculasCrudas = Matricula::with('estudiante')
            ->where('horario_id', $horario_id)
            ->where(function ($q) use ($curso_id, $esFormacionContinua) {
                if ($esFormacionContinua) {
                    $q->whereNotNull('id_curso')
                        ->orWhereNull('id_curso');
                } else {
                    $q->where('id_curso', $curso_id)
                        ->orWhereNull('id_curso');
                }
            })
            ->where('codigo_inscripcion', 'like', $anio . '%')
            ->whereIn('estado', [EstadoMatricula::ENPROCESO->value, EstadoMatricula::CULMINADO->value])
            ->get();

        // Agrupamos por estudiante para manejar los que tienen múltiples matrículas (curso por curso)
        $estudiantesAgrupados = $matriculasCrudas->groupBy('estudiante_id');
        
        $matriculas = $estudiantesAgrupados->map(function ($grupo) {
            return $grupo->first(); // Tomamos una matrícula de referencia para los datos personales
        })->sortBy('estudiante.apellido_paterno')->values();

        $templatePath = public_path('plantillas/acta.docx');

        if (!File::exists($templatePath)) {
            Log::error("La plantilla no existe en: {$templatePath}");
            return back()->with('error', 'No se encontró la plantilla base acta.docx.');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Cabeceras
        $templateProcessor->setValue('cetpro', 'LA MOLINA');
        $templateProcessor->setValue('programa', mb_strtoupper($nombrePrograma));
        $templateProcessor->setValue('modulo', mb_strtoupper($nombreModulo));
        $templateProcessor->setValue('anio', $anio);
        $templateProcessor->setValue('docente', $horario->docente ? mb_strtoupper($horario->docente->nombre_completo) : 'NO ASIGNADO');

        // Títulos de columnas
        for ($j = 1; $j <= 10; $j++) {
            $col = $columnas->get($j - 1);

            $nombreCol = $col ? ($esFormacionContinua ? $col->nombre_curso : $col->nombre_unidad) : '';
            // Escapar caracteres especiales para XML (&, <, >, etc.)
            $nombreColLimpio = htmlspecialchars(mb_strtoupper($nombreCol), ENT_QUOTES, 'UTF-8');
            
            $templateProcessor->setValue("titulo_u{$j}", $nombreColLimpio);
        }
        $templateProcessor->setValue('hoy', now()->format('d - m - Y'));

        // Filas alumnos
        for ($i = 1; $i <= 40; $i++) {
            $mat = $matriculas->get($i - 1);
            
            // Recogemos los IDs de TODAS las matrículas que tenga este estudiante específico
            $idsMatriculasDelAlumno = [];
            if ($mat) {
                $idsMatriculasDelAlumno = $estudiantesAgrupados[$mat->estudiante_id]->pluck('id')->toArray();
            }

            $templateProcessor->setValue("n_{$i}", $mat ? $i : '');
            $templateProcessor->setValue("cod_{$i}", $mat ? $mat->estudiante->nro_documento : '');
            $templateProcessor->setValue("nom_{$i}", $mat ? Str::upper("{$mat->estudiante->apellido_paterno} {$mat->estudiante->apellido_materno}, ") . trim(Str::title(Str::lower($mat->estudiante->nombres))) : '');

            $suma = 0;
            $conNota = 0;
            $aprobadas = 0;
            $desaprobadas = 0;

            for ($j = 1; $j <= 10; $j++) {
                $item = $columnas->get($j - 1);
                $notaVal = '';

                // Buscamos usando whereIn con todas las matrículas del alumno
                if (!empty($idsMatriculasDelAlumno) && $item) {
                    $queryNota = Nota::whereIn('matricula_id', $idsMatriculasDelAlumno);

                    if ($esFormacionContinua) {
                        $queryNota->where('curso_id', $item->id_curso);
                    } else {
                        $queryNota->where('unidad_id', $item->id_unidad);
                    }

                    $nota = $queryNota->value('nota_numerica');

                    if ($nota !== null) {
                        $notaVal = str_pad((int) $nota, 2, '0', STR_PAD_LEFT);
                        $suma += $nota;
                        $conNota++;
                        ($nota >= 13) ? $aprobadas++ : $desaprobadas++;
                    }
                }
                $templateProcessor->setValue("u{$j}_{$i}", $notaVal);
            }
            $templateProcessor->setValue("logro_{$i}", $conNota > 0 ? str_pad(round($suma / $conNota), 2, '0', STR_PAD_LEFT) : '');
            $templateProcessor->setValue("aprob_{$i}", $mat ? str_pad($aprobadas, 2, '0', STR_PAD_LEFT) : '');
            $templateProcessor->setValue("desap_{$i}", $mat ? str_pad($desaprobadas, 2, '0', STR_PAD_LEFT) : '');
        }
        //==========================================PARA PDF========================================
        // $tempPath = storage_path('app/temp');
        // File::ensureDirectoryExists($tempPath);

        // $fileName = 'Acta_Generada_' . time();
        // $docxPath = $tempPath . DIRECTORY_SEPARATOR . $fileName . '.docx';
        // $pdfPath = $tempPath . DIRECTORY_SEPARATOR . $fileName . '.pdf';

        // // Guardar el DOCX
        // $templateProcessor->saveAs($docxPath);

        // // Detectar ejecutable según el SO
        // if (PHP_OS_FAMILY === 'Windows') {
        //     $soffice = '"C:\Program Files\LibreOffice\program\soffice.exe"';
        // } else {
        //     // En Linux es importante asignar un HOME temporal para que www-data no falle al crear perfil
        //     $soffice = 'export HOME=/tmp && libreoffice';
        // }

        // // Ejecutar comando capturando la salida de error (2>&1)
        // $command = "{$soffice} --headless --convert-to pdf --outdir " . escapeshellarg($tempPath) . " " . escapeshellarg($docxPath) . " 2>&1";
        // exec($command, $output, $returnVar);

        // if ($returnVar !== 0 || !file_exists($pdfPath)) {
        //     Log::error("Error LibreOffice (Code {$returnVar}): " . implode("\n", $output));
        //     return back()->with('error', 'Error al generar el PDF. Revisa los logs.');
        // }

        // // Limpiar el DOCX temporal
        // @unlink($docxPath);

        // // Retornar la descarga directa sin deleteFileAfterSend inmediato para evitar cortes
        // return response()->download($pdfPath, "{$fileName}.pdf", [
        //     'Content-Type' => 'application/pdf',
        // ]);
        //=======================================PARA PDF==================================================
        $tempPath = storage_path('app/temp');
        File::ensureDirectoryExists($tempPath);

        $fileName = 'Acta_Generada_' . time() . '.docx';
        $docxPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;

        // Guardar directamente el archivo Word procesado
        $templateProcessor->saveAs($docxPath);

        // Retornar la descarga del archivo .docx
        return response()->download($docxPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}