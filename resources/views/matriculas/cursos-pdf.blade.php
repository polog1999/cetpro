<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cursos de Matrícula</title>

    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        body {
            margin: 20px;
        }

        h1, h2, h3, h4 {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        td, th {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .title-main {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            background-color: #eaeaea;
            font-size: 12px;
        }

        .text-center {
            text-align: center;
        }

        .course-selected {
            font-weight: bold;
            background-color: #e8f5e9; /* Tono verde muy suave para los matriculados */
        }

        .info-box {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
    </style>
</head>
<body>
@php
    use App\Enums\TipoMatricula;
    
    $est     = $matricula->estudiante;
    $horario = $matricula->horario;
    $prog    = $horario?->programa;
    $curso   = $matricula->curso;
    $cursos  = $prog?->cursos ?? collect();

     // NUEVO: Ordenamos los cursos/módulos cronológicamente por su fecha de inicio 
    // y reiniciamos los índices con values() para que el numerador (#) sea correlativo de 1 a N
    $cursos  = $prog?->cursos ? $prog->cursos->sortBy('fecha_inicio')->values() : collect();
    
    // Fallback de seguridad: si no se pasaron los IDs desde la llamada, los calculamos aquí
    if (!isset($cursosActivosIds)) {
        $cursosActivosIds = $matricula->obtenerCursosActivos()->pluck('id_curso')->toArray();
    }

    $esTipoModulo = in_array($matricula->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::MODULO]);
    $labelCursos = $esTipoModulo ? 'MÓDULOS' : 'CURSOS';
    $labelPrograma = $esTipoModulo ? 'PROGRAMA' : 'FORMACIÓN CONTINUA';
@endphp

{{-- ENCABEZADO --}}
<div class="title-main">{{ $labelCursos }} DE MATRÍCULA</div>
<div class="subtitle">{{ $labelPrograma }}: {{ $prog?->nombre_programa }}</div>

{{-- INFORMACIÓN DEL ESTUDIANTE --}}
<div class="info-box">
    <div><span class="info-label">Estudiante:</span> {{ $est?->nombres }} {{ $est?->apellido_paterno }} {{ $est?->apellido_materno }}</div>
    <div><span class="info-label">DNI:</span> {{ $est?->nro_documento }}</div>
    <div><span class="info-label">Código Inscripción:</span> {{ $matricula->codigo_inscripcion }}</div>
    <div><span class="info-label">Tipo de Matrícula:</span> {{ $matricula->tipo_matricula?->value ?? $matricula->tipo_matricula }}</div>
</div>

{{-- INFORMACIÓN DEL HORARIO --}}
<table>
    <tr class="section-title">
        <td colspan="4">INFORMACIÓN DEL HORARIO</td>
    </tr>
    <tr>
        <td style="width: 20%;"><strong>Turno:</strong></td>
        <td style="width: 30%;">{{ $horario?->turno?->value ?? $horario?->turno }}</td>
        <td style="width: 20%;"><strong>Modalidad:</strong></td>
        <td style="width: 30%;">{{ $horario?->modalidad?->value ?? $horario?->modalidad }}</td>
    </tr>
    <tr>
        <td><strong>Días:</strong></td>
        <td>{{ is_array($horario?->dias) ? implode(', ', $horario->dias) : $horario?->dias }}</td>
        <td><strong>Horario:</strong></td>
        <td>{{ $horario?->horario }}</td>
    </tr>
    <tr>
        <td><strong>Duración Total:</strong></td>
        <td colspan="3">{{ $prog?->duracion }} meses</td>
    </tr>
</table>

{{-- LISTA DE CURSOS/MÓDULOS --}}
<table>
    <tr class="section-title">
        <td colspan="4">{{ $labelCursos }} DEL {{ $labelPrograma }}</td>
    </tr>
    <tr class="section-title">
        <td style="width: 5%;">#</td>
        <td style="width: 45%;">Nombre del {{ $esTipoModulo ? 'Módulo' : 'Curso' }}</td>
        <td style="width: 20%;">Duración</td>
        <td style="width: 30%;">Fecha Inicio</td>
    </tr>
    @if($cursos->isEmpty())
        <tr>
            <td colspan="4" class="text-center">
                Este {{ mb_strtolower($labelPrograma) }} no tiene {{ mb_strtolower($labelCursos) }} registrados.
            </td>
        </tr>
    @else
        @foreach($cursos as $index => $c)
            @php
                // Verificamos si este curso específico está activo según las cuotas generadas
                $estaMatriculado = in_array($c->id_curso, $cursosActivosIds);
            @endphp
            <tr class="{{ $estaMatriculado ? 'course-selected' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $c->nombre_curso }}
                    @if($estaMatriculado)
                        <strong> ✓ (Matriculado)</strong>
                    @endif
                </td>
                <td>{{ $c->duracion ?? '-' }}</td>
                <td>{{ $c->fecha_inicio ? \Carbon\Carbon::parse($c->fecha_inicio)->format('d/m/Y') : '-' }}</td>
            </tr>
        @endforeach
    @endif
</table>

{{-- NOTAS ACADÉMICAS DEL CRONOGRAMA EN LA PARTE INFERIOR --}}
@if(in_array($matricula->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::UNIDAD]))
    <div style="margin-top: 20px; padding: 10px; background-color: #fffacd; border: 1px solid #ddd;">
        <strong>Nota:</strong> Este estudiante está matriculado individualmente en el {{ mb_strtolower($esTipoModulo ? 'módulo' : 'curso') }}: 
        <strong>{{ $curso?->nombre_curso ?? '-' }}</strong>
    </div>
@else
    @php
        $totalCursosPrograma = $cursos->count();
        $totalCursosActivos  = count($cursosActivosIds);
    @endphp
    
    @if($totalCursosActivos === $totalCursosPrograma)
        <div style="margin-top: 20px; padding: 10px; background-color: #e8f5e9; border: 1px solid #ddd;">
            <strong>Nota:</strong> Matrícula completa en todos los {{ mb_strtolower($labelCursos) }} del {{ mb_strtolower($labelPrograma) }}.
        </div>
    @else
        <div style="margin-top: 20px; padding: 10px; background-color: #e3f2fd; border: 1px solid #ddd;">
            <strong>Nota:</strong> Matrícula parcial activa. El estudiante está matriculado en <strong>{{ $totalCursosActivos }}</strong> de <strong>{{ $totalCursosPrograma }}</strong> {{ mb_strtolower($labelCursos) }} del {{ mb_strtolower($labelPrograma) }}, según su cronograma de pagos contratado.
        </div>
    @endif
@endif

<br>

<div style="font-size: 9px; color: #666; text-align: center; margin-top: 30px;">
    Documento generado el {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>