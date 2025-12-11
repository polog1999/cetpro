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
        Schema::table('matriculas', function (Blueprint $table) {
            $table->text('motivo_anulacion')->nullable()->after('estado');
            $table->dateTime('fecha_anulacion')->nullable()->after('motivo_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['motivo_anulacion', 'fecha_anulacion']);
        });
    }
};
