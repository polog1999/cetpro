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
        Schema::table('matriculas', function (Blueprint $table) {
            // La tabla YA tiene: id, codigo, estudiante_id, seccion_id, estado, timestamps.

            // Agregamos SOLO esta columna nueva
            $table->unsignedBigInteger('oferta_academica_id')
                ->nullable()
                ->after('estudiante_id');

            // Y su foreign key
            $table->foreign('oferta_academica_id')
                ->references('id_oferta')
                ->on('oferta_academica')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            // Quitamos la FK y la columna que esta migración agregó
            $table->dropForeign(['oferta_academica_id']);
            $table->dropColumn('oferta_academica_id');
        });
    }
};
