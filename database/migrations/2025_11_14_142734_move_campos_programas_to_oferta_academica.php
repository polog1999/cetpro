<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar columnas a oferta_academica
        Schema::table('oferta_academica', function (Blueprint $table) {
            // ENUM igual que en la tabla programas
            $table->enum('turno', ['Mañana', 'Tarde'])->nullable()->after('tipo_oferta');

            // ajusta tipos si en tu BD son otros
            $table->string('dias')->nullable()->after('turno');
            $table->string('horario')->nullable()->after('dias');

            $table->unsignedBigInteger('docente_id')->nullable()->after('horario');
            $table
                ->foreign('docente_id')
                ->references('id')
                ->on('docentes')
                ->onDelete('set null');
        });

        // 2) (Opcional) Si ya tienes datos y quieres copiarlos, aquí iría la lógica
        //    por ejemplo con DB::table(...) recorriendo programas y rellenando oferta_academica.
        //    Si estás en desarrollo y tu BD está vacía, puedes ignorar esto.

        // 3) Quitar columnas de programas
        Schema::table('programas', function (Blueprint $table) {
            // Si tenías FK a docente:
            try {
                $table->dropForeign(['docente_id']);
            } catch (\Throwable $e) {
                // por si no existe la foreign key, evitamos que falle
            }

            $table->dropColumn(['turno', 'dias', 'horario', 'docente_id']);
        });
    }

    public function down(): void
    {
        // Revertir los cambios

        Schema::table('programas', function (Blueprint $table) {
            $table->enum('turno', ['Mañana', 'Tarde'])->nullable()->after('modalidad');
            $table->string('dias')->nullable()->after('turno');
            $table->string('horario')->nullable()->after('dias');
            $table->unsignedBigInteger('docente_id')->nullable()->after('horario');

            $table
                ->foreign('docente_id')
                ->references('id')
                ->on('docentes')
                ->onDelete('set null');
        });

        Schema::table('oferta_academica', function (Blueprint $table) {
            try {
                $table->dropForeign(['docente_id']);
            } catch (\Throwable $e) {
                //
            }

            $table->dropColumn(['turno', 'dias', 'horario', 'docente_id']);
        });
    }
};
