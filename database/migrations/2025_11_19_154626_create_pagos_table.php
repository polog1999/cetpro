<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id(); // id_pago

            $table->foreignId('cronograma_id')
                ->constrained('cronogramas')
                ->cascadeOnDelete();

            $table->unsignedInteger('nro_cuota'); // número de cuota dentro del cronograma
            $table->string('codigo')->unique();
            $table->decimal('monto', 10, 2);

            $table->string('estado', 20)->default('pendiente');

            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();

            $table->string('metodo_pago', 50)->nullable();
            $table->string('evidencia_path')->nullable();

            $table->timestamps();

            // nro_cuota único dentro del cronograma
            $table->unique(['cronograma_id', 'nro_cuota'], 'cronograma_cuota_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
