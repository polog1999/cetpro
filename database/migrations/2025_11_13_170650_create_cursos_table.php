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
        Schema::create('cursos', function (Blueprint $table) {
            $table->bigIncrements('id_curso');

            // Campos según tu modelo Curso
            $table->string('nombre_curso', 150);

            // Asumo que "duracion" es un número (por ejemplo, horas o semanas).
            // Si prefieres texto (ej: "3 meses"), puedes cambiar a string.
            $table->integer('duracion')->nullable();

            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();

            $table->string('aula', 50)->nullable();

            // Relación con Programas
            $table->unsignedBigInteger('id_programa')->nullable();

            $table->timestamps();

            // Clave foránea hacia programas.id_programa
            $table->foreign('id_programa')
                ->references('id_programa')
                ->on('programas')
                ->nullOnDelete(); // si borras el programa, deja id_programa en NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
