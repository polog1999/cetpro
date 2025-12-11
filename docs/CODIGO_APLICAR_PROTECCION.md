# 🔧 CÓDIGO LISTO PARA APLICAR - RECURSOS PENDIENTES

## Copiar y pegar estos cambios en los archivos correspondientes

---

## 1. DOCENTES (asignados a Horarios)

### Archivo: `app/Filament/Resources/Docentes/DocenteResource.php`

**Modificar el método `canDelete`:**

```php
public static function canDelete($record): bool
{
    if (!static::canViewAny()) {
        return false;
    }
    
    // No permitir eliminación si el docente está asignado a horarios
    return !$record->horarios()->exists();
}
```

---

### Archivo: `app/Filament/Resources/Docentes/Tables/DocentesTable.php`

**Agregar al inicio del archivo (después de namespace):**
```php
use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Tables\Columns\IconColumn;
```

**Agregar en la clase:**
```php
class DocentesTable
{
    use PreventDeleteWithDependencies;
```

**Agregar columna visual (dentro de columns):**
```php
IconColumn::make('horarios')
    ->label('Horarios')
    ->boolean()
    ->trueIcon('heroicon-o-calendar')
    ->falseIcon('heroicon-o-x-mark')
    ->trueColor('success')
    ->falseColor('gray')
    ->getStateUsing(fn ($record) => $record->horarios()->exists()),

TextColumn::make('horarios_count')
    ->label('Cant. Horarios')
    ->counts('horarios')
    ->badge()
    ->color('info'),
```

**Modificar DeleteAction:**
```php
DeleteAction::make()
    ->before(fn (DeleteAction $action, $record) => 
        self::preventDeleteWithDependencies(
            $action,
            $record,
            'horarios',
            'horario(s) asignado(s)'
        )
    ),
```

**Modificar DeleteBulkAction:**
```php
DeleteBulkAction::make()
    ->before(fn (DeleteBulkAction $action, $records) => 
        self::preventBulkDeleteWithDependencies(
            $action,
            $records,
            'horarios',
            'horario(s)',
            'nombres' // o el atributo que use para nombre completo
        )
    ),
```

---

## 2. HORARIOS (tienen Matrículas)

### Archivo: `app/Filament/Resources/Horarios/HorarioResource.php`

**Modificar el método `canDelete`:**

```php
public static function canDelete($record): bool
{
    if (!static::canViewAny()) {
        return false;
    }
    
    // No permitir eliminación si el horario tiene matrículas
    return !$record->matriculas()->exists();
}
```

---

### Archivo: `app/Filament/Resources/Horarios/Tables/HorariosTable.php`

**Agregar al inicio:**
```php
use App\Filament\Traits\PreventDeleteWithDependencies;
```

**Agregar en la clase:**
```php
class HorariosTable
{
    use PreventDeleteWithDependencies;
```

**Agregar columna (si no existe):**
```php
TextColumn::make('matriculas_count')
    ->label('Matrículas')
    ->counts('matriculas')
    ->badge()
    ->color('success'),
```

**Modificar DeleteAction:**
```php
DeleteAction::make()
    ->before(fn (DeleteAction $action, $record) => 
        self::preventDeleteWithDependencies(
            $action,
            $record,
            'matriculas',
            'matrícula(s) activa(s)'
        )
    ),
```

**Modificar DeleteBulkAction:**
```php
DeleteBulkAction::make()
    ->before(fn (DeleteBulkAction $action, $records) => 
        self::preventBulkDeleteWithDependencies(
            $action,
            $records,
            'matriculas',
            'matrícula(s)',
            'codigo' // o el atributo que identifique al horario
        )
    ),
```

---

## 3. ESTUDIANTES (tienen Matrículas)

### Archivo: `app/Filament/Resources/Estudiantes/EstudianteResource.php`

**Modificar el método `canDelete`:**

```php
public static function canDelete($record): bool
{
    if (!static::canViewAny()) {
        return false;
    }
    
    // No permitir eliminación si el estudiante tiene matrículas
    return !$record->matriculas()->exists();
}
```

---

### Archivo: `app/Filament/Resources/Estudiantes/Tables/EstudiantesTable.php`

**Agregar al inicio:**
```php
use App\Filament\Traits\PreventDeleteWithDependencies;
```

**Agregar en la clase:**
```php
class EstudiantesTable
{
    use PreventDeleteWithDependencies;
```

**Agregar columna:**
```php
TextColumn::make('matriculas_count')
    ->label('Matrículas')
    ->counts('matriculas')
    ->badge()
    ->color('info'),
```

**Modificar DeleteAction:**
```php
DeleteAction::make()
    ->before(fn (DeleteAction $action, $record) => 
        self::preventDeleteWithDependencies(
            $action,
            $record,
            'matriculas',
            'matrícula(s)'
        )
    ),
```

