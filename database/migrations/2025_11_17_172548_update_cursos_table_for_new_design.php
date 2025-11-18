<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            // quitar aula (ahora estará en seccion)
            if (Schema::hasColumn('cursos', 'aula')) {
                $table->dropColumn('aula');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'aula')) {
                $table->string('aula')->nullable()->after('fecha_termino');
            }
        });
    }
};
