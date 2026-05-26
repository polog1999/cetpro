<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Try to drop the check constraint in PostgreSQL
        try {
            DB::statement('ALTER TABLE matriculas DROP CONSTRAINT matriculas_tipo_matricula_check');
        } catch (\Exception $e) {
            // Constraint might not exist or not PostgreSQL
        }

        // Try to modify the column if it's MySQL enum
        try {
            DB::statement("ALTER TABLE matriculas MODIFY COLUMN tipo_matricula ENUM('Programa', 'Formación continua', 'Curso', 'Módulo', 'Unidad') DEFAULT 'Programa'");
        } catch (\Exception $e) {
            // Not MySQL or enum modification failed
        }
        
        // As a fallback, ensure the column is a string type
        try {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->string('tipo_matricula')->change();
            });
        } catch (\Exception $e) {
            // Ignore if doctrine/dbal is not installed
        }
    }

    public function down(): void
    {
        // 
    }
};
