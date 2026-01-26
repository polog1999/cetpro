<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aumenta el límite del campo nombre_curso de 150 a 500 caracteres
     * para permitir nombres de cursos más descriptivos.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('nombre_curso', 500)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('nombre_curso', 150)->change();
        });
    }
};
