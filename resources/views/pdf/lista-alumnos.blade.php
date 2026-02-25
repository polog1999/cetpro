<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alumnos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            font-size: 12px;
            color: #333;
        }
        
        .header {
            text-align: left;
            margin-bottom: 5px;
        }
        
        .titulo {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .programa-info {
            text-align: center;
            margin-bottom: 5px;
        }
        
        .nombre-programa {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .dias-estudio {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .alumnos-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #333;
        }
        
        .alumnos-table th {
            background-color: #f5f5f5;
            border: 1px solid #333;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }
        
        .alumnos-table td {
            border: 1px solid #333;
            padding: 12px 8px;
            text-align: left;
            min-height: 50px;
        }
        
        .alumnos-table td:first-child {
            text-align: center;
            width: 40px;
        }
        
        .alumnos-table td.nombre-col {
            width: 25%;
        }
        
        .alumnos-table td.apellidos-col {
            width: 30%;
        }
        
        .alumnos-table td.dni-col {
            width: 20%;
            text-align: center;
        }
        
        .alumnos-table td.celular-col {
            width: 20%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="titulo">Lista de alumnos</div>
    </div>
    
    <div class="programa-info">
        <div class="nombre-programa">
            {{ $horario->programa->nombre_programa ?? 'Sin programa' }}
        </div>
    </div>
    
    <div class="dias-estudio">
        Días de estudio: {{ $dias_estudio }} <br>
        Horas: {{ $horario->hora_inicio && $horario->hora_fin ? $horario->hora_inicio->format('H:i') . ' - ' . $horario->hora_fin->format('H:i') : '-' }} <br>
        Profesor: {{ $horario->docente->nombre_completo ?? '-' }}
    </div>
    
    <table class="alumnos-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>DNI</th>
                <th>Celular</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumnos as $index => $alumno)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nombre-col">{{ $alumno->nombres ?? '' }}</td>
                    <td class="apellidos-col">{{ trim(($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '')) }}</td>
                    <td class="dni-col">{{ $alumno->nro_documento ?? '' }}</td>
                    <td class="celular-col">{{ $alumno->telefono ?? '' }}</td>
                </tr>
            @empty
                @for($i = 1; $i <= 15; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td class="nombre-col"></td>
                        <td class="apellidos-col"></td>
                        <td class="dni-col"></td>
                        <td class="celular-col"></td>
                    </tr>
                @endfor
            @endforelse
            
            {{-- Añadir filas vacías si hay menos de 15 alumnos --}}
            @if(count($alumnos) > 0 && count($alumnos) < 15)
                @for($i = count($alumnos) + 1; $i <= 15; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td class="nombre-col"></td>
                        <td class="apellidos-col"></td>
                        <td class="dni-col"></td>
                        <td class="celular-col"></td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>
</body>
</html>
