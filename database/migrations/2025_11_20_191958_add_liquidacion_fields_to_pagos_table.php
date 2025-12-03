<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('num_liquidacion')
                ->nullable()
                ->after('metodo_pago'); // o donde prefieras

            $table->date('fecha_liquidacion')
                ->nullable()
                ->after('num_liquidacion');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['num_liquidacion', 'fecha_liquidacion']);
        });
    }
};
