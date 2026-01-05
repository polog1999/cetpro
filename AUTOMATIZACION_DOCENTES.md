# Automatización de Creación de Docentes

## 📋 Descripción General

El sistema cuenta con una automatización que **crea automáticamente un registro en la tabla `docentes`** cada vez que un administrador crea un usuario con rol de **Profesor** o **Docente**.

## 🔄 Flujo Automatizado

### 1. **Creación de Usuario Profesor**
Cuando el administrador crea un nuevo usuario:

```
1. Admin selecciona Empleado (contiene: nombre, apellidos, documento, etc.)
2. Admin selecciona Rol = "Profesor"
3. Admin completa usuario y contraseña
4. Presiona "Guardar"
   ↓
5. El sistema AUTOMÁTICAMENTE:
   - Detecta que el rol es "Profesor"
   - Obtiene los datos del Empleado seleccionado
   - Crea un registro en tabla `docentes` con:
     * tipo_documento (del empleado)
     * nro_documento (del empleado)
     * nombres (del empleado)
     * apellido_paterno (del empleado)
     * apellido_materno (del empleado)
   - Vincula el Usuario al Docente creado
```

### 2. **Datos que se Copian del Empleado al Docente**

| Campo Empleado | → | Campo Docente |
|---|---|---|
| `tipo_documento` | → | `tipo_documento` |
| `num_documento` | → | `nro_documento` |
| `nombre` | → | `nombres` |
| `apellido_paterno` | → | `apellido_paterno` |
| `apellido_materno` | → | `apellido_materno` |

## 🛠️ Componentes Técnicos

### **Observer: UsuarioObserver**
**Ubicación**: `app/Observers/UsuarioObserver.php`

Escucha los eventos del modelo `Usuario`:

- **`created()`**: Se dispara cuando se crea un nuevo usuario
  - Verifica si es profesor (`esProfesor()`)
  - Si no tiene docente asociado, crea uno automáticamente
  - Registra el éxito/error en los logs

- **`updated()`**: Se dispara cuando se actualiza un usuario
  - Si cambian los datos del empleado, sincroniza automáticamente el docente
  - Mantiene coherencia entre datos

### **Registración del Observer**
**Ubicación**: `app/Providers/AppServiceProvider.php` (línea 43)

```php
\App\Models\Usuario::observe(\App\Observers\UsuarioObserver::class);
```

### **Formulario Mejorado**
**Ubicación**: `app/Filament/Resources/Usuarios/Schemas/UsuarioForm.php`

Incluye:
- Mensaje informativo sobre creación automática de docentes
- Ayuda contextual en cada campo
- Validaciones adecuadas

## 🔧 Comando de Sincronización

Para sincronizar **usuarios profesores existentes** que aún no tienen docente asociado:

```bash
php artisan sync:docentes
```

**Ubicación**: `app/Console/Commands/SincronizarDocentesDesdeUsuarios.php`

**Funcionalidades**:
- Busca todos los usuarios con rol "Profesor"
- Filtra los que NO tienen docente asociado
- Crea docentes con los datos de sus empleados
- Muestra progreso con barra visual
- Reporta errores con detalles

**Ejemplo de salida**:
```
Iniciando sincronización de docentes...
Se encontraron 3 usuario(s) profesor(es) sin docente asociado.

[████████████████████░░░░░░░░░░░░░░░░░] 15/20

═══════════════════════════════════════════
  RESULTADO DE LA SINCRONIZACIÓN
═══════════════════════════════════════════
✓ Docentes creados exitosamente: 18
✗ Usuarios con errores: 2
Total procesados: 20
═══════════════════════════════════════════
```

## 📊 Relaciones de Base de Datos

```
Empleado (1) ──┐
               ├─→ Usuario (1) ──→ Docente (1)
               │                      ↓
               └──────────────────────┘
               
Docente (1) ←──────────────── Horario (*)
Docente (1) ←──────────────── Nota (*)
```

## ✅ Validaciones

El observer realiza estas validaciones:

1. **Usuario es profesor**: Verifica rol o presencia de `docente_id`
2. **Empleado existe**: Valida que el usuario tenga empleado asociado
3. **Datos completos**: Verifica nombre y apellido paterno del empleado
4. **Sin duplicados**: No crea docente si ya existe uno asociado
5. **Manejo de errores**: Registra fallos en logs para debugging

## 📝 Logging

Todos los eventos se registran en:
```
storage/logs/laravel.log
```

**Ejemplos de registros**:
```
[2024-12-30 14:30:45] local.INFO: Docente creado automáticamente para usuario: jrodriguez

[2024-12-30 14:31:22] local.ERROR: Error al crear docente para usuario mgonzalez: No se encontró empleado asociado al usuario mgonzalez
```

## 🚀 Flujo Visual Completo

```
┌─────────────────────────────────────┐
│   Admin crea Usuario (Formulario)   │
└──────────────┬──────────────────────┘
               │
               ▼
    ┌─────────────────────┐
    │  ¿Rol = Profesor?   │
    └────────┬────────────┘
             │
         SÍ ▼ NO → Usuario creado sin docente
             │
    ┌────────────────────────────────┐
    │ Observer::created() se dispara  │
    └────────┬───────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ Obtener datos del Empleado     │
    └────────┬───────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ Crear Docente (tabla docentes) │
    └────────┬───────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ Vincular Usuario → Docente     │
    │ (docente_id = docente.id)      │
    └────────┬───────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ ✓ Proceso completado           │
    │ Docente listo para usar        │
    │ (asignar horarios, etc.)       │
    └────────────────────────────────┘
```

## 🐛 Troubleshooting

### Problema: Docente no se crea

**Posibles causas**:
1. El empleado no tiene nombre o apellido paterno
2. El usuario no tiene empleado asociado
3. El rol no es exactamente "Profesor"

**Solución**: 
- Revisar `storage/logs/laravel.log` para mensajes de error
- Ejecutar comando `php artisan sync:docentes` manualmente
- Verificar datos del empleado en tabla `empleados`

### Problema: Docente con datos incompletos

**Solución**:
- Actualizar el empleado con datos completos
- Actualizar el usuario (esto sincroniza automáticamente el docente)
- Si es necesario, usar comando `php artisan sync:docentes --fresh`

## 📞 Contacto y Soporte

Para reportar problemas con la automatización:
1. Revisar logs en `storage/logs/laravel.log`
2. Verificar que usuarios y empleados estén correctamente relacionados
3. Contactar al equipo de desarrollo

---

**Última actualización**: Diciembre 2024
**Versión**: 1.0
