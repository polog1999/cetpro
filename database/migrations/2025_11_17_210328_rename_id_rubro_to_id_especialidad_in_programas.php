<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            // Si tienes clave foránea, primero habría que eliminarla:
            // $table->dropForeign(['id_rubro']);

            $table->renameColumn('id_rubro', 'id_especialidad');
        });
    }

    public function down(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->renameColumn('id_especialidad', 'id_rubro');
        });
    }
};