**Modificar DeleteBulkAction:**
```php
DeleteBulkAction::make()
    ->before(fn (DeleteBulkAction $action, $records) => 
        self::preventBulkDeleteWithDependencies(
            $action,
            $records,
            'matriculas',
            'matrícula(s)',
            'nombre_completo' // o apellidos + nombres
        )
    ),
```

---

## 4. CRONOGRAMAS (tienen Pagos) ⚠️ CRÍTICO

### Archivo: `app/Filament/Resources/Cronogramas/CronogramaResource.php`

**Modificar el método `canDelete`:**

```php
public static function canDelete($record): bool
{
    if (!static::canViewAny()) {
        return false;
    }
    
    // NUNCA eliminar cronogramas con pagos (integridad financiera)
    return !$record->pagos()->exists();
}
```

---

### Archivo: `app/Filament/Resources/Cronogramas/Tables/CronogramasTable.php`

**Agregar al inicio:**
```php
use App\Filament\Traits\PreventDeleteWithDependencies;
```

**Agregar en la clase:**
```php
class CronogramasTable
{
    use PreventDeleteWithDependencies;
```

**Agregar columnas:**
```php
TextColumn::make('pagos_count')
    ->label('Pagos')
    ->counts('pagos')
    ->badge()
    ->color('warning'),

IconColumn::make('tiene_pagos')
    ->label('Con Pagos')
    ->boolean()
    ->trueIcon('heroicon-o-check-badge')
    ->falseIcon('heroicon-o-x-mark')
    ->trueColor('danger') // Rojo porque NO se puede eliminar
    ->falseColor('success')
    ->getStateUsing(fn ($record) => $record->pagos()->exists()),
```

**Modificar DeleteAction:**
```php
DeleteAction::make()
    ->before(fn (DeleteAction $action, $record) => 
        self::preventDeleteWithDependencies(
            $action,
            $record,
            'pagos',
            'pago(s) registrado(s)'
        )
    )
    ->requiresConfirmation()
    ->modalHeading('⚠️ Eliminar Cronograma')
    ->modalDescription('Esta acción es permanente y puede afectar la integridad de los registros de pago.')
    ->modalSubmitActionLabel('Sí, eliminar'),
```

**Modificar DeleteBulkAction:**
```php
DeleteBulkAction::make()
    ->before(fn (DeleteBulkAction $action, $records) => 
        self::preventBulkDeleteWithDependencies(
            $action,
            $records,
            'pagos',
            'pago(s)',
            'codigo' // o identificador del cronograma
        )
    )
    ->requiresConfirmation()
    ->modalHeading('⚠️ Eliminar Cronogramas')
    ->modalDescription('Esta acción es permanente. Los cronogramas con pagos NO serán eliminados.'),
```

---

## ✅ VERIFICACIÓN

Después de aplicar todos los cambios, verificar:

### Checklist por Recurso:

**Docentes:**
- [ ] Resource: canDelete() actualizado
- [ ] Table: Trait importado y usado
- [ ] Table: DeleteAction protegido
- [ ] Table: DeleteBulkAction protegido
- [ ] Table: Columna visual agregada

**Horarios:**
- [ ] Resource: canDelete() actualizado
- [ ] Table: Trait importado y usado
- [ ] Table: DeleteAction protegido
- [ ] Table: DeleteBulkAction protegido
- [ ] Table: Columna count agregada

**Estudiantes:**
- [ ] Resource: canDelete() actualizado
- [ ] Table: Trait importado y usado
- [ ] Table: DeleteAction protegido
- [ ] Table: DeleteBulkAction protegido
- [ ] Table: Columna matriculas_count agregada

**Cronogramas:**
- [ ] Resource: canDelete() actualizado
- [ ] Table: Trait importado y usado
- [ ] Table: DeleteAction protegido (+ confirmación extra)
- [ ] Table: DeleteBulkAction protegido (+ confirmación extra)
- [ ] Table: Columna pagos_count agregada
- [ ] Table: Columna tiene_pagos (icono rojo/verde)

---

## 🧪 PRUEBAS

Una vez aplicado todo, realizar estas pruebas:

1. **Intentar eliminar un docente asignado a horarios:**
   - ✅ Botón oculto
   - ✅ Notificación si se intenta

2. **Intentar eliminar un horario con matrículas:**
   - ✅ Botón oculto
   - ✅ Notificación clara

3. **Intentar eliminar un estudiante matriculado:**
   - ✅ Botón oculto
   - ✅ Mensaje educativo

4. **Intentar eliminar cronograma con pagos:**
   - ✅ Botón oculto
   - ✅ Confirmación doble
   - ✅ Notificación WARNING

5. **Eliminación masiva (seleccionar varios con dependencias):**
   - ✅ Lista todos los que tienen dependencias
   - ✅ Cancela toda la operación

---

**Tiempo estimado de aplicación:** 20-30 minutos  
**Copiar y pegar:** Seguir el orden (Docentes → Horarios → Estudiantes → Cronogramas)
