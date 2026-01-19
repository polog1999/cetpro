<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina campos redundantes del censo:
     * - grado_instruccion_ebr: Redundante con grado_instruccion
     * - ciclo_formacion: Siempre es "Auxiliar técnico" (no necesita guardarse)
     * - turno_matricula: Se registra en la matrícula, no en el estudiante
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn([
                'grado_instruccion_ebr',
                'ciclo_formacion',
                'turno_matricula',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('grado_instruccion_ebr')->nullable();
            $table->string('ciclo_formacion')->nullable();
            $table->string('turno_matricula')->nullable();
        });
    }
};
