<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar restricciones CHECK existentes
        DB::statement('ALTER TABLE matriculas DROP CONSTRAINT IF EXISTS matriculas_tipo_matricula_check');
        DB::statement('ALTER TABLE programas DROP CONSTRAINT IF EXISTS programas_tipo_programa_check');
        
        // 2. Actualizar valores de TipoMatricula en tabla matriculas
        DB::table('matriculas')
            ->where('tipo_matricula', 'Programa de estudio')
            ->update(['tipo_matricula' => 'Programa']);
        
        DB::table('matriculas')
            ->where('tipo_matricula', 'Programa de formación continua')
            ->update(['tipo_matricula' => 'Formación continua']);
        
        DB::table('matriculas')
            ->where('tipo_matricula', 'Curso libre')
            ->update(['tipo_matricula' => 'Curso']);
        
        // 3. Actualizar valores de TipoPrograma (ahora Tip) en tabla programas
        DB::table('programas')
            ->where('tipo_programa', 'Programa de estudio')
            ->update(['tipo_programa' => 'Programa']);
        
        DB::table('programas')
            ->where('tipo_programa', 'Programa de formación continua')
            ->update(['tipo_programa' => 'Formación continua']);
        
        // 4. Recrear restricciones CHECK con los nuevos valores
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT matriculas_tipo_matricula_check CHECK (tipo_matricula IN ('Programa', 'Formación continua', 'Curso', 'Módulo'))");
        DB::statement("ALTER TABLE programas ADD CONSTRAINT programas_tipo_programa_check CHECK (tipo_programa IN ('Programa', 'Formación continua'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Eliminar restricciones CHECK actuales
        DB::statement('ALTER TABLE matriculas DROP CONSTRAINT IF EXISTS matriculas_tipo_matricula_check');
        DB::statement('ALTER TABLE programas DROP CONSTRAINT IF EXISTS programas_tipo_programa_check');
        
        // 2. Revertir cambios de TipoMatricula
        DB::table('matriculas')
            ->where('tipo_matricula', 'Programa')
            ->update(['tipo_matricula' => 'Programa de estudio']);
        
        DB::table('matriculas')
            ->where('tipo_matricula', 'Formación continua')
            ->update(['tipo_matricula' => 'Programa de formación continua']);
        
        DB::table('matriculas')
            ->where('tipo_matricula', 'Curso')
            ->update(['tipo_matricula' => 'Curso libre']);
        
        // 3. Revertir cambios de TipoPrograma
        DB::table('programas')
            ->where('tipo_programa', 'Programa')
            ->update(['tipo_programa' => 'Programa de estudio']);
        
        DB::table('programas')
            ->where('tipo_programa', 'Formación continua')
            ->update(['tipo_programa' => 'Programa de formación continua']);
        
        // 4. Recrear restricciones CHECK con los valores antiguos
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT matriculas_tipo_matricula_check CHECK (tipo_matricula IN ('Programa de estudio', 'Programa de formación continua', 'Curso libre'))");
        DB::statement("ALTER TABLE programas ADD CONSTRAINT programas_tipo_programa_check CHECK (tipo_programa IN ('Programa de estudio', 'Programa de formación continua'))");
    }
};
