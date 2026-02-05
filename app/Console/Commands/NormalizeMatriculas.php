<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;
use Illuminate\Support\Facades\DB;

class NormalizeMatriculas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matriculas:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normaliza los códigos de inscripción al formato YYYYRRRR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando normalización de códigos de matrícula...');

        $matriculas = Matricula::orderBy('created_at')->get();
        $contadores = []; 
        $count = 0;

        DB::transaction(function () use ($matriculas, &$contadores, &$count) {
            foreach ($matriculas as $m) {
                // Si no tiene fecha de creación, usar el año actual o un default
                $year = $m->created_at ? $m->created_at->year : now()->year;
                
                // Inicializar contador para el año si no existe
                if (!isset($contadores[$year])) {
                    $contadores[$year] = 0;
                }
                
                // Generar nuevo código: YYYY + RRRR (0000, 0001...)
                $nuevoCodigo = $year . str_pad($contadores[$year], 4, '0', STR_PAD_LEFT);
                
                $this->line("Actualizando ID {$m->id}: {$m->codigo_inscripcion} -> {$nuevoCodigo}");

                // Actualizar sin disparar eventos
                $m->codigo_inscripcion = $nuevoCodigo;
                $m->saveQuietly();
                
                // Incrementar contador del año
                $contadores[$year]++;
                $count++;
            }
        });

        $this->info("Proceso terminado. Se actualizaron {$count} matrículas.");
        return 0;
    }
}
