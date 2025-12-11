<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permiso;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sección: Información Básica
                Section::make('Información del Rol')
                    ->description('Configure el nombre y características principales del rol')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del Rol')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ej: Secretaría, Tesorería, Coordinador')
                            ->columnSpan(2),

                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->placeholder('Descripción breve del rol y sus responsabilidades')
                            ->columnSpan(2),

                        Toggle::make('es_admin')
                            ->label('Rol de Administrador')
                            ->helperText('Los administradores tienen acceso completo al sistema sin restricciones')
                            ->live()
                            ->inline(false)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Mensaje cuando es admin
                Placeholder::make('admin_notice')
                    ->label('')
                    ->content('ℹ️ Los roles de administrador tienen acceso total. No es necesario configurar permisos individuales.')
                    ->visible(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),

                // Sección: Permisos
                Section::make('Permisos del Rol')
                    ->description('Active los permisos que tendrá este rol en el sistema')
                    ->schema([
                        // Gestión Estudiantil
                        Section::make('👥 Gestión Estudiantil')
                            ->description('Permisos relacionados con estudiantes y matrículas')
                            ->schema(
                                self::getPermisosToggles('Gestión Estudiantil')
                            )
                            ->columns(2)
                            ->collapsed(),

                        // Gestión Académica
                        Section::make('📚 Gestión Académica')
                            ->description('Permisos para programas, cursos y horarios')
                            ->schema(
                                self::getPermisosToggles('Gestión Académica')
                            )
                            ->columns(2)
                            ->collapsed(),

                        // Gestión Administrativa
                        Section::make('🏢 Gestión Administrativa')
                            ->description('Permisos administrativos y de personal')
                            ->schema(
                                self::getPermisosToggles('Gestión Administrativa')
                            )
                            ->columns(2)
                            ->collapsed(),

                        // Gestión Financiera
                        Section::make('💰 Gestión Financiera')
                            ->description('Permisos para pagos, cronogramas y finanzas')
                            ->schema(
                                self::getPermisosToggles('Gestión Financiera')
                            )
                            ->columns(2)
                            ->collapsed(),

                        // Gestión de Usuarios
                        Section::make('👤 Gestión de Usuarios')
                            ->description('Permisos para usuarios, roles y permisos')
                            ->schema(
                                self::getPermisosToggles('Gestión de Usuarios')
                            )
                            ->columns(2)
                            ->collapsed(),

                        // Gestión de Pagos (si existe como grupo separado)
                        Section::make('🔐 Gestión de Pagos')
                            ->description('Permisos específicos de pagos y evidencias')
                            ->schema(
                                self::getPermisosToggles('Gestión de Pagos')
                            )
                            ->columns(2)
                            ->collapsed()
                            ->visible(fn () => Permiso::where('grupo', 'Gestión de Pagos')->exists()),
                    ])
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    /**
     * Genera los toggles para un grupo de permisos
     *
     * @param string $grupo
     * @return array
     */
    protected static function getPermisosToggles(string $grupo): array
    {
        $permisos = Permiso::where('grupo', $grupo)
            ->orderBy('nombre')
            ->get();

        if ($permisos->isEmpty()) {
            return [
                Placeholder::make("no_permisos_{$grupo}")
                    ->label('')
                    ->content('No hay permisos configurados para este grupo.')
                    ->columnSpanFull(),
            ];
        }

        $toggles = [];

        foreach ($permisos as $permiso) {
            $fieldName = "permiso_{$permiso->id}";
            
            $toggles[] = Toggle::make($fieldName)
                ->label($permiso->nombre)
                ->helperText($permiso->descripcion ?? '')
                ->inline(false)
                ->default(false)
                ->live();
        }

        return $toggles;
    }

    /**
     * Obtiene todos los IDs de permisos seleccionados desde los toggles
     * Este método se puede usar en el recurso para procesar el guardado
     *
     * @param array $data
     * @return array
     */
    public static function extractPermisosFromToggles(array $data): array
    {
        $permisosIds = [];

        foreach ($data as $key => $value) {
            // Buscar campos que empiecen con 'permiso_' y estén activados
            if (str_starts_with($key, 'permiso_') && $value === true) {
                // Extraer el ID del permiso del nombre del campo
                $permisoId = (int) str_replace('permiso_', '', $key);
                $permisosIds[] = $permisoId;
            }
        }

        return $permisosIds;
    }

    /**
     * Prepara los datos del formulario con los toggles activados
     * Este método se puede usar en el recurso al cargar un rol existente
     *
     * @param \App\Models\Role $role
     * @return array
     */
    public static function fillPermisosToggles($role): array
    {
        $data = [];

        if ($role->permisos) {
            foreach ($role->permisos as $permiso) {
                $data["permiso_{$permiso->id}"] = true;
            }
        }

        return $data;
    }
}
