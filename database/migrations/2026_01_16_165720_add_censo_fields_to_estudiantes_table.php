<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos del censo escolar (Tablas 201-208) a la tabla estudiantes
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            // Tabla 205: Discapacidad y Necesidades Especiales
            $table->string('tipo_discapacidad')->nullable()->default('Ninguna');
            $table->string('subtipo_discapacidad')->nullable();
            
            // Tabla 206: Situación de Vulnerabilidad (Ley 28592)
            $table->string('tipo_programa_reparacion')->nullable()->default('Ninguno');
            
            // Tabla 207: Lengua Materna
            $table->string('lengua_materna')->nullable();
            
            // Tabla 203/204: Trayectoria Académica
            $table->integer('anio_egreso_ebr')->nullable(); // Año en que terminó el colegio
            $table->string('grado_instruccion_ebr')->nullable();
            
            // Tabla 208: Atributos de Matrícula
            $table->string('ciclo_formacion')->nullable();
            $table->string('turno_matricula')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_discapacidad',
                'subtipo_discapacidad',
                'tipo_programa_reparacion',
                'lengua_materna',
                'anio_egreso_ebr',
                'grado_instruccion_ebr',
                'ciclo_formacion',
                'turno_matricula',
            ]);
        });
    }
};
