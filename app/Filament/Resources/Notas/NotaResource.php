<?php

namespace App\Filament\Resources\Notas;

use App\Filament\Resources\Notas\Pages\CreateNota;
use App\Filament\Resources\Notas\Pages\EditNota;
use App\Filament\Resources\Notas\Pages\ListNotas;
use App\Filament\Resources\Notas\Schemas\NotaForm;
use App\Filament\Resources\Notas\Tables\NotasTable;
use App\Models\Nota;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

class NotaResource extends Resource
{
    protected static ?string $model = Nota::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static string|UnitEnum|null $navigationGroup = 'Gestión estudiantil';
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return NotaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasTable::configure($table);
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
            'index' => ListNotas::route('/'),
            'create' => CreateNota::route('/create'),
            'edit' => EditNota::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        
        // Permitir acceso a admin, profesores, y usuarios con permiso a estudiantes
        return $user?->role?->es_admin || $user?->esProfesor() || $user?->canAccessResource('estudiantes') || false;
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->esProfesor() || $user?->canAccessResource('estudiantes') || false;
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->esProfesor() || $user?->canAccessResource('estudiantes') || false;
    }
}
