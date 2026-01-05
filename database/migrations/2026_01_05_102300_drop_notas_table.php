<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la tabla 'notas' para reformular el diseño.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('notas');
    }

    public function down(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos', 'id_curso')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('tipo_evaluacion', 50);
            $table->decimal('nota', 5, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->unique(['matricula_id', 'curso_id', 'tipo_evaluacion'], 'notas_unique');
        });
    }
};
