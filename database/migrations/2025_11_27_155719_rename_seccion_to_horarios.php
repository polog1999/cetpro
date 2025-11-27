<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1) Eliminar foreign keys que apunten a seccion.id_seccion
         *    (aquí pongo solo matriculas, añade otras tablas si las tienes)
         */
        Schema::table('matriculas', function (Blueprint $table) {
            // Si usaste la convención por defecto, esto funciona:
            $table->dropForeign(['seccion_id']);
            // Si te da error, usa el nombre explícito:
            // $table->dropForeign('matriculas_seccion_id_foreign');
        });

        /**
         * 2) Renombrar tabla `seccion` -> `horarios`
         */
        Schema::rename('seccion', 'horarios');

        /**
         * 3) Renombrar columna PK en la nueva tabla
         */
        Schema::table('horarios', function (Blueprint $table) {
            $table->renameColumn('id_seccion', 'id_horario');
        });

        /**
         * 4) Renombrar columna FK en matriculas
         */
        Schema::table('matriculas', function (Blueprint $table) {
            $table->renameColumn('seccion_id', 'horario_id');
        });

        /**
         * 5) Volver a crear las foreign keys apuntando a la nueva tabla/columna
         */
        Schema::table('matriculas', function (Blueprint $table) {
            $table->foreign('horario_id')
                ->references('id_horario')
                ->on('horarios')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Revertir todo al estado anterior

        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropForeign(['horario_id']);
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->renameColumn('id_horario', 'id_seccion');
        });

        Schema::table('matriculas', function (Blueprint $table) {
            $table->renameColumn('horario_id', 'seccion_id');
        });

        Schema::rename('horarios', 'seccion');

        Schema::table('matriculas', function (Blueprint $table) {
            $table->foreign('seccion_id')
                ->references('id_seccion')
                ->on('seccion')
                ->onDelete('cascade');
        });
    }
};
