<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            // Añadimos el campo unidad_id apuntando a la tabla unidades (id_unidad)
            $table->foreignId('unidad_id')
                ->nullable()
                ->constrained('unidades', 'id_unidad')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropForeign(['unidad_id']);
            $table->dropColumn('unidad_id');
        });
    }
};