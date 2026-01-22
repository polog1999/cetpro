<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero intentamos eliminar el constraint existente
        // PostgreSQL le da nombre automático como tabela_columna_check si no se especifica
        // En el error vimos que se llama: matriculas_tipo_matricula_check
        
        DB::statement("ALTER TABLE matriculas DROP CONSTRAINT IF EXISTS matriculas_tipo_matricula_check");

        // Agregamos el nuevo constraint con 'Unidad'
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT matriculas_tipo_matricula_check 
            CHECK (tipo_matricula::text IN ('Programa', 'Formación continua', 'Curso', 'Módulo', 'Unidad'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE matriculas DROP CONSTRAINT IF EXISTS matriculas_tipo_matricula_check");

        // Regresamos al estado anterior sin 'Unidad'
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT matriculas_tipo_matricula_check 
            CHECK (tipo_matricula::text IN ('Programa', 'Formación continua', 'Curso', 'Módulo'))");
    }
};
