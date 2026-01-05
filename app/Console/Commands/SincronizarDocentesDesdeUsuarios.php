<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use App\Models\Docente;
use Exception;

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
            ->with('empleado', 'role')
            ->get();

        if ($usuariosProfesor->isEmpty()) {
            $this->info('✓ No hay usuarios profesores sin docente asociado.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$usuariosProfesor->count()} usuario(s) profesor(es) sin docente asociado.\n");

        $barProgress = $this->output->createProgressBar($usuariosProfesor->count());
        $barProgress->start();

        $successful = 0;
        $failed = 0;

        foreach ($usuariosProfesor as $usuario) {
            try {
                if (!$usuario->empleado) {
                    $this->error("\n✗ Usuario '{$usuario->usuario}' - No tiene empleado asociado.");
                    $failed++;
                    $barProgress->advance();
                    continue;
                }

                // Validar datos requeridos del empleado
                if (!$usuario->empleado->nombre || !$usuario->empleado->apellido_paterno) {
                    $this->error("\n✗ Usuario '{$usuario->usuario}' - El empleado no tiene nombre o apellido paterno.");
                    $failed++;
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

                $successful++;
            } catch (Exception $e) {
                $this->error("\n✗ Error procesando usuario '{$usuario->usuario}': {$e->getMessage()}");
                $failed++;
            }

            $barProgress->advance();
        }

        $barProgress->finish();
        $this->newLine(2);

        // Resumen final
        $this->info("═══════════════════════════════════════════");
        $this->info("  RESULTADO DE LA SINCRONIZACIÓN");
        $this->info("═══════════════════════════════════════════");
        $this->line("✓ Docentes creados exitosamente: <fg=green>{$successful}</fg=green>");
        if ($failed > 0) {
            $this->line("✗ Usuarios con errores: <fg=red>{$failed}</fg=red>");
        }
        $this->line("Total procesados: " . ($successful + $failed));
        $this->info("═══════════════════════════════════════════\n");

        return Command::SUCCESS;
    }
}
