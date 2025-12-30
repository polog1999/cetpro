<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();
                
            $table->foreignId('curso_id')
                ->constrained('cursos', 'id_curso')
                ->cascadeOnDelete();
                
            $table->foreignId('docente_id')
                ->constrained('docentes', 'id')
                ->cascadeOnDelete();
            
            // Datos de la evaluación
            $table->enum('tipo_evaluacion', [
                'practica',
                'teoria',
                'proyecto',
                'parcial',
                'final',
                'recuperacion',
                'subsanacion'
            ]);
            
            $table->string('periodo')->nullable(); // ej: "Bimestre 1", "Trimestre 2"
            
            // Calificaciones
            $table->decimal('nota', 5, 2); // Nota numérica
            $table->string('nota_letra', 2)->nullable(); // A, B, C, D, F
            
            // Información adicional
            $table->text('observaciones')->nullable();
            $table->date('fecha_evaluacion');
            
            $table->timestamps();
            
            // Índices
            $table->index('docente_id');
            $table->index('curso_id');
            
            // Índice único compuesto para evitar notas duplicadas
            $table->unique(
                ['matricula_id', 'curso_id', 'tipo_evaluacion', 'periodo'],
                'notas_unique_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};

