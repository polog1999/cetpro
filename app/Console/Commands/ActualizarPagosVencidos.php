<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronogramaService;

/**
 * Comando para actualizar pagos vencidos.
 * 
 * NOTA: El estado de los pagos ahora viene desde Oracle.
 * Este comando sincroniza el estado local basado en fecha de vencimiento.
 */
class ActualizarPagosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagos:actualizar-vencidos
                            {--dry-run : Ejecutar en modo simulación sin aplicar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado de los pagos pendientes que ya han vencido';

    protected CronogramaService $cronogramaService;

    /**
     * Create a new command instance.
     */
    public function __construct(CronogramaService $cronogramaService)
    {
        parent::__construct();
        $this->cronogramaService = $cronogramaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando actualización de pagos vencidos...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Modo SIMULACIÓN activado - No se aplicarán cambios');
            $this->newLine();
        }

        try {
            // Obtener pagos que se van a actualizar (estado pendiente desde Oracle)
            $pagosVencidos = \App\Models\Pago::whereRaw("LOWER(estado) LIKE '%pendiente%'")
                ->where('fecha_vencimiento', '<', now()->startOfDay())
                ->get();

            $totalPagos = $pagosVencidos->count();

            if ($totalPagos === 0) {
                $this->info('✓ No hay pagos pendientes vencidos para actualizar');
                return self::SUCCESS;
            }

            $this->info("📊 Se encontraron {$totalPagos} pagos pendientes vencidos:");
            $this->newLine();

            // Mostrar tabla con los pagos a actualizar
            $table = $pagosVencidos->map(function ($pago) {
                return [
                    'ID' => $pago->id,
                    'Código' => $pago->codigo ?? 'N/A',
                    'Estudiante' => $pago->cronograma?->matricula?->estudiante?->nombre_completo ?? 'N/A',
                    'Monto' => 'S/ ' . number_format($pago->monto, 2),
                    'Vencimiento' => $pago->fecha_vencimiento?->format('d/m/Y') ?? 'N/A',
                    'Días atraso' => $pago->diasRetraso(),
                ];
            });

            $this->table(
                ['ID', 'Código', 'Estudiante', 'Monto', 'Vencimiento', 'Días atraso'],
                $table
            );

            $this->newLine();

            if (!$dryRun) {
                if (! $this->confirm('¿Desea continuar con la actualización?', true)) {
                    $this->warn('❌ Operación cancelada por el usuario');
                    return self::FAILURE;
                }

                $actualizados = $this->cronogramaService->actualizarPagosVencidos();

                $this->newLine();
                $this->info("✓ Se actualizaron {$actualizados} pagos a estado VENCIDO");
                
                // Actualizar estados de matrículas afectadas
                $this->info('🔄 Actualizando estados de matrículas afectadas...');
                
                $matriculasAfectadas = \App\Models\Matricula::whereHas('cronograma.pagos', function ($query) {
                    $query->whereRaw("LOWER(estado) LIKE '%vencido%'");
                })->get();

                foreach ($matriculasAfectadas as $matricula) {
                    $matricula->actualizarEstadoSegunCronograma();
                }

                $this->info("✓ Se actualizaron {$matriculasAfectadas->count()} matrículas");
            } else {
                $this->info("✓ En modo real se actualizarían {$totalPagos} pagos");
            }

            $this->newLine();
            $this->info('✅ Proceso completado exitosamente');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error durante la actualización: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
