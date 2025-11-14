<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seccion', function (Blueprint $table) {
            // Si la columna existe, la borramos
            if (Schema::hasColumn('seccion', 'id_rubro')) {
                $table->dropColumn('id_rubro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seccion', function (Blueprint $table) {
            // Volver a crear la columna en rollback, si quieres
            $table->unsignedBigInteger('id_rubro')->nullable();
            // (Solo agrega la FK de nuevo si realmente la necesitas)
            // $table->foreign('id_rubro')
            //     ->references('id_rubro')
            //     ->on('rubros')
            //     ->onDelete('cascade');
        });
    }
};
