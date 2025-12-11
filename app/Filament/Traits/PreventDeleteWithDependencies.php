<?php

namespace App\Filament\Traits;

use Filament\Notifications\Notification;

/**
 * Trait para prevenir eliminación de registros con dependencias
 * 
 * Uso:
 * 1. Usar el trait en la clase Table
 * 2. Llamar a preventDeleteWithDependencies() en las acciones
 * 
 * Ejemplo:
 * use PreventDeleteWithDependencies;
 * 
 * DeleteAction::make()
 *     ->before(fn($action, $record) => 
 *         self::preventDeleteWithDependencies($action, $record, 'usuarios', 'usuario(s)')
 *     )
 */
trait PreventDeleteWithDependencies
{
    /**
     * Previene la eliminación si existen dependencias
     *
     * @param mixed $action La acción de eliminación (DeleteAction o DeleteBulkAction)
     * @param mixed $record El registro a eliminar
     * @param string $relationName Nombre de la relación a verificar
     * @param string $dependencyLabel Etiqueta descriptiva de la dependencia (ej: "usuario(s)", "matrícula(s)")
     * @return void
     */
    protected static function preventDeleteWithDependencies(
        $action,
        $record,
        string $relationName,
        string $dependencyLabel
    ): void {
        // Verificar si el registro tiene dependencias
        $count = $record->$relationName()->count();
        
        if ($count > 0) {
            Notification::make()
                ->warning()
                ->title('No se puede eliminar')
                ->body("Este registro tiene {$count} {$dependencyLabel} asociado(s). Para eliminarlo, primero debe eliminar o reasignar estas dependencias.")
                ->persistent()
                ->send();
            
            $action->cancel();
        }
    }

    /**
     * Previene la eliminación masiva si algún registro tiene dependencias
     *
     * @param mixed $action La acción de eliminación masiva
     * @param mixed $records Los registros a eliminar
     * @param string $relationName Nombre de la relación a verificar
     * @param string $dependencyLabel Etiqueta descriptiva de la dependencia
     * @param string $recordLabelAttribute Atributo del registro para mostrar en el mensaje (ej: 'nombre', 'codigo')
     * @return void
     */
    protected static function preventBulkDeleteWithDependencies(
        $action,
        $records,
        string $relationName,
        string $dependencyLabel,
        string $recordLabelAttribute = 'nombre'
    ): void {
        $recordsWithDependencies = [];
        
        foreach ($records as $record) {
            $count = $record->$relationName()->count();
            if ($count > 0) {
                $label = $record->$recordLabelAttribute ?? "ID: {$record->id}";
                $recordsWithDependencies[] = "{$label} ({$count} {$dependencyLabel})";
            }
        }
        
        if (!empty($recordsWithDependencies)) {
            Notification::make()
                ->warning()
                ->title('No se pueden eliminar algunos registros')
                ->body('Los siguientes registros tienen dependencias: ' . implode(', ', $recordsWithDependencies))
                ->persistent()
                ->send();
            
            $action->cancel();
        }
    }

    /**
     * Verifica múltiples relaciones de un registro
     *
     * @param mixed $action La acción de eliminación
     * @param mixed $record El registro a eliminar
     * @param array $dependencies Array de dependencias: ['relationName' => 'label']
     * @return void
     */
    protected static function preventDeleteWithMultipleDependencies(
        $action,
        $record,
        array $dependencies
    ): void {
        $foundDependencies = [];
        
        foreach ($dependencies as $relationName => $label) {
            $count = $record->$relationName()->count();
            if ($count > 0) {
                $foundDependencies[] = "{$count} {$label}";
            }
        }
        
        if (!empty($foundDependencies)) {
            Notification::make()
                ->warning()
                ->title('No se puede eliminar')
                ->body('Este registro tiene: ' . implode(', ', $foundDependencies) . '. Debe eliminarlos primero.')
                ->persistent()
                ->send();
            
            $action->cancel();
        }
    }
}
