<?php

namespace App\Filament\Resources\Horarios;

use App\Filament\Resources\Horarios\Pages\CreateHorario;
use App\Filament\Resources\Horarios\Pages\EditHorario;
use App\Filament\Resources\Horarios\Pages\ListHorarios;
use App\Filament\Resources\Horarios\Pages\VerAlumnos;
use App\Filament\Resources\Horarios\Schemas\HorarioForm;
use App\Filament\Resources\Horarios\Tables\HorariosTable;
use App\Models\Horario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class HorarioResource extends Resource
{
    protected static ?string $model = Horario::class;
    
    protected static ?string $pluralModelLabel = 'Horarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión Académica';

    // Texto que aparece en el menú lateral (navegación)
    protected static ?string $navigationLabel = 'Horarios';
    
    public static function getModelLabel(): string
    {
        return 'Horario';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Horarios';
    }

    public static function form(Schema $schema): Schema
    {
        return HorarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HorariosTable::configure($table);
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
            'index'       => ListHorarios::route('/'),
            'create'      => CreateHorario::route('/create'),
            'edit'        => EditHorario::route('/{record}/edit'),
            'ver-alumnos' => VerAlumnos::route('/{record}/ver-alumnos'),
        ];
    }

    // Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('horarios') || false;
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || false;
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || false;
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();
        
        // Solo admin puede eliminar
        if (!($user?->role?->es_admin ?? false)) {
            return false;
        }
        
        // No permitir eliminación si el horario tiene matrículas
        return !$record->matriculas()->exists();
    }

    public static function canDeleteAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    // Contar
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
