<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Docente;

class SincronizarDocentesDesdeUsuarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:docentes';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sincroniza usuarios profesores sin docente asociado, creando automáticamente sus registros en la tabla docentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de docentes...');

        // Obtener usuarios de tipo profesor sin docente asociado
        $usuariosProfesor = Usuario::whereHas('role', function ($query) {
            $query->where('nombre', 'Profesor');
        })
            ->whereNull('docente_id')
            ->with('empleado')
            ->get();

        if ($usuariosProfesor->isEmpty()) {
            $this->info('No hay usuarios profesores sin docente asociado.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$usuariosProfesor->count()} usuario(s) profesor(es) sin docente asociado.");

        $barProgress = $this->output->createProgressBar($usuariosProfesor->count());

        foreach ($usuariosProfesor as $usuario) {
            if (!$usuario->empleado) {
                $this->warn("Usuario {$usuario->usuario} no tiene empleado asociado. Saltando...");
                $barProgress->advance();
                continue;
            }

            // Crear docente con los datos del empleado
            $docente = Docente::create([
                'tipo_documento' => $usuario->empleado->tipo_documento,
                'nro_documento' => $usuario->empleado->num_documento,
                'nombres' => $usuario->empleado->nombre,
                'apellido_paterno' => $usuario->empleado->apellido_paterno,
                'apellido_materno' => $usuario->empleado->apellido_materno ?? '',
            ]);

            // Asignar el docente al usuario
            $usuario->update(['docente_id' => $docente->id]);

            $barProgress->advance();
        }

        $barProgress->finish();
        $this->newLine();
        $this->info('✓ Sincronización completada exitosamente.');

        return Command::SUCCESS;
    }
}
