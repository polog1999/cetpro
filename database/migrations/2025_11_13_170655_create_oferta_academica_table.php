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
        Schema::create('oferta_academica', function (Blueprint $table) {
            $table->bigIncrements('id_oferta');

            // Según tu modelo y Enum TipoOfertaAcademica
            $table->enum('tipo_oferta', [
                'PROG_ESTUDIO',
                'PROG_CONTINUA',
                'CURSO_LIBRE',
            ]);

            // Relaciones
            $table->unsignedBigInteger('id_programa')->nullable();
            $table->unsignedBigInteger('id_curso')->nullable();
            $table->unsignedBigInteger('id_rubro')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('id_programa')
                ->references('id_programa')
                ->on('programas')
                ->nullOnDelete();

            $table->foreign('id_curso')
                ->references('id_curso')
                ->on('cursos')
                ->nullOnDelete();

            $table->foreign('id_rubro')
                ->references('id_rubro')
                ->on('rubros')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oferta_academica');
    }
};
