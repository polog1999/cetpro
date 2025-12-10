# 🛡️ **PROTECCIÓN CONTRA ELIMINACIÓN DE ROLES EN USO**

## ✅ Problema Resuelto

### **Error Original:**
```
SQLSTATE[23001]: Restrict violation: 7 ERROR: 
update or delete on table "roles" violates RESTRICT setting 
of foreign key constraint "usuarios_role_id_foreign" on table "usuarios"

DETAIL: Key (id)=(2) is referenced from table "usuarios".
```

### **Causa:**
Intentar eliminar un rol que está siendo usado por uno o más usuarios causaba un error de violación de restricción de foreign key en PostgreSQL.

---

## 🎯 Solución Implementada

Se implementaron **3 capas de protección** para prevenir la eliminación de roles en uso:

### **1. Validación en RoleResource (`canDelete`)**

**Archivo:** `app/Filament/Resources/Roles/RoleResource.php`

```php
public static function canDelete($record): bool
{
    $user = Filament::auth()->user();
    
    // Solo admin puede intentar eliminar
    if (!($user?->role?->es_admin ?? false)) {
        return false;
    }
    
    // Verificar si el rol tiene usuarios asignados
    // Si tiene usuarios, no permitir eliminación
    return $record->usuarios()->count() === 0;
}
```

**¿Qué hace?**
- Si el rol tiene usuarios → Oculta el botón de eliminar
- Si no tiene usuarios → Muestra el botón

### **2. Validación en DeleteAction (Individual)**

**Archivo:** `app/Filament/Resources/Roles/Tables/RolesTable.php`

```php
DeleteAction::make()
    ->before(function (DeleteAction $action, $record) {
        // Verificar si el rol tiene usuarios
        $cantidadUsuarios = $record->usuarios()->count();
        
        if ($cantidadUsuarios > 0) {
            // Mostrar notificación
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('No se puede eliminar el rol')
                ->body("Este rol tiene {$cantidadUsuarios} usuario(s) asignado(s). 
                       Para eliminarlo, primero debe reasignar o eliminar estos usuarios.")
                ->persistent()
                ->send();
            
            // Cancelar la eliminación
            $action->cancel();
        }
    })
```

**¿Qué hace?**
- Intercepta la acción de eliminación ANTES de ejecutarla
- Cuenta cuántos usuarios tienen ese rol
- Si hay usuarios, muestra notificación y cancela
- Si no hay usuarios, permite la eliminación

### **3. Validación en DeleteBulkAction (Múltiple)**

```php
DeleteBulkAction::make()
    ->before(function (DeleteBulkAction $action, $records) {
        // Verificar todos los roles seleccionados
        $rolesConUsuarios = [];
        
        foreach ($records as $record) {
            $cantidadUsuarios = $record->usuarios()->count();
            if ($cantidadUsuarios > 0) {
                $rolesConUsuarios[] = "{$record->nombre} ({$cantidadUsuarios} usuarios)";
            }
        }
        
        if (!empty($rolesConUsuarios)) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('No se pueden eliminar algunos roles')
                ->body('Los siguientes roles tienen usuarios asignados: ' . 
                       implode(', ', $rolesConUsuarios))
                ->persistent()
                ->send();
            
            $action->cancel();
        }
    })
```

**¿Qué hace?**
- Valida cada rol seleccionado en la eliminación masiva
- Lista todos los roles que tienen usuarios
- Cancela la operación completa si alguno tiene usuarios

---

## 🎨 Experiencia de Usuario

### **Escenario 1: Rol SIN usuarios**

```
┌─────────────────────────────────────┐
│ ROLES                               │
├─────────────────────────────────────┤
│                                     │
│ Director         [✏️ Editar] [🗑️ Eliminar] │
│ 👥 0 usuarios                       │
│                                     │
└─────────────────────────────────────┘

Al hacer click en Eliminar:
✅ Se elimina correctamente
✅ Confirmación: "Rol eliminado"
```

### **Escenario 2: Rol CON usuarios**

```
┌─────────────────────────────────────┐
│ ROLES                               │
├─────────────────────────────────────┤
│                                     │
│ Secretaría       [✏️ Editar] [❌]      │
│ 👥 3 usuarios                       │
│                                     │
└─────────────────────────────────────┘

Botón de eliminar NO aparece ❌
(Debido a canDelete() retornando false)
```

### **Escenario 3: Intento de eliminación (fallback)**

Si de alguna manera se intenta eliminar:

```
┌────────────────────────────────────────────┐
│ ⚠️  NO SE PUEDE ELIMINAR EL ROL            │
├────────────────────────────────────────────┤
│                                            │
│  Este rol tiene 3 usuario(s) asignado(s).  │
│                                            │
│  Para eliminarlo, primero debe reasignar   │
│  o eliminar estos usuarios.                │
│                                            │
│  [Entendido]                               │
└────────────────────────────────────────────┘
```

### **Escenario 4: Eliminación masiva**

```
Usuario selecciona:
☑️  Secretaría (3 usuarios)
☑️  Tesorería (2 usuarios)  
☑️  Docente (0 usuarios)

Click en "Eliminar seleccionados"

┌────────────────────────────────────────────┐
│ ⚠️  NO SE PUEDEN ELIMINAR ALGUNOS ROLES    │
├────────────────────────────────────────────┤
│                                            │
│  Los siguientes roles tienen usuarios      │
│  asignados:                                │
│                                            │
│  • Secretaría (3 usuarios)                 │
│  • Tesorería (2 usuarios)                  │
│                                            │
│  [Entendido]                               │
└────────────────────────────────────────────┘

Resultado: NINGUNO se elimina (operación cancelada)
```

---

## 📊 Flujo de Validación

