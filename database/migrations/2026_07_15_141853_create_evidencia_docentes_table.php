<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evidencias_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('horario_id')->constrained('horarios')->references('id_horario')->onDelete('cascade');

            // Tipo de documento (Enum)
            $table->string('tipo_documento'); // Acta, Nómina, Portafolio, Informe Final, etc.

            $table->string('archivo_path'); // Ruta del PDF o ZIP en storage
            $table->string('estado')->default('Pendiente'); // Pendiente, Aprobado, Observado
            $table->text('observaciones')->nullable(); // En caso la directora observe el documento

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidencia_docentes');
    }
};
