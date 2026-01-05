<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la columna 'codigo' de la tabla 'pagos'.
 * 
 * Esta columna ya no es necesaria porque se usa 'num_liquidacion' 
 * como identificador único generado desde Oracle.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Eliminar el índice único primero
            $table->dropUnique('pagos_codigo_unique');
            
            // Eliminar la columna
            $table->dropColumn('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('codigo')->unique()->after('nro_cuota');
        });
    }
};
