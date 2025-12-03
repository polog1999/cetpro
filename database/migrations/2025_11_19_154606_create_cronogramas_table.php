<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cronogramas', function (Blueprint $table) {
            $table->id(); // id

            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();

            $table->unsignedInteger('num_cuotas');
            $table->decimal('monto_total', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronogramas');
    }
};
