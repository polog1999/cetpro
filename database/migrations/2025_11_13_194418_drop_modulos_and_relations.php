<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Quitar FK y columna modulo_id de SECCIONS (o SECCIONES)
        Schema::table('seccions', function (Blueprint $table) {
            // Si el nombre por defecto se creó, esto funciona:
            $table->dropForeign(['modulo_id']);
            $table->dropColumn('modulo_id');
        });

        // 2. Quitar FK y columna modulo_id de DOCENTES
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropForeign(['modulo_id']);
            $table->dropColumn('modulo_id');
        });

        // 3. Borrar tabla pivot DOCENTE_MODULO
        Schema::dropIfExists('docente_modulo');

        // 4. Borrar tabla MODULOS
        Schema::dropIfExists('modulos');
    }

    public function down(): void
    {
        // Opcional: recrear tablas/columnas por si haces rollback.
        // Solo si te interesa, si no puedes dejarlo vacío.

        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('costo', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('docente_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('modulo_id')->constrained('modulos')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('docentes', function (Blueprint $table) {
            $table->foreignId('modulo_id')->nullable()->constrained('modulos');
        });

        Schema::table('seccions', function (Blueprint $table) {
            $table->foreignId('modulo_id')->nullable()->constrained('modulos');
        });
    }
};
