<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            // Grado de instrucción
            $table->enum(
                'grado_instruccion',
                array_map(fn ($case) => $case->value, GradoInstruccion::cases())
            )
            ->nullable()
            ->after('estado_civil');

            // Provincia (solo Lima)
            $table->enum(
                'provincia',
                array_map(fn ($case) => $case->value, Provincia::cases())
            )
            ->default(\App\Enums\Provincia::LIMA->value)
            ->after('direccion');

            // Distrito (Lima)
            $table->enum(
                'distrito',
                array_map(fn ($case) => $case->value, DistritoLima::cases())
            )
            ->nullable()
            ->after('provincia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['grado_instruccion', 'provincia', 'distrito']);
        });
    }
};
