<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Evaluación Modular</title>
<style>
    @page { margin: 8mm 10mm; size: A4 landscape; }
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 6.5px; color: #000; margin: 0; padding: 0; }
    
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td, th { border: 0.8px solid #000; padding: 1px 2px; vertical-align: middle; text-align: center; }
    
    .bg-gray { background-color: #e0e0e0; }
    .font-bold { font-weight: bold; }
    .text-left { text-align: left; padding-left: 4px; }
    
    /* Layout Header principal */
    .top-header { border: none; margin-bottom: 2px; }
    .top-header td { border: none; }
    .title-cell { font-size: 11px; font-weight: bold; text-align: center; line-height: 1.3; }
    
    /* Celdas Anidadas sin bordes dobles */
    .no-border-wrapper { padding: 0 !important; border: none !important; vertical-align: top; }
    .inner-table { height: 165px; border-style: hidden; }
    .inner-table td { height: 20px; font-size: 6px; text-align: left; padding-left: 3px;}
    .inner-table .bg-gray { text-align: left; font-weight: bold; }

    /* Imagen Vertical SVG */
    .img-vertical {
        display: block;
        margin: 0 auto;
        height: 140px;
        width: 24px;
    }

    /* Filas de datos */
    .data-row td { height: 11.5px; font-size: 6.5px; }
    .nota-azul { color: #00008B; font-weight: bold; }
    
    /* Tablas inferiores */
    .bottom-tables td { font-size: 6.5px; padding: 3px; }
    .firma-box { width: 150px; border-top: 1px solid #000; margin: 0 auto; padding-top: 2px; text-align: center; font-weight: bold; }
</style>
</head>
<body>

    @php
        // TRUCO INFALIBLE PARA DOMPDF: Genera un SVG rotado y lo devuelve como imagen Base64
        $renderSvgText = function($texto) {
            if (!$texto) return '';
            $lineas = explode("\n", wordwrap(trim($texto), 24, "\n"));
            $totalLineas = count($lineas);
            
            $svg = '<svg viewBox="0 0 24 140" xmlns="http://www.w3.org/2000/svg">';
            foreach ($lineas as $index => $linea) {
                // Matematica para centrar lineas
                $x = 12 + ((($totalLineas - 1) / 2) - $index) * 7;
                $svg .= '<text transform="translate('.$x.', 135) rotate(-90)" font-family="Helvetica, sans-serif" font-size="6.5" font-weight="bold" fill="#000">'.htmlspecialchars(trim($linea)).'</text>';
            }
            $svg .= '</svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        };
    @endphp

    @for($page = 0; $page < 2; $page++)
        
        @if($page > 0)
            <div style="page-break-before: always;"></div>
        @endif

        {{-- TITULO Y LOGO --}}
        <table class="top-header">
            <tr>
                <td width="15%"><img src="{{ public_path('min_edu.png') }}" alt="Logo" width="100"></td>
                <td width="85%" class="title-cell">
                    ACTA DE EVALUACIÓN MODULAR<br>
                    EDUCACIÓN TÉCNICO PRODUCTIVA<br>
                    AÑO {{ $anio }} - PERIODO ACADÉMICO I
                </td>
            </tr>
        </table>

        {{-- MASTER GRID: Sin <col>, usando width estrictos en las celdas --}}
        <table>
            <thead>
                {{-- BLOQUE SUPERIOR DE INFORMACIÓN --}}
                <tr>
                    {{-- LADO IZQUIERDO (Datos CETPRO) --}}
                    <th colspan="3" class="no-border-wrapper" width="31%">
                        <table class="inner-table">
                            <tr><td class="bg-gray" width="30%">CETPRO</td><td colspan="3" class="text-center font-bold">{{ $cetpro }}</td></tr>
                            <tr><td class="bg-gray">Tipo de gestión</td><td width="20%">PRIVADA</td><td class="bg-gray" width="30%">Código modular</td><td width="20%"></td></tr>
                            <tr><td class="bg-gray">Resol. Autorización</td><td>{{ $resolucion_autorizacion }}</td><td class="bg-gray">Resol. Conversión</td><td>{{ $resolucion_conversion }}</td></tr>
                            <tr><td class="bg-gray">Lugar de servicio</td><td>Sede prin. <b>X</b> Filial </td><td class="bg-gray">Local</td><td>LA MOLINA</td></tr>
                            <tr><td class="bg-gray">DRE</td><td class="text-center">UGEL</td><td colspan="2" class="text-center">6</td></tr>
                            <tr><td class="bg-gray">Región</td><td>LIMA METROPOLITANA</td><td class="bg-gray">Provincia</td><td>LIMA</td></tr>
                            <tr><td class="bg-gray">Distrito</td><td>LIMA</td><td class="bg-gray">Lugar</td><td>LA MOLINA</td></tr>
                            <tr><td class="bg-gray">Dirección</td><td colspan="3">CALLE LOS MANGOS 145</td></tr>
                        </table>
                    </th>

                    {{-- CENTRO (Cabeceras Verticales SVG a Base64) --}}
                    <th colspan="12" class="no-border-wrapper" width="48%">
                        <table class="inner-table">
                            <tr><td colspan="12" class="bg-gray text-center font-bold" style="height: 20px; border-bottom: 0.5px solid #000;">UNIDADES DIDÁCTICAS</td></tr>
                            <tr class="bg-gray">
                                @foreach($unidadesPadded as $u)
                                    <td class="text-center" style="height: 145px; padding: 0; width: 8.33%; border-bottom: none;">
                                        @if($u) <img src="{{ $renderSvgText($u['nombre_unidad']) }}" class="img-vertical"> @endif
                                    </td>
                                @endforeach
                                <td class="text-center" style="height: 145px; padding: 0; width: 8.33%; border-bottom: none;">
                                    <img src="{{ $renderSvgText('EXPERIENCIAS FORMATIVAS EN SITUACIONES REALES DE TRABAJO') }}" class="img-vertical">
                                </td>
                                <td class="text-center" style="height: 145px; padding: 0; width: 8.33%; border-bottom: none;">
                                    <img src="{{ $renderSvgText('LOGRO DEL PERIODO ACADÉMICO') }}" class="img-vertical">
                                </td>
                                <td class="text-center" style="height: 145px; padding: 0; width: 8.33%; border-bottom: none;">
                                    <img src="{{ $renderSvgText('N° UNIDADES DIDÁCTICAS APROBADAS') }}" class="img-vertical">
                                </td>
                                <td class="text-center" style="height: 145px; padding: 0; width: 8.33%; border-bottom: none;">
                                    <img src="{{ $renderSvgText('N° UNIDADES DIDÁCTICAS DESAPROBADAS') }}" class="img-vertical">
                                </td>
                            </tr>
                        </table>
                    </th>

                    {{-- LADO DERECHO (Datos Programa) --}}
                    <th colspan="1" class="no-border-wrapper" width="21%">
                        <table class="inner-table">
                            <tr><td class="bg-gray">PROGRAMA DE ESTUDIOS:</td></tr>
                            <tr><td class="text-center font-bold" style="font-size: 5.5px;">{{ $programa_estudios }}</td></tr>
                            <tr><td class="bg-gray">CICLO: <span style="font-weight:normal; margin-left: 20px;">{{ $ciclo }}</span></td></tr>
                            <tr><td class="bg-gray">MÓDULO:</td></tr>
                            <tr><td class="text-center font-bold" style="font-size: 5.5px;">{{ $modulo }}</td></tr>
                            <tr><td class="bg-gray">RES. AUTORIZACIÓN DEL MÓDULO:</td></tr>
                            <tr><td class="bg-gray">SECCIÓN: <span style="font-weight:normal; margin-left: 20px;">ÚNICA</span></td></tr>
                            <tr><td class="bg-gray">TURNO: <span style="font-weight:normal; margin-left: 20px;">{{ $turno }}</span></td></tr>
                        </table>
                    </th>
                </tr>

                {{-- TÍTULOS DE COLUMNA CON ANCHOS EXACTOS --}}
                <tr class="bg-gray font-bold">
                    <th width="2.5%">N.°</th>
                    <th width="6.5%">Código Mat.</th>
                    <th width="22%">APELLIDOS Y NOMBRES<br><span style="font-weight:normal; font-size: 5px;">(orden alfabético)</span></th>
                    @foreach($unidadesPadded as $u) <th width="3.2%"></th> @endforeach
                    <th width="4%"></th>
                    <th width="3.5%"></th>
                    <th width="3.5%"></th>
                    <th width="3.5%"></th>
                    <th width="21%">OBSERVACIONES</th>
                </tr>
                
                {{-- SUBTITULOS DE COLUMNA --}}
                <tr class="bg-gray font-bold">
                    <th colspan="3" style="background-color: #fff; border-top: none;"></th>
                    @foreach($unidadesPadded as $u)
                        <th>{{ $u ? ($u['creditos'] ?? '0').'/'.($u['horas'] ?? '0') : '' }}</th>
                    @endforeach
                    <th>6/192</th>
                    <th></th><th></th><th></th>
                    <th style="border-top: none;">TOTAL CRÉDITOS / HORAS: <span style="font-weight:normal;">{{ $total_creditos_horas }}</span></th>
                </tr>
            </thead>

            <tbody>
                {{-- BUCLE DE ESTUDIANTES (20 por página) --}}
                @php $startIndex = $page * 20; @endphp
                @for($i = $startIndex; $i < $startIndex + 20; $i++)
                    @php $alumno = $alumnos[$i] ?? null; @endphp
                    <tr class="data-row">
                        <td>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $alumno['codigo_matricula'] ?? '' }}</td>
                        <td class="text-left font-bold">{{ $alumno['apellidos_nombres'] ?? '' }}</td>
                        
                        @for($j = 0; $j < 8; $j++)
                            <td class="nota-azul">{{ $alumno['notas'][$j] ?? '' }}</td>
                        @endfor
                        <td>{{ $alumno ? '00' : '' }}</td>
                        
                        <td class="nota-azul">{{ $alumno['logro_periodo'] ?? '' }}</td>
                        <td>{{ $alumno['aprobadas'] ?? '' }}</td>
                        <td>{{ $alumno['desaprobadas'] ?? '' }}</td>
                        <td></td>
                    </tr>
                @endfor
                
                {{-- ESTADISTICAS AL FINAL DE LA PÁGINA 2 --}}
                @if($page == 1)
                    <tr class="data-row font-bold">
                        <td colspan="3" class="bg-gray text-right" style="padding-right: 5px;">APROBADOS</td>
                        @foreach($stat_aprobados as $val) <td>{{ $val }}</td> @endforeach
                        <td style="border: none;"></td>
                    </tr>
                    <tr class="data-row font-bold">
                        <td colspan="3" class="bg-gray text-right" style="padding-right: 5px;">DESAPROBADOS</td>
                        @foreach($stat_desaprobados as $val) <td>{{ $val }}</td> @endforeach
                        <td style="border: none;"></td>
                    </tr>
                    <tr class="data-row font-bold">
                        <td colspan="3" class="bg-gray text-right" style="padding-right: 5px;">RETIRADOS</td>
                        @foreach($stat_retirados as $val) <td>{{ $val }}</td> @endforeach
                        <td style="border: none;"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if($page == 1)
            <div style="font-weight:bold; font-size: 6px; margin-top: 5px; text-align: center;">
                ESTADÍSTICA POR UNIDAD DIDÁCTICA DEL TOTAL DE ALUMNOS REGISTRADOS
            </div>

            <table class="bottom-tables" style="margin-top: 4px;">
                <tr><td colspan="2" class="bg-gray font-bold text-center">UNIDAD(ES) DE COMPETENCIA</td></tr>
                <tr class="bg-gray font-bold text-center">
                    <td width="30%">UNIDAD DIDÁCTICA</td>
                    <td width="70%">CAPACIDAD</td>
                </tr>
                @foreach($unidadesReales as $u)
                    <tr>
                        <td class="font-bold">{{ $u->nombre_unidad }}</td>
                        <td class="text-left" style="text-align: justify; padding: 2px 4px;">
                            {{ $u->capacidad ?? 'Capacidad definida en la currícula del programa de estudios.' }}
                        </td>
                    </tr>
                @endforeach
            </table>

            <table style="width: 100%; border: none; margin-top: 15px;" class="bottom-tables">
                <tr>
                    <td style="border: none; text-align: left;" width="33%">LA MOLINA, {{ now()->format('d - m - Y') }}</td>
                    <td style="border: none;" width="34%"></td>
                    <td style="border: none;" width="33%">
                        <div class="firma-box">DIRECCIÓN</div>
                    </td>
                </tr>
                <tr>
                    <td style="border: none; padding-top: 25px;" colspan="3">
                        <div class="firma-box" style="width: 250px;">
                            DOCENTE: {{ $docente }}
                        </div>
                    </td>
                </tr>
            </table>
        @endif
        
    @endfor

</body>
</html>