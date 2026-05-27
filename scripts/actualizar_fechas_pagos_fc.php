<?php

/**
 * Script para actualizar las fechas de vencimiento de los pagos
 * asociados a matrículas de Formación Continua.
 *
 * Las fechas de vencimiento se recalculan según las fechas actuales
 * de los cursos del programa (misma lógica que generarCronograma).
 *
 * USO DESDE TINKER:
 *   php artisan tinker
 *   require 'scripts/actualizar_fechas_pagos_fc.php';
 *
 * El script pedirá el nombre del programa (o parte de él) para buscar.
 * Mostrará un resumen antes de aplicar los cambios.
 */

use App\Models\Programa;
use App\Models\Matricula;
use App\Models\Cronograma;
use App\Models\Pago;
use App\Enums\TipoMatricula;
use App\Enums\TipoPrograma;
use Carbon\Carbon;

// =====================================================
// CONFIGURACIÓN: Especificar el programa a actualizar
// =====================================================
// Puedes buscar por nombre (parcial) o por ID del programa.
// Descomentar y ajustar la opción deseada:

// OPCIÓN 1: Buscar por nombre (busca coincidencia parcial)
$busqueda = 'CASACAS PARA DAMA';

// OPCIÓN 2: Buscar por ID del programa
// $programaId = 1;

// =====================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ACTUALIZACIÓN DE FECHAS DE VENCIMIENTO DE PAGOS           ║\n";
echo "║  Para matrículas de Formación Continua                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// 1. Buscar el programa
if (isset($programaId)) {
    $programa = Programa::find($programaId);
} else {
    $programa = Programa::where('nombre_programa', 'LIKE', "%{$busqueda}%")
        ->where('tipo_programa', TipoPrograma::FORMACION_CONTINUA)
        ->first();
}

if (!$programa) {
    echo "❌ No se encontró ningún programa de Formación Continua con: '{$busqueda}'\n";
    echo "   Programas de FC disponibles:\n";
    Programa::where('tipo_programa', TipoPrograma::FORMACION_CONTINUA)
        ->get()
        ->each(function ($p) {
            echo "   - [{$p->id_programa}] {$p->nombre_programa} (Duración: {$p->duracion} meses)\n";
        });
    return;
}

echo "✅ Programa encontrado: [{$programa->id_programa}] {$programa->nombre_programa}\n";
echo "   Tipo: {$programa->tipo_programa->value}\n";
echo "   Duración: {$programa->duracion} meses\n\n";

// 2. Obtener los cursos del programa ordenados por fecha_inicio
$cursos = $programa->cursos()->orderBy('fecha_inicio', 'asc')->get();

echo "📚 Cursos del programa ({$cursos->count()}):\n";
echo str_repeat('─', 80) . "\n";
printf("   %-4s %-45s %-12s %-12s\n", '#', 'Nombre', 'Inicio', 'Término');
echo str_repeat('─', 80) . "\n";

foreach ($cursos as $i => $curso) {
    $inicio = $curso->fecha_inicio ? Carbon::parse($curso->fecha_inicio)->format('d/m/Y') : 'N/A';
    $termino = $curso->fecha_termino ? Carbon::parse($curso->fecha_termino)->format('d/m/Y') : 'N/A';
    printf("   %-4d %-45s %-12s %-12s\n", $i + 1, mb_substr($curso->nombre_curso, 0, 45), $inicio, $termino);
}
echo str_repeat('─', 80) . "\n\n";

// 3. Calcular las nuevas fechas de vencimiento
// Lógica idéntica a calcularFechasVencimientoCuotas para FORMACION_CONTINUA:
// Se toma fecha_inicio del primer curso del programa y se generan cuotas mensuales (fin de mes)
$numCuotas = max(1, (int) $programa->duracion);
$primerCurso = $cursos->first();

if (!$primerCurso || !$primerCurso->fecha_inicio) {
    echo "❌ El primer curso no tiene fecha de inicio. No se puede calcular.\n";
    return;
}

$fechaInicio = Carbon::parse($primerCurso->fecha_inicio);
$nuevasFechas = [];

for ($i = 0; $i < $numCuotas; $i++) {
    $nuevasFechas[] = $fechaInicio->copy()->addMonths($i)->endOfMonth();
}

echo "📅 Nuevas fechas de vencimiento calculadas ({$numCuotas} cuotas):\n";
foreach ($nuevasFechas as $i => $fecha) {
    echo "   Cuota " . ($i + 1) . ": {$fecha->format('d/m/Y')}\n";
}
echo "\n";

// 4. Obtener los horarios del programa
$horarioIds = $programa->horarios()->pluck('id_horario')->toArray();

if (empty($horarioIds)) {
    echo "❌ No se encontraron horarios para este programa.\n";
    return;
}

// 5. Obtener las matrículas de Formación Continua asociadas a esos horarios
$matriculas = Matricula::whereIn('horario_id', $horarioIds)
    ->where('tipo_matricula', TipoMatricula::FORMACION_CONTINUA)
    ->with(['estudiante', 'cronograma.pagos'])
    ->get();

