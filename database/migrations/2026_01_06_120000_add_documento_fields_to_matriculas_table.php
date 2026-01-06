<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para almacenar el certificado/documento de cada matrícula.
     */
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('documento_path')->nullable()->after('estado');
            $table->string('tipo_certificado')->nullable()->after('documento_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['documento_path', 'tipo_certificado']);
        });
    }
};
