<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empleado_id')
                ->constrained('empleados')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('usuario')->unique();
            $table->string('password');

            // rol almacenado como string y casteado al enum Rol
            // nullable temporalmente para migración al nuevo sistema de roles
            $table->string('rol', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};



