<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cambia el campo estado de enum a string para sincronizar con Oracle.
     * El estado ahora viene directamente de VU_BUSCA_TUSNE_PER_Pen.ESTADO
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Cambiar estado a string (nullable para migración gradual)
            $table->string('estado')->nullable()->default('Pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Revertir a string con valores anteriores
            $table->string('estado')->nullable()->change();
        });
    }
};
