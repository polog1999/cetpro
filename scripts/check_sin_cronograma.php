<?php
// Script para diagnosticar matrículas sin cronograma de pagos
// Ejecutar: php artisan tinker scripts/check_sin_cronograma.php

$sinCrono = App\Models\Matricula::doesntHave('cronograma')
    ->where('estado', '!=', 'Anulado')
    ->with(['horario.programa', 'estudiante'])
    ->get();

echo PHP_EOL;
echo "=========================================" . PHP_EOL;
echo " MATRÍCULAS SIN CRONOGRAMA DE PAGOS" . PHP_EOL;
echo "=========================================" . PHP_EOL;
echo "Total encontradas: " . $sinCrono->count() . PHP_EOL;
echo str_repeat('-', 100) . PHP_EOL;

if ($sinCrono->isEmpty()) {
    echo "✓ No hay matrículas sin cronograma." . PHP_EOL;
    return;
}

foreach ($sinCrono as $i => $m) {
    $est = $m->estudiante;
    $nombreCompleto = $est
        ? trim("{$est->nombres} {$est->apellido_paterno} {$est->apellido_materno}")
        : 'SIN ESTUDIANTE';
    $dni = $est->nro_documento ?? 'SIN DNI';
    $programa = $m->horario?->programa?->nombre_programa ?? 'Sin programa';

    echo ($i + 1) . ". Matrícula ID: {$m->id}" . PHP_EOL;
    echo "   Código Inscripción : " . ($m->codigo_inscripcion ?? 'SIN CÓDIGO') . PHP_EOL;
    echo "   Estudiante         : {$nombreCompleto}" . PHP_EOL;
    echo "   DNI                : {$dni}" . PHP_EOL;
    echo "   Tipo               : " . ($m->tipo_matricula?->value ?? 'NULL') . PHP_EOL;
    echo "   Programa/FC        : {$programa}" . PHP_EOL;
    echo "   Estado             : " . ($m->estado?->value ?? $m->estado) . PHP_EOL;
    echo "   Creada             : " . ($m->created_at?->format('d/m/Y H:i') ?? '?') . PHP_EOL;
    echo str_repeat('-', 100) . PHP_EOL;
}
