<?php
// debug_notas.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Nota;

echo "--- Detalle de Notas ---\n";

$notas = Nota::latest()->take(5)->get();

foreach ($notas as $nota) {
    echo "ID: {$nota->id}\n";
    echo "  Numerica: " . ($nota->nota_numerica ?? 'NULL') . "\n";
    echo "  Letra:    " . ($nota->nota_letra?->value ?? 'NULL') . "\n";
    echo "--------------------\n";
}
