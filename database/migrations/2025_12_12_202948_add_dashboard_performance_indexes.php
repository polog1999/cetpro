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
        // Índices para tabla matriculas
        Schema::table('matriculas', function (Blueprint $table) {
            // Optimiza: KPI "Matrículas del mes" y gráfico "Matrículas por mes"
            $table->index('created_at', 'idx_matriculas_created_at');
            
            // Optimiza filtros por estado y fecha
            $table->index(['estado', 'created_at'], 'idx_matriculas_estado_created');
        });

        // Índices para tabla pagos
        Schema::table('pagos', function (Blueprint $table) {
            // Optimiza: KPI "Pendiente de cobrar" y "Cuotas vencidas"
            $table->index(['estado', 'fecha_vencimiento'], 'idx_pagos_estado_vencimiento');
            
            // Optimiza: KPI "Ingresos del mes" y gráfico "Pagado vs Pendiente"
            $table->index(['estado', 'fecha_pago'], 'idx_pagos_estado_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropIndex('idx_matriculas_created_at');
            $table->dropIndex('idx_matriculas_estado_created');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('idx_pagos_estado_vencimiento');
            $table->dropIndex('idx_pagos_estado_pago');
        });
    }
};
