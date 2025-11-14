<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renombra la tabla antigua a la nueva
        Schema::rename('oferta_academica', 'seccion');
        // o 'secciones', como prefieras llamarla en la BD
    }

    public function down(): void
    {
        // Reverso del cambio
        Schema::rename('seccion', 'oferta_academica');
        // o 'secciones' -> 'oferta_academica' si usaste plural
    }
};
