<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1. Quitar la foreign key antigua en matriculas
         */
        Schema::table('matriculas', function (Blueprint $table) {
            // Opción más segura (usa el nombre de la columna)
            $table->dropForeign(['oferta_academica_id']);

            // Si te da error diciendo que no existe esa FK,
            // usa el nombre que salga en el mensaje, por ejemplo:
            // $table->dropForeign('matriculas_oferta_academica_id_foreign');
        });

        /**
         * 2. Renombrar la PK en seccion: id_oferta -> id_seccion
         */
        Schema::table('seccion', function (Blueprint $table) {
            $table->renameColumn('id_oferta', 'id_seccion');
        });

        /**
         * 3. Renombrar la FK en matriculas: oferta_academica_id -> seccion_id
         */
        Schema::table('matriculas', function (Blueprint $table) {
            $table->renameColumn('oferta_academica_id', 'seccion_id');
        });

        /**
         * 4. Crear la nueva foreign key
         */
        Schema::table('matriculas', function (Blueprint $table) {
            $table->foreign('seccion_id')
                ->references('id_seccion')
                ->on('seccion')  // o 'secciones' si tu tabla es plural
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        /**
         * Revertir todo
         */

        // 1. Quitar la nueva FK
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropForeign(['seccion_id']);
        });

        // 2. Volver a los nombres antiguos de las columnas
        Schema::table('matriculas', function (Blueprint $table) {
            $table->renameColumn('seccion_id', 'oferta_academica_id');
        });

        Schema::table('seccion', function (Blueprint $table) {
            $table->renameColumn('id_seccion', 'id_oferta');
        });

        // 3. Volver a crear la FK antigua
        Schema::table('matriculas', function (Blueprint $table) {
            $table->foreign('oferta_academica_id')
                ->references('id_oferta')
                ->on('seccion')  // antes se llamaba oferta_academica, ahora seccion
                ->onDelete('cascade');
        });
    }
};
