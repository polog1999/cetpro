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
        Schema::create('rubros', function (Blueprint $table) {
            $table->bigIncrements('id_rubro');

            // Campos según tu modelo Rubro
            $table->string('nombre_rubro', 150);

            $table->decimal('costo_mensual', 10, 2); // 99999999.99 máximo
            $table->string('num_resolucion', 100)->nullable();

            $table->date('fecha_registro')->nullable();
            $table->date('fecha_inicio_vigencia')->nullable();
            $table->date('fecha_fin_vigencia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};
