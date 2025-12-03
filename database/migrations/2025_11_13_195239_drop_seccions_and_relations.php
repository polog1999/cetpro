<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Quitar FK y columna seccion_id de MATRICULAS
        Schema::table('matriculas', function (Blueprint $table) {
            // Si la FK se creó con foreignId()->constrained()
            $table->dropForeign(['seccion_id']);    // FK
            $table->dropColumn('seccion_id');       // columna
        });

        // 2. Borrar tabla pivot ESTUDIANTE_SECCION (si existe)
        Schema::dropIfExists('estudiante_seccion');

        // 3. Borrar tabla SECCIONS
        Schema::dropIfExists('seccions');
    }

    public function down(): void
    {
        // Opcional: solo si quieres poder hacer rollback.
        // Puedes dejarlo vacío si ya no piensas volver a usar seccions.

        Schema::create('seccions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('docente_id')->constrained('docentes');
            $table->string('modalidad');
            $table->json('dias_estudio')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('turno');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('codigo')->nullable();
            $table->timestamps();
        });

        Schema::create('estudiante_seccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('seccion_id')->constrained('seccions')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('matriculas', function (Blueprint $table) {
            $table->foreignId('seccion_id')->nullable()->constrained('seccions');
        });
    }
};
