<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de notas finales por curso/módulo.
 * 
 * Cada matrícula puede tener una nota por cada curso del programa.
 * Soporta calificación numérica (0-20) y en letra (AD, A, B, C).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            
            // Referencias principales
            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();
            
            $table->unsignedBigInteger('curso_id');
            $table->foreign('curso_id')
                ->references('id_curso')
                ->on('cursos')
                ->cascadeOnDelete();
            
            $table->unsignedBigInteger('docente_id')->nullable();
            $table->foreign('docente_id')
                ->references('id')
                ->on('docentes')
                ->nullOnDelete();
            
            // Calificaciones
            $table->decimal('nota_numerica', 4, 2)->nullable();   // 0.00 - 20.00
            $table->string('nota_letra', 5)->nullable();          // AD, A, B, C
            
            // Documento adjunto
            $table->string('pdf_calificacion')->nullable();       // Ruta al PDF
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            // Solo una nota por curso por matrícula
            $table->unique(['matricula_id', 'curso_id'], 'notas_matricula_curso_unique');
            
            // Índices para búsquedas frecuentes
            $table->index('curso_id');
            $table->index('docente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
