<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Horario;
use App\Enums\EstadoMatricula;
use App\Enums\TipoGenero;
use App\Enums\LenguaMaterna;
use App\Enums\Turno;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\File;

class ReporteCensoController extends Controller
{
    public function descargar($anio)
    {
        // 1. OBTENER ESTUDIANTES ÚNICOS DEL AÑO
        $matriculas = Matricula::with('estudiante')
            ->where('id', '!=', 42)
            ->where('codigo_inscripcion', 'like', $anio . '%')
            ->whereIn('estado', [EstadoMatricula::ENPROCESO->value, EstadoMatricula::CULMINADO->value])
            ->get()
            ->unique('estudiante_id') // 👈 CERO DUPLICADOS (1 persona = 1 conteo)
            ->values();

        // 2. INICIALIZAR CONTADORES DE EDAD
        $edades = [
            '14' => ['H' => 0, 'M' => 0], '15' => ['H' => 0, 'M' => 0],
            '16' => ['H' => 0, 'M' => 0], '17' => ['H' => 0, 'M' => 0],
            '18' => ['H' => 0, 'M' => 0], '19' => ['H' => 0, 'M' => 0],
            '20' => ['H' => 0, 'M' => 0], '21' => ['H' => 0, 'M' => 0],
            '22' => ['H' => 0, 'M' => 0], '23' => ['H' => 0, 'M' => 0],
            '24' => ['H' => 0, 'M' => 0],
            '25_29' => ['H' => 0, 'M' => 0], '30_34' => ['H' => 0, 'M' => 0],
            '35_39' => ['H' => 0, 'M' => 0], '40_44' => ['H' => 0, 'M' => 0],
            '45_49' => ['H' => 0, 'M' => 0], '50_54' => ['H' => 0, 'M' => 0],
            '55_59' => ['H' => 0, 'M' => 0], '60_mas' => ['H' => 0, 'M' => 0],
        ];

        // 3. INICIALIZAR CONTADORES DE NIVEL EDUCATIVO
        $niveles = [
            'sin' => ['H' => 0, 'M' => 0], // Sin Nivel
            'pi'  => ['H' => 0, 'M' => 0], // Primaria Incompleta
            'pc'  => ['H' => 0, 'M' => 0], // Primaria Completa
            'si'  => ['H' => 0, 'M' => 0], // Secundaria Incompleta
            'sc'  => ['H' => 0, 'M' => 0], // Secundaria Completa
            'sui' => ['H' => 0, 'M' => 0], // Superior Incompleta
            'suc' => ['H' => 0, 'M' => 0], // Superior Completa
        ];

        // 4. INICIALIZAR CONTADORES DE LENGUA MATERNA
        $lenguas = [
            'castellano' => ['H' => 0, 'M' => 0],
            'quechua'    => ['H' => 0, 'M' => 0],
            'aimara'     => ['H' => 0, 'M' => 0],
            'ashaninka'  => ['H' => 0, 'M' => 0],
            'otra_orig'  => ['H' => 0, 'M' => 0],
            'extranjera' => ['H' => 0, 'M' => 0],
            'senas'      => ['H' => 0, 'M' => 0],
        ];

        // 5. INICIALIZAR CONTADORES AÑO DE EGRESO EBR
        $egreso = [
            '2025'   => ['H' => 0, 'M' => 0],
            '2024'   => ['H' => 0, 'M' => 0],
            '2023'   => ['H' => 0, 'M' => 0],
            '2022'   => ['H' => 0, 'M' => 0],
            '2021'   => ['H' => 0, 'M' => 0],
            '2020'   => ['H' => 0, 'M' => 0],
            '2019'   => ['H' => 0, 'M' => 0],
            '2018'   => ['H' => 0, 'M' => 0],
            '2017'   => ['H' => 0, 'M' => 0],
            'pre_2017' => ['H' => 0, 'M' => 0],
        ];

        // 6. INICIALIZAR CONTADORES DE SECCIONES
        $secciones = [
            'manana' => ['aux' => 0, 'tec' => 0],
            'tarde'  => ['aux' => 0, 'tec' => 0],
            'noche'  => ['aux' => 0, 'tec' => 0],
        ];

        // Fecha de corte MINEDU: 31 de marzo del año seleccionado
        $fechaCorte = Carbon::createFromDate($anio, 3, 31);

        // ===============================================
        // PROCESAMIENTO DE ESTUDIANTES
        // ===============================================
        foreach ($matriculas as $mat) {
            $est = $mat->estudiante;
            if (!$est) continue;

            // Determinar Sexo (H o M)
            $generoStr = is_object($est->genero) ? $est->genero->value : $est->genero;
            $sexo = ($generoStr === TipoGenero::MASCULINO->value || $generoStr === 'Masculino') ? 'H' : 'M';

            // --- CÁLCULO DE EDAD AL 31 DE MARZO ---
            if ($est->fecha_nacimiento) {
                // Obtenemos los años cumplidos como número entero puro
                $edadFloat = Carbon::parse($est->fecha_nacimiento)->floatDiffInYears($fechaCorte);
                
                // Si la fecha de corte es menor a su nacimiento, tendrá edad negativa. Evitamos eso:
                $edad = $edadFloat > 0 ? (int) floor($edadFloat) : 0;
                
                // Mapeo de Edad estricto a las llaves del array
                if ($edad <= 14) {
                    $edades['14'][$sexo]++;
                } elseif ($edad >= 15 && $edad <= 24) {
                    $edades[(string)$edad][$sexo]++; 
                } elseif ($edad >= 25 && $edad <= 29) {
                    $edades['25_29'][$sexo]++;
                } elseif ($edad >= 30 && $edad <= 34) {
                    $edades['30_34'][$sexo]++;
                } elseif ($edad >= 35 && $edad <= 39) {
                    $edades['35_39'][$sexo]++;
                } elseif ($edad >= 40 && $edad <= 44) {
                    $edades['40_44'][$sexo]++;
                } elseif ($edad >= 45 && $edad <= 49) {
                    $edades['45_49'][$sexo]++;
                } elseif ($edad >= 50 && $edad <= 54) {
                    $edades['50_54'][$sexo]++;
                } elseif ($edad >= 55 && $edad <= 59) {
                    $edades['55_59'][$sexo]++;
                } else {
                    $edades['60_mas'][$sexo]++;
                }
            }

            // --- MAPEADO DE NIVEL EDUCATIVO ---
            $grado = strtolower(trim($est->grado_instruccion->value ?? ''));

            match (true) {
                str_contains($grado, 'sin nivel') => $niveles['sin'][$sexo]++,
                str_contains($grado, 'primaria incompleta') => $niveles['pi'][$sexo]++,
                str_contains($grado, 'primaria completa') => $niveles['pc'][$sexo]++,
                str_contains($grado, 'secundaria incompleta') => $niveles['si'][$sexo]++,
                str_contains($grado, 'secundaria completa') => $niveles['sc'][$sexo]++,
                str_contains($grado, 'superior incompleta') || str_contains($grado, 'tecnico incompleto') => $niveles['sui'][$sexo]++,
                str_contains($grado, 'superior completa') || str_contains($grado, 'tecnico completo') => $niveles['suc'][$sexo]++,
                default => null,
            };

            // --- MAPEADO DE LENGUA MATERNA ---
            $lengua = is_object($est->lengua_materna) ? $est->lengua_materna->value : $est->lengua_materna;
            
            if ($lengua) {
                match ($lengua) {
                    LenguaMaterna::CASTELLANO->value => $lenguas['castellano'][$sexo]++,
                    LenguaMaterna::QUECHUA->value => $lenguas['quechua'][$sexo]++,
                    LenguaMaterna::AIMARA->value => $lenguas['aimara'][$sexo]++,
                    LenguaMaterna::ASHANINKA->value => $lenguas['ashaninka'][$sexo]++,
                    LenguaMaterna::OTRA_LENGUA_ORIGINARIA->value => $lenguas['otra_orig'][$sexo]++,
                    LenguaMaterna::LENGUA_EXTRANJERA->value => $lenguas['extranjera'][$sexo]++,
                    LenguaMaterna::LENGUA_SENAS_PERUANA->value => $lenguas['senas'][$sexo]++,
                    default => null,
                };
            }

            // --- MAPEADO DE AÑO DE EGRESO EBR ---
            if ($est->anio_egreso_ebr) {
                $anioEgreso = (int) trim($est->anio_egreso_ebr);
                if ($anioEgreso >= 2017 && $anioEgreso <= 2025) {
                    $egreso[(string)$anioEgreso][$sexo]++;
                } elseif ($anioEgreso > 0 && $anioEgreso < 2017) {
                    $egreso['pre_2017'][$sexo]++;
                }
            }
        }

        // ===============================================
        // PROCESAMIENTO DE SECCIONES/HORARIOS
        // ===============================================
        $horariosActivos = Horario::with('programa')
            ->whereHas('programa', function ($q) {
                $q->where('activo', true);
            })
            ->get();

        foreach ($horariosActivos as $horarioActivo) {
            if (!$horarioActivo->programa) continue;

            $turnoStr = is_object($horarioActivo->turno) ? $horarioActivo->turno->value : $horarioActivo->turno;
            
            $keyTurno = match($turnoStr) {
                Turno::MAÑANA->value, 'Mañana' => 'manana',
                Turno::TARDE->value, 'Tarde' => 'tarde',
                Turno::NOCHE->value, 'Noche' => 'noche',
                default => null,
            };

            if (!$keyTurno) continue;

            $tipoProg = is_object($horarioActivo->programa->tipo_programa) 
                ? $horarioActivo->programa->tipo_programa->value 
                : $horarioActivo->programa->tipo_programa;
            
            if ( $tipoProg === \App\Enums\TipoPrograma::PROGRAMA_ESTUDIO->value || $tipoProg === \App\Enums\TipoPrograma::FORMACION_CONTINUA->value) {
                $secciones[$keyTurno]['aux']++;
            }
        }

        // ===============================================
        // EXPORTACIÓN A EXCEL: REEMPLAZO EXACTO POR BUCLE
        // ===============================================
        $templatePath = public_path('plantillas/censo.xlsx');
        if (!File::exists($templatePath)) abort(404, "Falta la plantilla censo.xlsx");

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $reemplazos = [];

        foreach ($edades as $key => $val) {
            $reemplazos["{e_{$key}_h}"] = $val['H'];
            $reemplazos["{e_{$key}_m}"] = $val['M'];
        }
        foreach ($niveles as $key => $val) {
            $reemplazos["{niv_{$key}_h}"] = $val['H'];
            $reemplazos["{niv_{$key}_m}"] = $val['M'];
        }
        foreach ($lenguas as $key => $val) {
            $reemplazos["{len_{$key}_h}"] = $val['H'];
            $reemplazos["{len_{$key}_m}"] = $val['M'];
        }
        foreach ($egreso as $key => $val) {
            $reemplazos["{egr_{$key}_h}"] = $val['H'];
            $reemplazos["{egr_{$key}_m}"] = $val['M'];
        }

        // Llenar array de reemplazos para SECCIONES (Modificado a sufijos cortos)
        foreach ($secciones as $turno => $val) {
            $prefijo = match($turno) {
                'manana' => 'man',
                'tarde'  => 'tar',
                'noche'  => 'noc',
            };
            
            $reemplazos["{sec_{$prefijo}_aux}"] = $val['aux'];
            $reemplazos["{sec_{$prefijo}_tec}"] = $val['tec'];
        }

        // NUEVO MÉTODO DE REEMPLAZO 100% EFECTIVO PARA EXCEL:
        // Buscamos celda por celda de forma plana en todo el libro.
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col !== $highestColumn; $col++) {
                $cell = $sheet->getCell($col . $row);
                $valorCelda = $cell->getValue();

                if (is_string($valorCelda) && str_contains($valorCelda, '{')) {
                    // Limpiamos los espacios en blanco que Excel a veces pone ocultos
                    $llaveLimpia = trim($valorCelda); 
                    
                    if (isset($reemplazos[$llaveLimpia])) {
                        $cell->setValue($reemplazos[$llaveLimpia]);
                    }
                }
            }
        }

        // GUARDAR Y DESCARGAR
        $tempPath = storage_path('app/temp');
        File::ensureDirectoryExists($tempPath);
        $fileName = 'Censo_Estadistico_' . $anio . '_' . time() . '.xlsx';
        $excelPath = $tempPath . DIRECTORY_SEPARATOR . $fileName;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($excelPath);

        return response()->download($excelPath, $fileName)->deleteFileAfterSend(true);
    }
}