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

use App\Filament\Resources\Cronogramas\RelationManagers\PagosRelationManager; // El que creaste

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Cronogramas\Pages\ViewCronograma;





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

        // Si guardas el rol como enum:
        return $user?->rol === Rol::ADMIN;

        // Si guardas el rol como string:
        // return $user?->rol === 'admin';
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }


//Contar
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    //Infolist

    public static function infolist(Schema $schema): Schema
{
    return $schema
        ->components([
            Section::make('Información de la Sección')
            
                ->description('Detalles del aula donde está este cronograma')
                ->icon('heroicon-m-building-library')
                ->schema([
                    Grid::make(3)->schema([
                        // Navegamos: Cronograma -> Matricula -> Seccion -> Campo
                        TextEntry::make('matricula.seccion.nombre_seccion')
                            ->label('Sección')
                            ->weight('bold'),
                        
                        TextEntry::make('matricula.seccion.aula')
                            ->label('Aula'),

                        TextEntry::make('matricula.seccion.turno')
                            ->label('Turno')
                            ->badge(),
                    ])
                ])
        ]);
}
}
