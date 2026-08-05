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
        Schema::create('unidades_didacticas_ugel', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('programa_id')->constrained('programas')->references('id_programa')->restrictOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->references('id_curso')->restrictOnDelete();
            $table->integer('creditos')->nullable();
            $table->integer('horas')->nullable();
            $table->string('capacidad', 500)->nullable();
            $table->integer('orden')->nullable();
            $table->boolean('es_efsrt')->default(false); // Para identificar si es la EFSRT
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_didacticas_ugel');
    }
};
