<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar columna modalidad a oferta_academica
        Schema::table('oferta_academica', function (Blueprint $table) {
            $table->enum('modalidad', [
                'Presencial',
                'Virtual',
                'Semipresencial',
            ])->nullable()->after('tipo_oferta');
        });

        // 2) (Opcional) si quieres copiar datos desde programas antes de borrar la columna,
        //    aquí iría la lógica con DB::table(...)

        // 3) Quitar columna modalidad de programas
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }

    public function down(): void
    {
        // Volver a poner modalidad en programas
        Schema::table('programas', function (Blueprint $table) {
            $table->enum('modalidad', [
                'Presencial',
                'Virtual',
                'Semipresencial',
            ])->nullable();
        });

        // Quitar modalidad de oferta_academica
        Schema::table('oferta_academica', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
