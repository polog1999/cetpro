<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Renombrar la tabla
        Schema::rename('rubros', 'especialidades');

        // 2) Renombrar columnas dentro de la nueva tabla
        Schema::table('especialidades', function (Blueprint $table) {
            $table->renameColumn('id_rubro', 'id_especialidad');
            $table->renameColumn('nombre_rubro', 'nombre_especialidad');
        });
    }

    public function down(): void
    {
        Schema::table('especialidades', function (Blueprint $table) {
            $table->renameColumn('id_especialidad', 'id_rubro');
            $table->renameColumn('nombre_especialidad', 'nombre_rubro');
        });

        Schema::rename('especialidades', 'rubros');
    }
};
