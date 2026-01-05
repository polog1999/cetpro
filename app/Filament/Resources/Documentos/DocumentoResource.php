<?php

namespace App\Filament\Resources\Documentos;

use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Models\Estudiante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use UnitEnum;

class DocumentoResource extends Resource
{
    protected static ?string $model = Estudiante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Matrícula';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documentos';

    protected static ?string $slug = 'documentos';

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
        ];
    }

    // Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('documentos') || false;
    }

    public static function canCreate(): bool
    {
        return false; // No se pueden crear estudiantes desde aquí
    }

    public static function canEdit($record): bool
    {
        return false; // No se puede editar estudiantes desde aquí
    }

    public static function canDelete($record): bool
    {
        return false; // No se puede eliminar estudiantes desde aquí
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
