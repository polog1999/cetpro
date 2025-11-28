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
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('recurso')->unique(); // Identificador del recurso (MatriculaResource, ProgramaResource, etc.)
            $table->string('nombre'); // Nombre legible (Matriculas, Programas, etc.)
            $table->string('grupo')->nullable(); // Agrupación (Gestión Estudiantil, Gestión Académica, etc.)
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
