<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Nómina de Matrícula {{ $anio }}</title>
    <style>
        /* Ajustamos los márgenes para usar casi toda la hoja A4 Vertical */
        @page {
            margin: 13mm 13mm 19mm 15mm;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 7.5px;
            /* Reducido para que todo encaje */
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Utilidades Generales */
        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        td {
            background: white;
        }

        .bg-gray {
            background-color: #d9d9d9;
        }

        /* Esto hace que la tabla suba 1px y se solape con el borde de la anterior */
        table+table {
            margin-top: -1px !important;
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse !important;
            /* Añadido !important aquí */
            margin-bottom: 0;
            table-layout: fixed;
        }

        /* .table-bordered {
            border-collapse: collapse !important;
        } */

        .table-bordered th,
        .table-bordered td {
            border: 0.5px solid #000 !important;
            padding: 1px 3px !important;
            vertical-align: middle !important;
        }

        .table-no-border td {
            border: none;
            padding: 1px;
            vertical-align: middle;
        }

        /* Encabezado Principal */
        .header-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header-subtitle {
            font-size: 11px;
            font-weight: bold;
            margin: 3px 0 6px 0;
        }

        /* Firmas */
        .signature-box {
            width: 180px;
            border-top: 1px solid #000;
            text-align: center;
            margin: 0 auto;
            padding-top: 3px;
            font-weight: bold;
            font-size: 8px;
        }

        /* Alturas estrictas para matemática de la Línea Z */
        .fila-alumno {
            height: 11.5px;
        }

        .cabecera-tabla {
            height: 20px;
        }

        .table-container {
            position: relative;
            width: 100%;
        }

        .empty-row td {
            color: transparent;
        }
    </style>
</head>

<body>

    @php
        // Identificar si es Formación Continua o Programa
        $esFormacionContinua = false;
        if ($horario->programa && $horario->programa->tipo_programa) {
            $tipo = is_object($horario->programa->tipo_programa)
                ? $horario->programa->tipo_programa->value
                : $horario->programa->tipo_programa;
            $esFormacionContinua = $tipo === App\Enums\TipoMatricula::FORMACION_CONTINUA->value;
        }

        // Nombres dinámicos
        // $labelPrograma = $esFormacionContinua ? 'FORMACIÓN CONTINUA' : 'PROGRAMA DE ESTUDIOS';
        $labelPrograma = 'PROGRAMA DE ESTUDIOS';
        // $labelModulo = $esFormacionContinua ? 'CURSO' : 'MÓDULO';
        $labelModulo = 'MÓDULO';
        $nombrePrograma = $esFormacionContinua ? 'FORMACIÓN CONTINUA' : $horario->programa->nombre_programa;
        $displayModulo = $esFormacionContinua ? ($horario->programa->nombre_programa ?? '-'): '';
        $ciclo = $esFormacionContinua ? '' : 'AUXILIAR TÉCNICO';

        // Fechas de inicio y fin
        $fechaInicio = '-';
        $fechaFin = '-';
        // MODIFICACIÓN: Extraemos las fechas exclusivas del módulo seleccionado
        if (isset($curso_id) && $curso_id) {
            $cursoSeleccionado = $horario->programa->cursos->where('id_curso', $curso_id)->first();
            if ($cursoSeleccionado) {
                $fechaInicio = $cursoSeleccionado->fecha_inicio
                    ? \Carbon\Carbon::parse($cursoSeleccionado->fecha_inicio)->format('d/m/Y')
                    : '-';
                $fechaFin = $cursoSeleccionado->fecha_termino
                    ? \Carbon\Carbon::parse($cursoSeleccionado->fecha_termino)->format('d/m/Y')
                    : '-';
                $displayModulo = $cursoSeleccionado->nombre_curso; // Reemplaza para mostrar el nombre del módulo exacto
            }
        }
        // Fallback: Si no hay curso_id, calcula el rango de todo el programa
        elseif ($horario->programa->cursos->isNotEmpty()) {
            $primerCurso = $horario->programa->cursos->sortBy('fecha_inicio')->first();
            $ultimoCurso = $horario->programa->cursos->sortByDesc('fecha_termino')->first();
            $fechaInicio = $primerCurso->fecha_inicio
                ? \Carbon\Carbon::parse($primerCurso->fecha_inicio)->format('d/m/Y')
                : '-';
            $fechaFin = $ultimoCurso->fecha_termino
                ? \Carbon\Carbon::parse($ultimoCurso->fecha_termino)->format('d/m/Y')
                : '-';
        }

        // Datos para la línea Z
        $totalAlumnos = count($alumnos);
        $alumnosPad = array_pad($alumnos->toArray(), 40, null);
    @endphp

    {{-- ========================================== --}}
    {{-- LOGOS Y TÍTULO --}}
    {{-- ========================================== --}}
    <table class="table-no-border" style="margin-bottom: 5px;">
        <tr>
            <td width="20%" class="text-center">
                <img src="{{ public_path('min_edu.png') }}" width="90" alt="Escudo">
                {{-- <div style="font-size: 6px; font-weight: bold; margin-top: 2px;">MINISTERIO DE EDUCACIÓN</div> --}}
            </td>
            <td width="80%" class="text-center">
                <h1 class="header-title">NÓMINA DE MATRÍCULA {{ $anio }}</h1>
                <h2 class="header-subtitle">EDUCACIÓN TÉCNICO PRODUCTIVA</h2>
            </td>
        </tr>
    </table>

    <div class="text-center font-bold" style="font-size: 9px; margin-bottom: 3px;">
        DATOS DEL CENTRO DE EDUCACIÓN TÉCNICO PRODUCTIVA
    </div>

    {{-- DATOS INSTITUCIONALES --}}
    <table class="table-bordered">
        <tr>
            <td width="6%" class="bg-gray font-bold">REGIÓN</td>
            <td width="22%" class="text-center font-bold">LIMA METROPOLITANA</td>
            <td width="5%" class="bg-gray font-bold">UGEL</td>
            <td width="19%" class="text-center font-bold">6</td>
            <td width="7%" class="bg-gray font-bold">CETPRO</td>
            <td width="41%" class="text-center font-bold">LA MOLINA</td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none !important;">
        <tr>
            <td width="16%" class="bg-gray font-bold">GESTIÓN PÚBLICA</td>
            <td width="12%"></td>
            <td width="12%" class="bg-gray font-bold">GESTIÓN PRIVADA</td>
            <td width="12%" class="text-center font-bold">X</td>
            <td width="7%" class="bg-gray font-bold">CONVENIO</td>
            <td width="41%"></td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none !important;">
        <tr>
            <td width="18%" class="bg-gray font-bold">RESOLUCIÓN DE CREACIÓN</td>
            <td width="17%" class="text-center font-bold">RDR Nº 01072-2002-USE Nº 06</td>
            <td width="24%" class="bg-gray font-bold">RESOLUCIÓN DIRECTORAL DEL MÓDULO</td>
            <td width="41%" class="text-center font-bold">RD. N° 06335 - 2024</td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none !important;">
        <tr>
            <td width="16%" class="bg-gray font-bold">PROVINCIA</td>
            <td width="25%" class="text-center font-bold">LIMA</td>
            <td width="11%" class="bg-gray font-bold">DISTRITO</td>
            <td width="48%" class="text-center font-bold">LA MOLINA</td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none">
        <tr>
            <td width="6%" class="bg-gray font-bold">LUGAR</td>
            <td width="35%" class="text-center font-bold">LA MOLINA</td>
            <td width="11%" class="bg-gray font-bold">DIRECCIÓN</td>
            <td width="48%" class="text-center font-bold">CALLE LOS MANGOS 145</td>
        </tr>
    </table>

    {{-- DATOS DEL PROGRAMA --}}
    <table class="table-bordered">
        <tr>
            <td width="28%" class="bg-gray font-bold">{{ $labelPrograma }}</td>
            <td width="72%" colspan="3" class="text-center font-bold">{{ mb_strtoupper($nombrePrograma) }}</td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none;">
        <tr>
            <td width="6%" class="bg-gray font-bold">{{ $labelModulo }}</td>
            <td width="74%" class="text-center font-bold">{{ mb_strtoupper($displayModulo) }}</td>
            <td width="10%" class="bg-gray font-bold text-center">CICLO</td>
            <td width="10%" class="text-center font-bold">{{ $ciclo }}</td>
        </tr>
    </table>
    <table class="table-bordered" style="border-top:none;">
        <tr>
            <td width="16%" class="bg-gray font-bold">FECHA DE INICIO</td>
            <td width="12%" class="text-center font-bold">{{ $fechaInicio }}</td>
            <td width="12%" class="bg-gray font-bold">FECHA DE TERMINO</td>
            <td width="15%" class="text-center font-bold">{{ $fechaFin }}</td>
            <td width="10%" class="bg-gray font-bold text-center">TURNO</td>
            <td width="15%" class="text-center font-bold">{{ mb_strtoupper($horario->turno?->value ?? 'TARDE') }}
            </td>
            <td width="10%" class="bg-gray font-bold text-center">SECCIÓN</td>
            <td width="10%" class="text-center font-bold">ÚNICA</td>
        </tr>
    </table>

    {{-- ========================================== --}}
    {{-- TABLA ESTUDIANTES (FILAS 1 AL 40 UNIFICADAS) --}}
    {{-- ========================================== --}}
    <div class="table-container">
        <table class="table-bordered" style="margin-top: -1px;">
            <thead>
                <tr class="bg-gray font-bold text-center cabecera-tabla">
                    <th width="4%">Nº<br>Ord.</th>
                    <th width="12%">Código de<br>Matrícula</th>
                    <th width="64%">APELLIDOS Y NOMBRES<br><span style="font-weight:normal; font-size:6px;">(Orden
                            Alfabético)</span></th>
                    <th width="5%">SEXO<br><span style="font-size:6px;">H - M</span></th>
                    <th width="5%">EDAD</th>
                    <th width="10%">Condición<br><span style="font-weight:normal; font-size:6px;">(G - P -
                            B)</span></th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 40; $i++)
                    @php $alumno = $alumnosPad[$i]; @endphp
                    <tr class="fila-alumno {{ $alumno ? '' : 'empty-row' }}">
                        <td class="text-center">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="text-center">{{ $alumno['codigo'] ?? '' }}</td>
                        <td style="padding-left: 5px;">{{ $alumno['apellidos_nombres'] ?? '' }}</td>
                        <td class="text-center">{{ $alumno['sexo'] ?? '' }}</td>
                        <td class="text-center">{{ $alumno['edad'] ?? '' }}</td>
                        <td class="text-center">{{ $alumno['condicion'] ?? '' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        {{-- LÍNEA Z DE ANULACIÓN (OVERLAY SVG CORREGIDO PARA DOMPDF) --}}
        {{-- @if ($totalAlumnos < 40)
            @php
                // Matemática de posición:
                // La cabecera mide 24px aprox. Cada fila mide 11.5px.
                // El borde superior arranca donde termina el último alumno con datos.
                $offsetY = 24 + ($totalAlumnos * 11.5); 
                $alturaSVG = (40 - $totalAlumnos) * 11.5; 
            @endphp
             --}}
        {{-- DIV para la LÍNEA RECTA HORIZONTAL (Cubre desde la columna SEXO hacia la derecha) --}}
        {{-- <div style="position: absolute; top: {{ $offsetY }}px; left: 80%; width: 20%; height: 1px; border-top: 1px solid #000; z-index: 10;"></div> --}}

        {{-- DIV para la LÍNEA DIAGONAL (Cubre toda el área vacía desde APELLIDOS Y NOMBRES hasta la derecha) --}}
        {{-- <div style="position: absolute; top: {{ $offsetY }}px; left: 16%; width: 84%; height: {{ $alturaSVG }}px; z-index: 9;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                    <!-- Línea Diagonal desde la esquina superior izquierda de esta caja hasta la esquina inferior derecha -->
                    <line x1="0" y1="0" x2="100" y2="100" stroke="black" stroke-width="0.5"/>
                </svg>
            </div>
        @endif --}}
    </div>

    {{-- ========================================== --}}
    {{-- CUADRO DE RESUMEN Y FIRMAS                 --}}
    {{-- ========================================== --}}
    <div class="text-center font-bold" style="margin-top: 6px; margin-bottom: 2px; font-size: 9px;">RESUMEN</div>

    <table class="table-no-border" style="width: 100%; margin-bottom: 15px; border-spacing: 0;">
        <tr>
            <td width="16%"></td>
            <td width="24%" style="padding: 0;"> {{-- Quitamos padding al td contenedor --}}
                <table class="table-bordered text-center font-bold" style="width: 100%;">
                    <tr class="bg-gray">
                        <td class="bg-gray">Hombres</td>
                        <td class="bg-gray">Mujeres</td>
                        <td class="bg-gray">TOTAL</td>
                    </tr>
                    <tr>
                        <td>{{ str_pad($resumen['hombres'], 2, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ str_pad($resumen['mujeres'], 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="bg-gray">{{ str_pad($resumen['total'], 2, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                </table>
            </td>
            <td width="5%"></td>
            <td width="45%" style="padding: 0;"> {{-- Quitamos padding al td contenedor --}}
                <table class="table-bordered text-center font-bold" style="width: 100%;">
                    <tr class="bg-gray">
                        <td class="bg-gray">Gratuitos</td>
                        <td class="bg-gray">Pagantes</td>
                        <td class="bg-gray">Becarios</td>
                        <td class="bg-gray">TOTAL</td>
                    </tr>
                    <tr>
                        <td>00</td>
                        <td>{{ str_pad($resumen['pagantes'], 2, '0', STR_PAD_LEFT) }}</td>
                        <td>00</td>
                        <td class="bg-gray">{{ str_pad($resumen['total'], 2, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                </table>
            </td>
            <td width="10%"></td>
        </tr>
    </table>

    {{-- FECHA Y DOCENTE --}}
    <table class="table-no-border" style="width: 100%; margin-bottom: 130px;">
        <tr>
            <td width="8%" class="font-bold">Fecha:</td>
            <td width="92%">La Molina, {{ now()->format('d - m - Y') }}</td>
        </tr>
        <tr>
            <td class="font-bold">Docente:</td>
            <td>{{ $horario->docente ? \Illuminate\Support\Str::upper("{$horario->docente->apellido_paterno} {$horario->docente->apellido_materno}, ") . trim(\Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($horario->docente->nombres))) : '' }}
            </td>
        </tr>
    </table>

    {{-- FIRMAS --}}
    <table class="table-no-border" style="width: 100%;">
        <tr>
            <td width="50%" class="text-center">
                <div class="signature-box">
                    PROFESOR (A)<br>
                    <span style="font-weight:normal; font-size:7px;">(Firma, sello, pos firma)</span>
                </div>
            </td>
            <td width="50%" class="text-center">
                <div class="signature-box">
                    DIRECTOR(A)<br>
                    <span style="font-weight:normal; font-size:7px;">(Firma, sello, pos firma)</span>
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
