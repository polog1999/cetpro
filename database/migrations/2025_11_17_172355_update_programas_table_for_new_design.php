<?php

use App\Enums\TipoPrograma;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            // num_componentes -> num_cursos
            if (Schema::hasColumn('programas', 'num_componentes')) {
                $table->renameColumn('num_componentes', 'num_cursos');
            }

            // tipo_programa (enum)
            if (! Schema::hasColumn('programas', 'tipo_programa')) {
                $table->enum('tipo_programa', [
                    TipoPrograma::PROGRAMA_ESTUDIO->value,
                    TipoPrograma::FORMACION_CONTINUA->value,
                ])->default(TipoPrograma::PROGRAMA_ESTUDIO->value)->after('id_rubro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            if (Schema::hasColumn('programas', 'tipo_programa')) {
                $table->dropColumn('tipo_programa');
            }

            if (Schema::hasColumn('programas', 'num_cursos')) {
                $table->renameColumn('num_cursos', 'num_componentes');
            }
        });
    }
};
