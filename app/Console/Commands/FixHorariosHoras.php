<?php

namespace App\Console\Commands;

use App\Models\Horario;
use Illuminate\Console\Command;

class FixHorariosHoras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:horarios-horas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los horarios que tienen hora_inicio y hora_fin nulos con valores predeterminados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de horarios...');

        // Obtener horarios sin hora_inicio o hora_fin
        $horarios = Horario::whereNull('hora_inicio')
            ->orWhereNull('hora_fin')
            ->get();

        if ($horarios->isEmpty()) {
            $this->info('No hay horarios para actualizar.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$horarios->count()} horarios para actualizar.");

        $bar = $this->output->createProgressBar($horarios->count());
        $bar->start();

        foreach ($horarios as $horario) {
            // Asignar horas predeterminadas basadas en el turno
            $horaInicio = '08:00:00';
            $horaFin = '13:00:00';

            if ($horario->turno) {
                $turnoValue = is_object($horario->turno) ? $horario->turno->value : $horario->turno;
                
                switch (strtolower($turnoValue)) {
                    case 'mañana':
                    case 'manana':
                        $horaInicio = '08:00:00';
                        $horaFin = '13:00:00';
                        break;
                    case 'tarde':
                        $horaInicio = '14:00:00';
                        $horaFin = '19:00:00';
                        break;
                    case 'noche':
                        $horaInicio = '19:00:00';
                        $horaFin = '22:00:00';
                        break;
                    default:
                        $horaInicio = '08:00:00';
                        $horaFin = '13:00:00';
                }
            }

            $horario->update([
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Actualización completada exitosamente.');

        return Command::SUCCESS;
    }
}
