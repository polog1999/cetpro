<?php

use App\Enums\CondicionPago;
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
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('condicion_pago')->default(CondicionPago::NORMAL->value);
            $table->boolean('generar_pago')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['condicion_pago', 'generar_pago']);
        });
    }
};
