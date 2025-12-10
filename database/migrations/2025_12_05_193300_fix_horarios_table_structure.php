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
        Schema::table('horarios', function (Blueprint $table) {
            // Agregar columnas de tiempo
            $table->time('hora_inicio')->nullable()->after('dias');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
            
            // Eliminar la columna string 'horario'
            if (Schema::hasColumn('horarios', 'horario')) {
                $table->dropColumn('horario');
            }

            // Cambiar dias a JSON (en SQLite/Postgres json es soportado, 
            // pero si ya existe como string, mejor lo borramos y creamos o lo modificamos.
            // Para evitar líos de conversión, vamos a asumir que se puede alterar o 
            // simplemente asegurarnos que sea text/json.
            // En Laravel, ->change() a veces da problemas con enum/json.
            // Vamos a dejarlo como está si ya es string, pero idealmente debería ser json.
            // Si es string, el cast 'array' en el modelo funciona igual (serializa a json string).
        });
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('horario')->nullable();
            $table->dropColumn(['hora_inicio', 'hora_fin']);
        });
    }
};
