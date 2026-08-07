<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Curso;
use App\Models\Unidad;
use App\Enums\EstadoMatricula;
use App\Enums\TipoPrograma;
use App\Models\UnidadDidacticaUgel;
use Filament\Notifications\Notification;
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
        $chtotal = '';
        if ($esFormacionContinua) {
            $columnas = UnidadDidacticaUgel::where('programa_id', $horario->programa->id_programa)->orderBy('orden')->get();
            $nombrePrograma = 'FORMACIÓN CONTINUA';
            $nombreModulo = $horario->programa->nombre_programa;
            $chtotal = $horario->programa->creditos . ' / ' . $horario->programa->horas;
        } else {
            $columnas = UnidadDidacticaUgel::where('programa_id', $horario->programa->id_programa)->where('curso_id', $curso_id)->orderBy('orden')->get();

            $nombrePrograma = $horario->programa->nombre_programa;
            $nombreModulo = mb_strtoupper($curso->nombre_curso);
            $chtotal = $curso->creditos . ' / ' . $curso->horas;
        }
        $totalColumnas = $columnas->count();
        // 1. OBTENEMOS LAS MATRÍCULAS CRUDAS (Agrupamos por estudiante para unificar duplicados)
        $matriculasCrudas = Matricula::with('estudiante')
            //MATRICULA DE PRUEBA CON ESTUDIANTE TEST
            ->where('id', '!=', 42)
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
            ->whereHas('cronograma.pagos', function ($q) {
                $q->where('estado', 'Cancelado');
            }) //FILTRA SOLO LAS MATRICULAS QUE PAGARON ALMENOS UNA VEZ
            ->where('codigo_inscripcion', 'like', $anio . '%')
            ->whereIn('estado', [EstadoMatricula::ENPROCESO->value, EstadoMatricula::CULMINADO->value])
            ->get();

        // Agrupamos por estudiante para manejar los que tienen múltiples matrículas (curso por curso)
        $estudiantesAgrupados = $matriculasCrudas->groupBy('estudiante_id');

        $matriculas = $estudiantesAgrupados->map(function ($grupo) {
            return $grupo->first(); // Tomamos una matrícula de referencia para los datos personales
        })->sortBy('estudiante.apellido_paterno')->values();

        $templatePath = public_path('plantillas/ACTA.docx');

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
        $templateProcessor->setValue('turno',  mb_strtoupper($horario->turno->value));
        $templateProcessor->setValue('docente', $horario->docente ? mb_strtoupper($horario->docente->nombre_completo) : 'NO ASIGNADO');
        $templateProcessor->setValue('chtotal', $chtotal ?? '');
        // Títulos de columnas
        for ($j = 1; $j <= 10; $j++) {
            $col = $columnas->get($j - 1);

            // Si no hay columna o es un elemento EFSRT, dejamos los valores vacíos
            if (!$col || $col->es_efsrt) {
                $templateProcessor->setValue("titulo_u{$j}", '');
                $templateProcessor->setValue("c/h_u{$j}", '');
                $templateProcessor->setValue("capacidad_u{$j}", '');
                continue; // Pasamos a la siguiente iteración
            }

            // Si es un curso/unidad normal, obtenemos su nombre
            $nombreCol = $col->nombre ?? '';

            // Limpieza de texto para XML
            $nombreColLimpio = htmlspecialchars(mb_strtoupper($nombreCol), ENT_QUOTES, 'UTF-8');
            $nombreCapacidadLimpio = htmlspecialchars(mb_strtoupper($nombreCol), ENT_QUOTES, 'UTF-8');
            // Asignamos los valores al Template Processor
            $templateProcessor->setValue("titulo_u{$j}", $nombreColLimpio);
            $templateProcessor->setValue("c/h_u{$j}", $col->creditos . ' / ' . $col->horas);
            $templateProcessor->setValue("capacidad_u{$j}", $col->capacidad);
        }
        // Obtenemos el primer elemento que cumpla con la condición
        $efsrt = $columnas->first(fn($item) => $item->es_efsrt == true);

        // Evaluamos correctamente y asignamos según el tipo de colección
        $valorEfsrt = '';
        if ($efsrt) {
            $valorEfsrt = $efsrt->nombre ?? '';
        }
        $templateProcessor->setValue("efsrt", $valorEfsrt);
        $templateProcessor->setValue("c/h_efsrt", ($efsrt ? $efsrt->creditos . ' / ' . $efsrt->horas : '') ?? '');
        $templateProcessor->setValue('hoy', now()->format('d - m - Y'));


        // ==========================================
        // NUEVO: ARREGLOS PARA CONTAR ESTADÍSTICAS POR COLUMNA
        // ==========================================
        $totalesAprobados = array_fill(1, 10, 0);
        $totalesDesaprobados = array_fill(1, 10, 0);
        $totalesRetirados = array_fill(1, 10, 0);
        // Pre-recolectamos la matriz de notas de todos los alumnos por cada columna (1 al 10)
        // $matrizNotasPorColumna = array_fill(1, 10, []);


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
            $notaEfsrtVal = ''; // Variable para almacenar la nota de la EFSRT de este alumno

            // $topeAprobado = false;

            for ($j = 1; $j <= 10; $j++) {
                $item = $columnas->get($j - 1);
                $notaVal = '';

                // Buscamos usando whereIn con todas las matrículas del alumno
                if (!empty($idsMatriculasDelAlumno) && $item) {
                    $queryNota = Nota::whereIn('matricula_id', $idsMatriculasDelAlumno);

                    $queryNota->where('unidad_id', $item->id);

                    // 1. Ejecutamos la consulta para obtener el registro
                    $notaModel = $queryNota->first();

                    // 2. Evaluamos la lógica solicitada para el EFSRT
                    $nota = '';
                    if ($notaModel) {
                        $esEfsrt = false;

                        // // Si la unidad es null, buscamos es_efsrt en curso
                        // if (is_null($notaModel->unidad_id) && $notaModel->curso) {
                        $esEfsrt = (bool) $notaModel->unidad->es_efsrt;
                        // }
                        // // Sino, si la unidad tiene un valor no null, buscamos en unidad
                        // elseif (!is_null($notaModel->unidad_id) && $notaModel->unidad) {
                        //     $esEfsrt = (bool) $notaModel->es_efsrt;
                        // }

                        // Si no es EFSRT, asignamos la nota numérica
                        if (!$esEfsrt) {
                            $nota = $notaModel->nota_numerica ?? '';
                        }
                    }

                    if ($nota !== null && is_numeric($nota)) {
                        $notaInt = (int) $nota;
                        $notaVal = str_pad($notaInt, 2, '0', STR_PAD_LEFT);
                        $suma += $notaInt;
                        $conNota++;

                        // Lógica de conteo por unidad
                        if ($mat) {
                            if ($notaInt === 0) {
                                $totalesRetirados[$j]++;
                            } elseif ($notaInt >= 13) {
                                $totalesAprobados[$j]++;
                                $aprobadas++;
                            } else {
                                $totalesDesaprobados[$j]++;
                                $desaprobadas++;
                            }
                        }
                    }
                }
                $templateProcessor->setValue("u{$j}_{$i}", $notaVal);
            }
            // ==========================================
            // ASIGNAR NOTA EFSRT AL MARCADOR DEL WORD (${nota_efsrt1}, etc.)
            // ==========================================
            if ($efsrt && !empty($idsMatriculasDelAlumno)) {
                $queryEfsrt = Nota::whereIn('matricula_id', $idsMatriculasDelAlumno);
                // if ($esFormacionContinua) {
                //     $queryEfsrt->where('curso_id', $efsrt->id_curso);
                // } else {
                //     $queryEfsrt->where('unidad_id', $efsrt->id_unidad);
                // }
                  $queryEfsrt->where('unidad_id', $efsrt->id);
                $notaEfsrtModel = $queryEfsrt->first();
                if ($notaEfsrtModel && $notaEfsrtModel->nota_numerica !== null) {
                    $notaEfsrtVal = str_pad((int) $notaEfsrtModel->nota_numerica, 2, '0', STR_PAD_LEFT);
                }
            }

            // Inyectamos en las dos variantes de marcadores por si acaso tu Word usa con o sin guion bajo
            $templateProcessor->setValue("nota_efsrt{$i}", $notaEfsrtVal);
            // $templateProcessor->setValue("nota_efsrt_{$i}", $notaEfsrtVal);
            // Calcular el promedio numérico como un entero para las condiciones
            $promedioNum = $conNota > 0 ? (int) round($suma / $conNota) : null;
            $promedioFormateado = $promedioNum !== null ? str_pad($promedioNum, 2, '0', STR_PAD_LEFT) : '';
            $templateProcessor->setValue("logro_{$i}", $promedioFormateado);
            $templateProcessor->setValue("aprob_{$i}", $mat ? str_pad($aprobadas, 2, '0', STR_PAD_LEFT) : '');
            $templateProcessor->setValue("desap_{$i}", $mat ? str_pad($desaprobadas, 2, '0', STR_PAD_LEFT) : '');
            // Determinar la observación correctamente
            $observacion = '';
            if ($mat) {
                if ($promedioNum !== null) {
                    if ($promedioNum === 0) {
                        $observacion = 'Retirado';
                    } elseif ($promedioNum < 13) {
                        $observacion = 'Desaprobado';
                    }
                }
            }

            $templateProcessor->setValue("obs_{$i}", $observacion);
        }
        // ==========================================
        // MODIFICADO: LLENAR ESTADÍSTICAS SÓLO HASTA LAS UNIDADES REALES
        // ==========================================
        for ($j = 1; $j <= 10; $j++) {
            // Si la columna existe (está dentro del total de unidades del módulo), ponemos el número con ceros. 
            // Si está fuera de rango (ej: columnas 7 a 10 cuando el módulo solo tiene 6 unidades), mandamos vacío.
            if ($j <= $totalColumnas) {
                $templateProcessor->setValue("aprob_col_{$j}", str_pad($totalesAprobados[$j], 2, '0', STR_PAD_LEFT));
                $templateProcessor->setValue("desap_col_{$j}", str_pad($totalesDesaprobados[$j], 2, '0', STR_PAD_LEFT));
                $templateProcessor->setValue("ret_col_{$j}", str_pad($totalesRetirados[$j], 2, '0', STR_PAD_LEFT));
            } else {
                $templateProcessor->setValue("aprob_col_{$j}", '');
                $templateProcessor->setValue("desap_col_{$j}", '');
                $templateProcessor->setValue("ret_col_{$j}", '');
            }
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
