<?php

namespace App\Filament\Resources\Estudiantes;

use App\Filament\Resources\Estudiantes\Pages\CreateEstudiante;
use App\Filament\Resources\Estudiantes\Pages\EditEstudiante;
use App\Filament\Resources\Estudiantes\Pages\ListEstudiantes;
use App\Filament\Resources\Estudiantes\Pages\ViewEstudiante;
use App\Filament\Resources\Estudiantes\Schemas\EstudianteForm;
use App\Filament\Resources\Estudiantes\Tables\EstudiantesTable;
use App\Models\Estudiante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\Estudiantes\RelationManagers;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class EstudianteResource extends Resource
{
    protected static ?string $model = Estudiante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión estudiantil';

    public static function form(Schema $schema): Schema
    {
        return EstudianteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstudiantesTable::configure($table);
    }


    public static function getRelations(): array
    {
        return [
            RelationManagers\MatriculasRelationManager::class,
            RelationManagers\NotasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstudiantes::route('/'),
            'create' => CreateEstudiante::route('/create'),
            'view' => ViewEstudiante::route('/{record}'),
            'edit' => EditEstudiante::route('/{record}/edit'),
        ];
    }

    //Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        
        // Profesores no pueden acceder a estudiantes
        if ($user?->esProfesor()) {
            return false;
        }
        
        return $user?->role?->es_admin || $user?->canAccessResource('estudiantes') || false;
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('estudiantes') || false;
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('estudiantes') || false;
    }

    public static function canDelete($record): bool
    {
        if (!static::canViewAny()) {
            return false;
        }
        
        // No permitir eliminación si el estudiante tiene matrículas
        return !$record->matriculas()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }


//Contar
    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

    /**
     * Habilita la búsqueda global para Estudiantes.
     * Permite buscar por documento, nombres o apellidos desde la barra superior.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nro_documento', 'nombres', 'apellido_paterno', 'apellido_materno'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->nombres . ' ' . $record->apellido_paterno . ' ' . $record->apellido_materno;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Documento' => $record->nro_documento,
        ];
    }
}
