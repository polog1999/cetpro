<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\Page;
use App\Models\Pago;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Exception;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class ReasignarPagos extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = PagoResource::class;

    protected string $view = 'filament.resources.pagos.pages.reasignar-pagos';

    protected static bool $shouldRegisterNavigation = false;

    public $liquidacion_1 = '';
    public $liquidacion_2 = '';

    public function mount(): void
    {
        $user = Filament::auth()->user();
        
        if (!$user?->esAdmin()) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
    }

    public function getPagoData($liquidacion)
    {
        if (empty($liquidacion)) return null;

        $pago = Pago::with(['cronograma.matricula.horario.programa', 'cronograma.matricula.curso', 'cronograma.matricula.unidad'])
            ->where('num_liquidacion', $liquidacion)->first();

        if (!$pago) return null;

        $correspondeA = 'Sin detalle';
        if ($pago->cronograma && $pago->cronograma->matricula) {
            $matricula = $pago->cronograma->matricula;
            $tipo = $matricula->tipo_matricula?->value ?? $matricula->tipo_matricula ?? '';
            
            if ($tipo === 'Unidad' && $matricula->unidad) {
                $correspondeA = $matricula->unidad->nombre_unidad;
            } elseif (in_array($tipo, ['Curso', 'Módulo']) && $matricula->curso) {
                $correspondeA = $matricula->curso->nombre_curso;
            } elseif (in_array($tipo, ['Programa', 'Formación continua']) && $matricula->horario?->programa) {
                $correspondeA = $matricula->horario->programa->nombre_programa;
            }
        }

        return [
            'id' => $pago->id,
            'estado' => $pago->estado,
            'monto' => $pago->monto,
            'corresponde_a' => $correspondeA
        ];
    }

    public function getPago1Property()
    {
        return $this->getPagoData($this->liquidacion_1);
    }

    public function getPago2Property()
    {
        return $this->getPagoData($this->liquidacion_2);
    }

    public function intercambiar()
    {
        if (empty($this->liquidacion_1) || empty($this->liquidacion_2)) {
            Notification::make()->title('Debe ingresar ambos números de liquidación')->danger()->send();
            return;
        }

        if ($this->liquidacion_1 == $this->liquidacion_2) {
            Notification::make()->title('Los números de liquidación deben ser diferentes')->danger()->send();
            return;
        }

        $pago1 = Pago::where('num_liquidacion', $this->liquidacion_1)->first();
        $pago2 = Pago::where('num_liquidacion', $this->liquidacion_2)->first();

        if (!$pago1 || !$pago2) {
            Notification::make()->title('Uno o ambos pagos no fueron encontrados')->danger()->send();
            return;
        }

        if ($pago1->monto != $pago2->monto) {
            Notification::make()->title('Los montos de ambos pagos deben ser exactamente iguales')
                ->body('Pago 1: S/.' . number_format($pago1->monto, 2) . ' | Pago 2: S/.' . number_format($pago2->monto, 2))
                ->danger()->send();
            return;
        }

        try {
            DB::transaction(function () use ($pago1, $pago2) {
                // Hacer el intercambio usando un valor temporal
                $tempLiquidacion = 'TEMP_' . $pago1->id . '_' . time();
                
                $pago1->update(['num_liquidacion' => $tempLiquidacion]);
                $pago2->update(['num_liquidacion' => $this->liquidacion_1]);
                $pago1->update(['num_liquidacion' => $this->liquidacion_2]);
            });

            // Limpiar los campos para que se relacione con el nuevo estado visual
            $temp = $this->liquidacion_1;
            $this->liquidacion_1 = $this->liquidacion_2;
            $this->liquidacion_2 = $temp;

            Notification::make()
                ->title('Éxito')
                ->body('Se han intercambiado los números de liquidación correctamente.')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Error al intercambiar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
