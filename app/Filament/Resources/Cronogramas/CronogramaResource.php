<?php

namespace App\Filament\Resources\Cronogramas;

use App\Filament\Resources\Cronogramas\Pages\CreateCronograma;
use App\Filament\Resources\Cronogramas\Pages\EditCronograma;
use App\Filament\Resources\Cronogramas\Pages\ListCronogramas;
use App\Filament\Resources\Cronogramas\Schemas\CronogramaForm;
use App\Filament\Resources\Cronogramas\Tables\CronogramasTable;
use App\Models\Cronograma;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;
// Estado de pagos viene desde Oracle (strings: 'Pendiente', 'Cancelado', 'Vencido', 'Anulado')

use App\Filament\Resources\Cronogramas\RelationManagers\PagosRelationManager; // El que creaste

use Filament\Infolists\Components\TextEntry;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Cronogramas\Pages\ViewCronograma;
use Filament\Support\Enums\TextSize;





class CronogramaResource extends Resource
{
    protected static ?string $model = Cronograma::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Pagos';

    public static function form(Schema $schema): Schema
    {
        return CronogramaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CronogramasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PagosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCronogramas::route('/'),
            'view' => ViewCronograma::route('/{record}'),
        ];
    }

    //Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('cronogramas') || false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        // ⚠️ CRÍTICO: Los cronogramas NUNCA deben eliminarse
        // Razones:
        // 1. Integridad financiera: Contienen historial de pagos
        // 2. Auditoría: Son evidencia de transacciones
        // 3. Legal: Requeridos para comprobantes y reportes oficiales
        
        // Si necesitas "eliminar" un cronograma, mejor:
        // - Marca la matrícula como ANULADA
        // - Los pagos quedarán registrados para auditoría
        
        return false;
    }

    public static function canDeleteAny(): bool
    {
        // No permitir eliminación masiva bajo ninguna circunstancia
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }


//Contar
    public static function getNavigationBadge(): ?string
    {
        // return static::getModel()::count(); <- Lógica de cuetan total anterior
        
        // Contar cronogramas que tienen al menos una cuota pendiente y vencida
        $cronogramasEnDeuda = static::getModel()::whereHas('pagos', function ($query) {
            $query->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                  ->where('fecha_vencimiento', '<', now());
        })->count();
        
        return $cronogramasEnDeuda > 0 ? (string) $cronogramasEnDeuda : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger'; // Badge rojo
    }

    //Infolist

    public static function infolist(Schema $schema): Schema
{
    return $schema
        ->components([
            
            
            Section::make('Estado del Cronograma')
                ->description('Resumen financiero y estado de deuda')
                ->icon('heroicon-m-banknotes')
                ->schema([
                Grid::make(4)->schema([
                            
                            // 1. Monto Total
                TextEntry::make('monto_total')
                ->label('Monto Total')
                ->money('PEN')
                ->size(TextSize::Large)
                ->weight('bold'),

                            // 2. Progreso de Cuotas (Calculado)
                TextEntry::make('progreso')
                                ->label('Cuotas Pagadas')
                                ->state(function (Cronograma $record): string {
                                    // Contamos cuántos pagos tienen estado CANCELADO (pagado en Oracle)
                                    $pagadas = $record->pagos()
                                        ->whereRaw("LOWER(estado) LIKE '%cancelado%'")
                                        ->count();
                                    
                                    return "{$pagadas} de {$record->num_cuotas}";
                                })
                                ->icon('heroicon-m-chart-pie'),

                            // 3. Estado de Deuda (Lógica personalizada)
                            TextEntry::make('estado_financiero')
                                ->label('Estado Actual')
                                ->badge()
                                ->state(function (Cronograma $record): string {
                                    // Buscamos si existe alguna cuota PENDIENTE y VENCIDA
                                    $esDeudor = $record->pagos()
                                        ->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                                        ->where('fecha_vencimiento', '<', now()) // Venció antes de hoy
                                        ->exists();

                                    return $esDeudor ? 'Deudor' : 'Al día';
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'Deudor' => 'danger',  // Rojo
                                    'Al día' => 'success', // Verde
                                    default => 'gray',
                                })
                                ->icon(fn (string $state): string => match ($state) {
                                    'Deudor' => 'heroicon-m-exclamation-circle',
                                    'Al día' => 'heroicon-m-check-badge',
                                    default => 'heroicon-m-question-mark-circle',
                                }),

                            // 4. Dato extra (Matrícula)
                            TextEntry::make('matricula.estudiante.nombre_completo')
                                ->label('Estudiante')
                                ->columnSpan(1),
                        ]),
                    ]),
            
        ]);
}
}
