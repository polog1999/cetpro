<?php

use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Renombrar codigo -> codigo_inscripcion
        Schema::table('matriculas', function (Blueprint $table) {
            if (Schema::hasColumn('matriculas', 'codigo') &&
                ! Schema::hasColumn('matriculas', 'codigo_inscripcion')) {
                $table->renameColumn('codigo', 'codigo_inscripcion');
            }
        });

        // 2) Agregar columnas nuevas e índices
        Schema::table('matriculas', function (Blueprint $table) {

            // unique en codigo_inscripcion
            if (Schema::hasColumn('matriculas', 'codigo_inscripcion')) {
                $table->unique('codigo_inscripcion');
            }

            // tipo_matricula (enum con 4 opciones actualizadas)
            if (! Schema::hasColumn('matriculas', 'tipo_matricula')) {
                $table->enum('tipo_matricula', [
                    TipoMatricula::PROGRAMA->value,
                    TipoMatricula::FORMACION_CONTINUA->value,
                    TipoMatricula::CURSO->value,
                    TipoMatricula::MODULO->value,
                ])->default(TipoMatricula::PROGRAMA->value)->after('estado');
            }

            // id_curso nullable + FK
            if (! Schema::hasColumn('matriculas', 'id_curso')) {
                $table->unsignedBigInteger('id_curso')->nullable()->after('seccion_id');

                $table->foreign('id_curso')
                    ->references('id_curso')
                    ->on('cursos')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {

            // quitar FK id_curso y la columna
            if (Schema::hasColumn('matriculas', 'id_curso')) {
                try {
                    $table->dropForeign(['id_curso']);
                } catch (\Throwable $e) {
                    // ignoramos si no existe
                }

                $table->dropColumn('id_curso');
            }

            // quitar tipo_matricula
            if (Schema::hasColumn('matriculas', 'tipo_matricula')) {
                $table->dropColumn('tipo_matricula');
            }

            // quitar índice unique
            if (Schema::hasColumn('matriculas', 'codigo_inscripcion')) {
                $table->dropUnique(['codigo_inscripcion']);
            }
        });

        // renombrar de vuelta codigo_inscripcion -> codigo
        Schema::table('matriculas', function (Blueprint $table) {
            if (Schema::hasColumn('matriculas', 'codigo_inscripcion') &&
                ! Schema::hasColumn('matriculas', 'codigo')) {
                $table->renameColumn('codigo_inscripcion', 'codigo');
            }
        });
    }
};
