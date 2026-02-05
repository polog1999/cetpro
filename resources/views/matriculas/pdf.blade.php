<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de Matrícula</title>

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
        }

        td, th {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .title-main {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }

        .section-title {
            font-weight: bold;
            background-color: #eaeaea;
        }

        .text-center {
            text-align: center;
        }

        .course-list {
            margin: 10px 0;
            padding-left: 20px;
        }

        .course-item {
            margin: 3px 0;
        }

        .course-selected {
            font-weight: bold;
            background-color: #ffffcc;
            padding: 2px 4px;
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
    
    // Determinar etiqueta según tipo de matrícula
    $esTipoModulo = in_array($matricula->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::MODULO]);
    $labelCursos = $esTipoModulo ? 'Módulos' : 'Cursos';
    $labelCursosSingular = $esTipoModulo ? 'Módulo' : 'Curso';
@endphp

{{-- ENCABEZADO --}}
<table class="no-border">
    <tr>
        <td style="width: 15%; text-align: center;">
            {{-- Si tienes logo, puedes colocarlo aquí con <img> --}}
        </td>
        <td style="width: 70%;">
            <div class="title-main">FICHA DE MATRÍCULA</div>
            <div class="subtitle">EDUCACIÓN TÉCNICO - PRODUCTIVA</div>
        </td>
        <td style="width: 15%;"></td>
    </tr>
</table>

<br>

<table>
    <tr>
        <td style="width: 25%;">AÑO</td>
        <td style="width: 25%;">{{ $matricula->created_at?->format('Y') }}</td>
        <td style="width: 25%;">CÓDIGO DE INSCRIPCIÓN</td>
        <td style="width: 25%;">{{ $matricula->codigo_inscripcion }}</td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <td colspan="8">DATOS DE LA INSTITUCIÓN</td>
    </tr>
    <tr>
        <td style="width: 15%;">CETPRO</td>
        <td colspan="3">"LA MOLINA"</td>
        <td style="width: 15%;">CONVENIO N°</td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td>REGIÓN</td>
        <td>LIMA</td>
        <td>UGEL</td>
        <td>06</td>
        <td>PROVINCIA</td>
        <td>LIMA</td>
        <td>DISTRITO</td>
        <td>LA MOLINA</td>
    </tr>
    <tr>
        <td>LUGAR</td>
        <td>LA MOLINA</td>
        <td>DIRECCIÓN</td>
        <td colspan="5">CALLE LOS ALMENDROS CIUDAD A (ejemplo)</td>
    </tr>
</table>

<br>

{{-- DATOS DEL ESTUDIANTE --}}
<table>
    <tr class="section-title">
        <td colspan="8">DATOS DEL ESTUDIANTE</td>
    </tr>
    <tr>
        <td>APELLIDO PATERNO</td>
        <td>{{ $est?->apellido_paterno }}</td>
        <td>APELLIDO MATERNO</td>
        <td>{{ $est?->apellido_materno }}</td>
        <td>NOMBRES</td>
        <td colspan="3">{{ $est?->nombres }}</td>
    </tr>
    <tr>
        <td>SEXO</td>
        <td>{{ $est?->genero }}</td>
        <td>Edad</td>
        <td>
            @if($est?->fecha_nacimiento)
                {{ \Carbon\Carbon::parse($est->fecha_nacimiento)->age }} años
            @endif
        </td>
        <td>Estado civil</td>
        <td>{{ $est?->estado_civil }}</td>
        <td>Grado de instrucción</td>
        <td>{{ $est?->grado_instruccion }}</td>
    </tr>
    <tr>
        <td>Documento de Identidad</td>
        <td colspan="2">
            {{ $est?->tipo_documento }}: {{ $est?->nro_documento }}
        </td>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td>DOMICILIO</td>
        <td colspan="3">{{ $est?->direccion }}</td>
        <td>PROVINCIA</td>
        <td>{{ $est?->provincia }}</td>
        <td>DISTRITO</td>
        <td>{{ $est?->distrito }}</td>
    </tr>
    <tr>
        <td>LUGAR</td>
        <td>LIMA</td>
        <td>TELÉFONO</td>
        <td>{{ $est?->telefono }}</td>
        <td>CORREO ELECTRÓNICO</td>
        <td colspan="3">{{ $est?->email }}</td>
    </tr>
</table>

<br>

{{-- DATOS DEL CENSO ESCOLAR --}}
<table>
    <tr class="section-title">
        <td colspan="8">DATOS DEL CENSO ESCOLAR</td>
    </tr>
    <tr>
        <td>TIPO DISCAPACIDAD</td>
        <td colspan="3">{{ $est?->tipo_discapacidad?->getLabel() ?? 'Ninguna' }}</td>
        <td>SUBTIPO DISCAPACIDAD</td>
        <td colspan="3">{{ $est?->subtipo_discapacidad?->getLabel() ?? '-' }}</td>
    </tr>
    <tr>
        <td>PROGRAMA REPARACIÓN</td>
        <td colspan="3">{{ $est?->tipo_programa_reparacion?->getLabel() ?? 'Ninguno' }}</td>
        <td>LENGUA MATERNA</td>
        <td colspan="3">{{ $est?->lengua_materna?->getLabel() ?? '-' }}</td>
    </tr>
    <tr>
        <td>AÑO EGRESO EBR</td>
        <td colspan="3">{{ $est?->anio_egreso_ebr ?? '-' }}</td>
        <td>EDAD AL 31 MARZO</td>
        <td colspan="3">{{ $est?->edad_al_31_marzo ?? '-' }} años</td>
    </tr>
</table>

<br>

{{-- DATOS ACADÉMICOS --}}
<table>
    <tr class="section-title">
        <td colspan="8">DATOS ACADÉMICOS</td>
    </tr>
    <tr>
        <td>CICLO</td>
        <td></td>
        <td>ESPECIALIDAD / OPCIÓN OCUPACIONAL</td>
        <td colspan="3">{{ $prog?->nombre_programa }}</td>
        <td>TURNO</td>
        <td>{{ $horario?->turno?->value ?? $horario?->turno }}</td>
    </tr>
    <tr>
        <td>{{ $labelCursosSingular }} SELECCIONADO</td>
        <td colspan="3">
            @if($curso)
                {{ $curso->nombre_curso }}
            @else
                @if(in_array($matricula->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA]))
                    Matrícula completa (todos los {{ strtolower($labelCursos) }})
                @else
                    -
                @endif
            @endif
        </td>
        <td>TIPO MATRÍCULA</td>
        <td colspan="3">{{ $matricula->tipo_matricula?->value ?? $matricula->tipo_matricula }}</td>
    </tr>
    <tr>
        <td>DURACIÓN</td>
        <td>{{ $prog?->duracion }}</td>
        <td>MODALIDAD</td>
        <td>{{ $horario?->modalidad?->value ?? $horario?->modalidad }}</td>
        <td>HORARIO</td>
        <td colspan="3">
            {{ is_array($horario?->dias) ? implode(', ', $horario->dias) : $horario?->dias }} 
            @if($horario?->hora_inicio && $horario?->hora_fin)
                | {{ $horario->hora_inicio->format('H:i') }} - {{ $horario->hora_fin->format('H:i') }}
            @endif
        </td>
    </tr>
    <tr>
        <td>DOCENTE</td>
        <td colspan="7">{{ $horario?->docente?->nombre_completo ?? 'No asignado' }}</td>
    </tr>
    <tr>
        <td>GRADO ACADÉMICO</td>
        <td colspan="7">
            @php
                $grado = match ($matricula->tipo_matricula) {
                    TipoMatricula::FORMACION_CONTINUA => 'Certificado de Estudio',
                    TipoMatricula::PROGRAMA => 'Título Auxiliar Técnico',
                    TipoMatricula::CURSO => 'Certificado',
                    TipoMatricula::MODULO => 'Certificado del Módulo',
                    default => 'N/A',
                };
            @endphp
            {{ $grado }}
        </td>
    </tr>
    
</table>

</table>

<br>



<br><br>

<table class="no-border">
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <tr>
        <td class="text-center">
            _______________________________<br>
            FIRMA DEL ESTUDIANTE
        </td>
        <td class="text-center">
            _______________________________<br>
            FIRMA DEL DIRECTOR
        </td>
    </tr>
</table>

</body>
</html>
