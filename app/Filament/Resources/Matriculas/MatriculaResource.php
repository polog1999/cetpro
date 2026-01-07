<?php

namespace App\Filament\Resources\Matriculas;

use App\Filament\Resources\Matriculas\Pages\CreateMatricula;
use App\Filament\Resources\Matriculas\Pages\EditMatricula;
use App\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Filament\Resources\Matriculas\Pages\MatriculaMasiva;
use App\Filament\Resources\Matriculas\Schemas\MatriculaForm;
use App\Filament\Resources\Matriculas\Tables\MatriculasTable;
use App\Models\Matricula;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use UnitEnum;

class MatriculaResource extends Resource
{
    protected static ?string $model = Matricula::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    //protected static string|UnitEnum|null $navigationGroup = 'Gestión de Matrícula';

    public static function form(Schema $schema): Schema
    {
        return MatriculaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatriculasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatriculas::route('/'),
            'create' => CreateMatricula::route('/create'),
            'edit' => EditMatricula::route('/{record}/edit'),
            'matricula-masiva' => MatriculaMasiva::route('/matricula-masiva'),
        ];
    }

    //Contar
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    // Verificación de permisos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('matriculas') || false;
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

    /**
     * Habilita la búsqueda global para Matrículas.
     * Permite buscar por código de inscripción o nombre del estudiante.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo_inscripcion', 'estudiante.nombres', 'estudiante.apellido_paterno', 'estudiante.nro_documento'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->codigo_inscripcion;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Estudiante' => $record->estudiante?->nombres . ' ' . $record->estudiante?->apellido_paterno,
        ];
    }
}
