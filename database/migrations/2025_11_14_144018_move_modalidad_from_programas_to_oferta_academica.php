<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar modalidad a oferta_academica
        Schema::table('oferta_academica', function (Blueprint $table) {
            // Mismos valores que tu enum Modalidad
            $table->enum('modalidad', ['Presencial', 'Virtual', 'Semipresencial'])
                  ->nullable()
                  ->after('tipo_oferta');
        });

        // 2) (Opcional) Si quieres copiar los datos antiguos:
        //    Solo si ya tienes registros y usas MySQL:
        /*
        DB::statement('
            UPDATE oferta_academica oa
            JOIN programas p ON oa.id_programa = p.id_programa
            SET oa.modalidad = p.modalidad
            WHERE oa.id_programa IS NOT NULL
        ');
        */

        // 3) Eliminar modalidad de programas
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }

    public function down(): void
    {
        // Volver a poner modalidad en programas
        Schema::table('programas', function (Blueprint $table) {
            $table->enum('modalidad', ['Presencial', 'Virtual', 'Semipresencial'])
                  ->nullable()
                  ->after('id_programa'); // ajusta la posición si quieres
        });

        // Quitarla de oferta_academica
        Schema::table('oferta_academica', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
