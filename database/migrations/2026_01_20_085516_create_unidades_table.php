<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla unidades para soportar la jerarquía:
     * Programa → Módulo (curso) → Unidades
     * 
     * Solo se utiliza para programas de tipo PROGRAMA_ESTUDIO.
     * Formación Continua no utiliza unidades.
     */
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->bigIncrements('id_unidad');
            
            // FK al curso/módulo padre
            $table->unsignedBigInteger('id_curso');
            
            $table->string('nombre_unidad', 150);
            $table->integer('duracion')->nullable()->comment('Duración en horas');
            $table->integer('orden')->default(1)->comment('Orden de la unidad dentro del módulo');
            $table->text('descripcion')->nullable();
            
            $table->timestamps();
            
            // Índice para optimizar consultas por curso
            $table->index('id_curso');
            
            // FK con cascade delete: si se elimina el módulo, se eliminan sus unidades
            $table->foreign('id_curso')
                ->references('id_curso')
                ->on('cursos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
