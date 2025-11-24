<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información de Sección</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            font-size: 14px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .tipo-programa {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .nombre-programa {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        
        .nombre-especialidad {
            font-size: 16px;
            color: #555;
            margin-top: 10px;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-item {
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .info-label {
            font-weight: normal;
        }
        
        .cursos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #333;
        }
        
        .cursos-table th {
            background-color: #f5f5f5;
            border: 1px solid #333;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .cursos-table td {
            border: 1px solid #333;
            padding: 10px;
            text-align: center;
            width: 20%;
        }
        
        .cursos-table td:first-child, .cursos-table td:last-child{
            width: 10%;
        }
        
        .cursos-table th:first-child {
            text-align: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="tipo-programa">
            {{ $seccion->programa->tipo_programa?->getLabel() ?? 'Tipo de programa' }}
        </div>
        <div class="nombre-programa">
            {{ $seccion->programa->nombre_programa ?? 'Nombre del programa' }}
        </div>
        <div class="nombre-especialidad">
            {{ $seccion->programa->especialidad->nombre_especialidad ?? 'Nombre de la especialidad' }}
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-column">
            <div class="info-item">
                <span class="info-label">Profesor: </span>{{ $seccion->docente->nombre_completo ?? '-' }}
            </div>
            <div class="info-item">
                <span class="info-label">Turno: </span>{{ $seccion->turno?->getLabel() ?? '-' }}
            </div>
            <div class="info-item">
                <span class="info-label">Horario: </span>{{ $seccion->horario ?? '-' }}
            </div>
            <div class="info-item">
                <span class="info-label">Días: </span>{{ is_array($seccion->dias) ? implode(', ', $seccion->dias) : $seccion->dias }}
            </div>
        </div>
        <div class="info-column">
            <div class="info-item">
                <span class="info-label">Duración: </span>{{ $seccion->programa->duracion ?? '-' }} meses
            </div>
            <div class="info-item">
                <span class="info-label">Coste: S/. </span>{{ number_format($seccion->programa->especialidad->costo_mensual ?? 0, 2) }}
            </div>
        </div>
    </div>
    
    <table class="cursos-table">
        <thead>
            <tr>
                <th></th>
                <th>Curso</th>
                <th>Inicio</th>
                <th>Final</th>
                <th>Aula</th>
            </tr>
        </thead>
        <tbody>
            @php
                $cursos = $seccion->programa->cursos ?? collect();
            @endphp
            
            @forelse($cursos as $index => $curso)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $curso->nombre_curso }}</td>
                    <td>{{ $curso->fecha_inicio ? \Carbon\Carbon::parse($curso->fecha_inicio)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $curso->fecha_termino ? \Carbon\Carbon::parse($curso->fecha_termino)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $seccion->aula ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="row-number">1</td>
                    <td colspan="4">No hay cursos registrados</td>
                </tr>
                <tr>
                    <td class="row-number">2</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td class="row-number">3</td>
                    <td colspan="4"></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        Certificado a nombre de La Nación
    </div>
</body>
</html>
