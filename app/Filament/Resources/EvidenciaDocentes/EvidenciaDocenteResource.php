<?php

namespace App\Filament\Resources\EvidenciaDocentes;

use App\Filament\Resources\EvidenciaDocentes\Pages\CreateEvidenciaDocente;
use App\Filament\Resources\EvidenciaDocentes\Pages\EditEvidenciaDocente;
use App\Filament\Resources\EvidenciaDocentes\Pages\ListEvidenciaDocentes;
use App\Filament\Resources\EvidenciaDocentes\Schemas\EvidenciaDocenteForm;
use App\Filament\Resources\EvidenciaDocentes\Tables\EvidenciaDocentesTable;
use App\Models\EvidenciaDocente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EvidenciaDocenteResource extends Resource
{
    protected static ?string $model = EvidenciaDocente::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|UnitEnum|null $navigationGroup = 'Gestión estudiantil';

    protected static ?string $recordTitleAttribute = 'yes';

    public static function form(Schema $schema): Schema
    {
        return EvidenciaDocenteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EvidenciaDocentesTable::configure($table);
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
            'index' => ListEvidenciaDocentes::route('/'),
            'create' => CreateEvidenciaDocente::route('/create'),
            'edit' => EditEvidenciaDocente::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Si es Directora o Administrador, ve las evidencias de todos los profesores
        if ($user->esAdmin() || $user->esDirectora()) {
            return $query;
        }

        // Si es docente ordinario, solo ve y gestiona sus propios archivos
        if ($user->docente_id) {
            return $query->where('docente_id', $user->docente_id);
        }

        // Bloqueo preventivo por defecto
        return $query->whereRaw('1 = 0');
    }
    public static function canCreate(): bool
{
    $user = auth()->user();
    
    // Si el usuario es Directora, NO puede crear registros (retorna false)
    if ($user && $user->esDirectora()) {
        return false;
    }

    return true; // Administradores y Docentes sí pueden crear
}
}