if ($matriculas->isEmpty()) {
    echo "❌ No se encontraron matrículas de Formación Continua para este programa.\n";
    return;
}

echo "📋 Matrículas encontradas: {$matriculas->count()}\n\n";

// 6. Mostrar resumen de cambios ANTES de aplicar
$totalPagosAfectados = 0;

echo str_repeat('═', 100) . "\n";
echo "  RESUMEN DE CAMBIOS A APLICAR\n";
echo str_repeat('═', 100) . "\n\n";

foreach ($matriculas as $matricula) {
    $estudiante = $matricula->estudiante;
    $nombreEstudiante = $estudiante
        ? trim("{$estudiante->apellido_paterno} {$estudiante->apellido_materno}, {$estudiante->nombres}")
        : 'Sin estudiante';

    $cronograma = $matricula->cronograma;

    if (!$cronograma) {
        echo "   ⚠️  Matrícula #{$matricula->id} ({$nombreEstudiante}): SIN CRONOGRAMA - se omite\n";
        continue;
    }

    $pagos = $cronograma->pagos()->orderBy('nro_cuota', 'asc')->get();

    if ($pagos->isEmpty()) {
        echo "   ⚠️  Matrícula #{$matricula->id} ({$nombreEstudiante}): SIN PAGOS - se omite\n";
        continue;
    }

    echo "   👤 Matrícula #{$matricula->id} - {$nombreEstudiante} (Código: {$matricula->codigo_inscripcion})\n";

    foreach ($pagos as $pago) {
        $cuotaIndex = $pago->nro_cuota - 1;

        if (!isset($nuevasFechas[$cuotaIndex])) {
            echo "      ⚠️  Cuota {$pago->nro_cuota}: No hay fecha calculada (más cuotas que meses) - se omite\n";
            continue;
        }

        $fechaActual = $pago->fecha_vencimiento
            ? Carbon::parse($pago->fecha_vencimiento)->format('d/m/Y')
            : 'N/A';
        $fechaNueva = $nuevasFechas[$cuotaIndex]->format('d/m/Y');

        $cambio = ($fechaActual !== $fechaNueva) ? '⟵ CAMBIA' : '✓ Ya correcta';

        echo "      Cuota {$pago->nro_cuota}: {$fechaActual} → {$fechaNueva} {$cambio}\n";

        if ($fechaActual !== $fechaNueva) {
            $totalPagosAfectados++;
        }
    }

    echo "\n";
}

if ($totalPagosAfectados === 0) {
    echo "✅ Todas las fechas ya están correctas. No hay cambios que aplicar.\n";
    return;
}

echo str_repeat('═', 100) . "\n";
echo "  Total de pagos que serán actualizados: {$totalPagosAfectados}\n";
echo str_repeat('═', 100) . "\n\n";

// 7. Confirmación - En tinker no hay readline, aplicamos directamente
echo "⚡ APLICANDO CAMBIOS...\n\n";

$pagosActualizados = 0;
$errores = [];

foreach ($matriculas as $matricula) {
    $cronograma = $matricula->cronograma;

    if (!$cronograma) {
        continue;
    }

    $pagos = $cronograma->pagos()->orderBy('nro_cuota', 'asc')->get();

    foreach ($pagos as $pago) {
        $cuotaIndex = $pago->nro_cuota - 1;

        if (!isset($nuevasFechas[$cuotaIndex])) {
            continue;
        }

        $fechaActual = $pago->fecha_vencimiento
            ? Carbon::parse($pago->fecha_vencimiento)->format('Y-m-d')
            : null;
        $fechaNueva = $nuevasFechas[$cuotaIndex]->format('Y-m-d');

        if ($fechaActual !== $fechaNueva) {
            try {
                $pago->update(['fecha_vencimiento' => $nuevasFechas[$cuotaIndex]]);
                $pagosActualizados++;

                $estudiante = $matricula->estudiante;
                $nombre = $estudiante
                    ? "{$estudiante->apellido_paterno} {$estudiante->apellido_materno}"
                    : "Mat#{$matricula->id}";

                echo "   ✅ {$nombre} - Cuota {$pago->nro_cuota}: {$fechaActual} → {$fechaNueva}\n";
            } catch (\Exception $e) {
                $errores[] = "Pago #{$pago->id}: {$e->getMessage()}";
                echo "   ❌ Error en Pago #{$pago->id}: {$e->getMessage()}\n";
            }
        }
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  RESULTADO FINAL                                           ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║  Pagos actualizados: %-38s ║\n", $pagosActualizados);
printf("║  Errores:            %-38s ║\n", count($errores));
echo "╚══════════════════════════════════════════════════════════════╝\n";

if (!empty($errores)) {
    echo "\n⚠️  ERRORES:\n";
    foreach ($errores as $error) {
        echo "   - {$error}\n";
    }
}

echo "\n✅ Proceso completado.\n";
