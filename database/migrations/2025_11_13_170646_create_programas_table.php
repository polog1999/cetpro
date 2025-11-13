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
        Schema::create('programas', function (Blueprint $table) {
    $table->bigIncrements('id_programa');

    $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL', 'MIXTO']);
    $table->enum('turno', ['MANANA', 'TARDE', 'NOCHE']);
    $table->string('nombre_programa', 150);
    $table->integer('duracion')->nullable();
    $table->string('dias', 150)->nullable();
    $table->string('horario', 150)->nullable();
    $table->integer('num_componentes')->nullable();

    // 🔁 AQUÍ el cambio
    $table->unsignedBigInteger('docente_id')->nullable();
    $table->unsignedBigInteger('id_rubro');

    $table->timestamps();

    // FK a docentes.id
    $table->foreign('docente_id')
        ->references('id')
        ->on('docentes')
        ->nullOnDelete();

    $table->foreign('id_rubro')
        ->references('id_rubro')
        ->on('rubros')
        ->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programas');
    }
};
