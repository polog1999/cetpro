<?php

namespace App\Filament\Resources\Programas;

use App\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Filament\Resources\Programas\Pages\EditPrograma;
use App\Filament\Resources\Programas\Pages\ListProgramas;
use App\Filament\Resources\Programas\Schemas\ProgramaForm;
use App\Filament\Resources\Programas\Tables\ProgramasTable;
use App\Models\Programa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Enums\Rol;
use UnitEnum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\Programas\RelationManagers\CursosRelationManager;
use App\Filament\Resources\Programas\Pages\ViewPrograma;
use App\Enums\TipoPrograma;

class ProgramaResource extends Resource
{
    protected static ?string $model = Programa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión Académica';

    public static function form(Schema $schema): Schema
    {
        return ProgramaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProgramas::route('/'),
            'create' => CreatePrograma::route('/create'),
            'edit'   => EditPrograma::route('/{record}/edit'),
            'view'   => ViewPrograma::route('/{record}'),
        ];
    }

    // Relaciones (para ver/agregar cursos desde el programa)
    public static function getRelations(): array
    {
        return [
            CursosRelationManager::class,
        ];
    }

    // Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('ProgramaResource') || false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('nombre_programa')
                ->label('Nombre'),

            TextEntry::make('tipo_programa')
                ->label('Tipo de programa')
                ->formatStateUsing(
                    fn (?TipoPrograma $state) => $state?->getLabel()
                ),

            TextEntry::make('duracion')
                ->label('Duración en meses'),

            TextEntry::make('num_cursos')
                ->label('Número de cursos'),

            TextEntry::make('especialidad.nombre_especialidad')
                ->label('Especialidad'),
        ]);
    }
}