```
Usuario intenta eliminar rol
         │
         ▼
    ┌─────────────────────┐
    │ canDelete($record)  │ ← Primera barrera
    └──────┬──────────────┘
           │
     ┌─────┴─────┐
     │           │
    No          Sí
     │           │
     ▼           ▼
┌─────────┐  ┌──────────────────┐
│ Botón   │  │ Mostrar botón    │
│ oculto  │  │ de eliminar      │
└─────────┘  └────────┬─────────┘
                      │
              Usuario hace click
                      │
                      ▼
         ┌─────────────────────────┐
         │ before() en DeleteAction│ ← Segunda barrera
         └──────────┬──────────────┘
                    │
          ┌─────────┴─────────┐
          │                   │
    Tiene usuarios      No tiene usuarios
          │                   │
          ▼                   ▼
  ┌──────────────┐    ┌──────────────┐
  │ Notificación │    │ Eliminar rol │
  │ de error     │    │ exitosamente │
  │ + Cancel     │    └──────────────┘
  └──────────────┘
```

---

## 🔐 Beneficios de la Solución

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| **Error técnico** | ✅ Error SQL | ✅ Mensaje claro |
| **UX** | ❌ Confuso | ✅ Informativo |
| **Integridad** | ⚠️ Usuario intenta y falla | ✅ Prevenido antes |
| **Feedback** | ❌ Error críptico | ✅ Solución sugerida |
| **Bulk Delete** | ❌ Falla todo | ✅ Valida cada uno |
| **Admin** | ⚠️ Puede causar error | ✅ Protegido |

---

## 🛠️ Alternativas Consideradas

### **Opción 1: CASCADE (❌ Rechazada)**

```sql
ALTER TABLE usuarios 
DROP CONSTRAINT usuarios_role_id_foreign,
ADD CONSTRAINT usuarios_role_id_foreign 
    FOREIGN KEY (role_id) 
    REFERENCES roles(id) 
    ON DELETE CASCADE;
```

**Problema:** Eliminaría TODOS los usuarios al borrar el rol ⚠️ **PELIGROSO**

### **Opción 2: SET NULL (⚠️ No ideal)**

```sql
ON DELETE SET NULL
```

**Problema:** Usuarios quedarían sin rol, causando errores en permisos

### **Opción 3: SOFT DELETE (❌ Sobrecarga)**

Marcar roles como "eliminados" pero mantenerlos en BD.

**Problema:** Complejidad innecesaria para este caso

### **✅ Opción 4: PREVENT DELETE (Implementada)**

La mejor opción porque:
- ✅ Mantiene integridad de datos
- ✅ UX clara y educativa
- ✅ No requiere cambios en BD
- ✅ Sugiere solución al usuario

---

## 📝 Pasos para el Usuario

### **Si necesita eliminar un rol con usuarios:**

1. **Ver cuántos usuarios tiene:**
   ```
   Rol: Secretaría
   👥 3 usuarios
   ```

2. **Ir a Usuarios:**
   - `/admin/usuarios`
   - Filtrar por rol "Secretaría"

3. **Reasignar usuarios:**
   - Editar cada usuario
   - Cambiar rol a otro (ej: "Administrativo")
   - Guardar

4. **Ahora sí eliminar el rol:**
   - Volver a `/admin/roles`
   - El botón de eliminar aparecerá
   - Eliminar sin problemas

### **Flujo Visual:**

```
Paso 1: Intentar eliminar rol
    ↓
⚠️  "Este rol tiene 3 usuarios asignados"
    ↓
Paso 2: Ir a Usuarios
    ↓
Filtrar por rol "Secretaría"
    ↓
Reasignar los 3 usuarios a otro rol
    ↓
Paso 3: Volver a Roles
    ↓
Ahora el botón de eliminar aparece
    ↓
✅ Eliminar rol exitosamente
```

---

## 🧪 Testing

### **Probar la protección:**

```bash
# Escenario 1: Rol sin usuarios
1. Crear rol nuevo: "Rol Test"
2. NO asignar usuarios
3. Intentar eliminar
4. ✅ Debe eliminarse correctamente

# Escenario 2: Rol con 1 usuario
1. Crear usuario con "Rol Test"
2. Intentar eliminar "Rol Test"
3. ❌ Botón no debe aparecer
4. ✅ Notificación informativa

# Escenario 3: Eliminación masiva
1. Seleccionar varios roles (algunos con usuarios)
2. Eliminar seleccionados
3. ✅ Lista roles con usuarios
4. ❌ Ninguno se elimina
```

---

## 🔍 Verificación en Código

### **Comprobar relación en modelo Role:**

```php
// app/Models/Role.php
public function usuarios(): HasMany
{
    return $this->hasMany(Usuario::class);
}
```

✅ **Confirmado:** La relación existe

### **Comprobar foreign key en migración:**

```php
// database/migrations/xxx_create_usuarios_table.php
$table->foreignId('role_id')
    ->constrained('roles')
    ->onUpdate('cascade')
    ->onDelete('restrict'); // ← RESTRICT
```

✅ **Confirmado:** RESTRICT está configurado

---

## 📌 Resumen

| Componente | Función | Estado |
|------------|---------|--------|
| **canDelete()** | Oculta botón si tiene usuarios | ✅ Implementado |
| **DeleteAction** | Valida antes de eliminar | ✅ Implementado |
| **DeleteBulkAction** | Valida eliminación múltiple | ✅ Implementado |
| **Notificaciones** | Mensajes claros al usuario | ✅ Implementado |
| **Integridad BD** | Foreign key RESTRICT | ✅ Mantenido |

**Resultado:** Sistema seguro, claro y educativo para el usuario.

---

**Fecha de implementación:** 2025-12-10  
**Versión:** 1.0  
**Sistema:** CETPRO MDLM
