<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Eliminar id_curso si existe
        if (Schema::hasColumn('seccion', 'id_curso')) {
            try {
                DB::transaction(function () {
                    Schema::table('seccion', function (Blueprint $table) {
                        $table->dropForeign(['id_curso']);
                    });
                });
            } catch (\Exception $e) {
                try {
                    DB::transaction(function () {
                        Schema::table('seccion', function (Blueprint $table) {
                            $table->dropForeign('oferta_academica_id_curso_foreign');
                        });
                    });
                } catch (\Exception $e2) {}
            }

            Schema::table('seccion', function (Blueprint $table) {
                $table->dropColumn('id_curso');
            });
        }

        Schema::table('seccion', function (Blueprint $table) {
            // 2) Eliminar tipo_oferta
            if (Schema::hasColumn('seccion', 'tipo_oferta')) {
                $table->dropColumn('tipo_oferta');
            }

            // 3) Renombrar docente_id -> id_docente
            if (Schema::hasColumn('seccion', 'docente_id') &&
                ! Schema::hasColumn('seccion', 'id_docente')) {
                $table->renameColumn('docente_id', 'id_docente');
            }

            // 4) Agregar aula
            if (! Schema::hasColumn('seccion', 'aula')) {
                $table->string('aula')->nullable()->after('id_docente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seccion', function (Blueprint $table) {

            // quitar aula
            if (Schema::hasColumn('seccion', 'aula')) {
                $table->dropColumn('aula');
            }

            // renombrar id_docente -> docente_id
            if (Schema::hasColumn('seccion', 'id_docente') &&
                ! Schema::hasColumn('seccion', 'docente_id')) {
                $table->renameColumn('id_docente', 'docente_id');
            }

            // volver a crear tipo_oferta (si te sirve en el down)
            if (! Schema::hasColumn('seccion', 'tipo_oferta')) {
                $table->string('tipo_oferta')->nullable();
            }

            // volver a crear id_curso (sin FK para simplificar el down)
            if (! Schema::hasColumn('seccion', 'id_curso')) {
                $table->unsignedBigInteger('id_curso')->nullable();
            }
        });
    }
};
