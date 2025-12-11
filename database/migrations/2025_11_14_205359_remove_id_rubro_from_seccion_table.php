<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Try to drop FK first (safe for both SQLite and Postgres using transaction savepoints)
        if (Schema::hasColumn('seccion', 'id_rubro')) {
            try {
                DB::transaction(function () {
                    Schema::table('seccion', function (Blueprint $table) {
                        $table->dropForeign(['id_rubro']);
                    });
                });
            } catch (\Exception $e) {
                try {
                    DB::transaction(function () {
                        Schema::table('seccion', function (Blueprint $table) {
                            $table->dropForeign('oferta_academica_id_rubro_foreign');
                        });
                    });
                } catch (\Exception $e2) {}
            }

            Schema::table('seccion', function (Blueprint $table) {
                $table->dropColumn('id_rubro');
            });
        }
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
