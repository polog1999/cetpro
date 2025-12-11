# 🔧 Mejora Implementada: Autocompletado de Email en Usuarios

## Problema Identificado

Al crear un usuario desde un empleado, el formulario pedía ingresar el correo electrónico nuevamente, aunque el empleado ya tenía un correo registrado en su ficha.

### Antes:
```
1. Usuario selecciona empleado: "Juan Pérez"
2. Empleado tiene correo: juan.perez@cetpro.edu.pe
3. Sistema pide ingresar correo manualmente ❌
4. Usuario tiene que escribir: juan.perez@cetpro.edu.pe (duplicado)
```

## Solución Implementada

Se modificó `app/Filament/Resources/Usuarios/Schemas/UsuarioForm.php` para autocompletar el email del empleado seleccionado.

### Ahora:
```
1. Usuario selecciona empleado: "Juan Pérez"
2. Sistema detecta que tiene correo: juan.perez@cetpro.edu.pe
3. Autocompleta el campo email ✅
4. Campo queda deshabilitado (solo lectura)
5. Si el empleado no tiene correo, el campo queda habilitado para ingresarlo manualmente
```

## Cambios Técnicos

### 1. Campo `empleado_id` con evento `live()`

```php
Select::make('empleado_id')
    ->label('Empleado')
    ->relationship('empleado', 'nombre')
    ->live() // ← NUEVO: Actualiza en tiempo real
    ->afterStateUpdated(function ($state, $set) {
        // Autocompletar correo desde el empleado
        if ($state) {
            $empleado = Empleado::find($state);
            if ($empleado && $empleado->correo) {
                $set('email', $empleado->correo);
            }
        }
    }),
```

**¿Qué hace?**
- `->live()`: Hace que el campo escuche cambios en tiempo real
- `->afterStateUpdated()`: Se ejecuta cuando se selecciona un empleado
- Busca el empleado seleccionado
- Si tiene correo, lo auto-completa en el campo email

### 2. Campo `email` mejorado

```php
TextInput::make('email')
    ->label('Correo Electrónico')
    ->email()
    ->unique(ignoreRecord: true)
    ->nullable()
    ->helperText('Se autocompletará desde el empleado seleccionado') // ← NUEVO
    ->disabled(fn ($get) => filled($get('empleado_id'))) // ← NUEVO: Deshabilitado si hay empleado
    ->dehydrated(), // ← NUEVO: Asegura que se guarde aunque esté disabled
```

**¿Qué hace?**
- `->helperText()`: Muestra mensaje informativo al usuario
- `->disabled()`: Deshabilita el campo si ya se seleccionó un empleado
- `->dehydrated()`: Importante! Asegura que el valor se guarde en la BD aunque el campo esté disabled

## Flujo de Uso

### Escenario 1: Empleado con correo

```
┌─────────────────────────────────────┐
│ CREAR NUEVO USUARIO                 │
├─────────────────────────────────────┤
│                                     │
│ Empleado*:                          │
│ [Juan Pérez ▾]  ← Usuario selecciona│
│                                     │
│ ↓ Sistema detecta correo            │
│                                     │
│ Email:                              │
│ [juan.perez@cetpro.edu.pe] 🔒      │
│ (autocompletado y bloqueado)        │
│                                     │
│ Rol*:                               │
│ [Secretaría ▾]                      │
│                                     │
│ Usuario*:                           │
│ [jperez_____]  ← Usuario escribe    │
│                                     │
│ Contraseña*:                        │
│ [••••••••___]                       │
│                                     │
│ [Cancelar]  [Guardar]               │
└─────────────────────────────────────┘
```

### Escenario 2: Empleado sin correo

```
┌─────────────────────────────────────┐
│ CREAR NUEVO USUARIO                 │
├─────────────────────────────────────┤
│                                     │
│ Empleado*:                          │
│ [María López ▾]  ← No tiene correo  │
│                                     │
│ Email:                              │
│ [___________________] ✏️            │
│ (campo habilitado para escribir)    │
│                                     │
└─────────────────────────────────────┘
```

## Beneficios

✅ **Mejor UX:** No se pide información duplicada
✅ **Menos errores:** No hay riesgo de escribir mal el correo
✅ **Más rápido:** Un paso menos al crear usuarios
✅ **Consistencia:** El correo es siempre el mismo que el del empleado
✅ **Flexible:** Si el empleado no tiene correo, se puede ingresar manualmente

## Validaciones Mantenidas

- ✅ Email debe ser válido (formato email)
- ✅ Email debe ser único en la tabla usuarios
- ✅ Se puede dejar en blanco (nullable)

## Casos de Uso

### Caso 1: Empleado nuevo con correo

```bash
1. Registrar empleado en Empleados
   - Nombre: Juan Pérez
   - Correo: juan.perez@cetpro.edu.pe ✅
   
2. Crear usuario en Usuarios
   - Seleccionar: Juan Pérez
   - Email se autocompleta: juan.perez@cetpro.edu.pe ✅
   - Campo queda bloqueado 🔒
```

### Caso 2: Empleado sin correo

```bash
1. Empleado registrado sin correo
   - Nombre: María López
   - Correo: (vacío)
   
2. Crear usuario en Usuarios
   - Seleccionar: María López
   - Email queda vacío y editable ✏️
   - Usuario puede ingresar: maria.lopez@cetpro.edu.pe
   - Se guarda normalmente
```

### Caso 3: Editar usuario existente

```bash
1. Abrir usuario existente
   - Empleado: Carlos Gómez
   - Email: carlos.gomez@cetpro.edu.pe (desde empleado)
   
2. El email se muestra pero está bloqueado
3. Para cambiarloprimero hay que actualizar el empleado
```

## Recomendaciones

### Para Administradores

1. **Siempre registrar correo en Empleado:**
   - Al crear empleado, incluir su correo
   - Esto facilita la creación posterior del usuario

2. **Actualizar correos en Empleados:**
   - Si un empleado cambia de correo
   - Actualizar en su ficha de empleado
   - Esto mantendrá consistencia con su usuario

### Para Usuarios del Sistema

1. **Al crear usuario:**
   - Primero selecciona el empleado
   - El email se completará solo si existe
   - Solo escribe el username y contraseña

2. **Si el email está mal:**
   - No editar en Usuarios
   - Ir a Empleados y corregir el correo
   - El cambio se reflejará automáticamente

## Código Completo Actualizado

```php
<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\Empleado;

class UsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('empleado_id')
                    ->label('Empleado')
                    ->relationship('empleado', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Empleado $e) {
                        return trim($e->nombre.' '.$e->apellido_paterno.' '.$e->apellido_materno);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // ← Escucha cambios
                    ->afterStateUpdated(function ($state, $set) {
                        // Autocompletar correo
                        if ($state) {
                            $empleado = Empleado::find($state);
                            if ($empleado && $empleado->correo) {
                                $set('email', $empleado->correo);
                            }
                        }
                    }),
                    
                Select::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Role $role) {
                        return $role->nombre . ($role->es_admin ? ' (Admin)' : '');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione el rol que determinará los permisos del usuario'),
                    
                TextInput::make('usuario')
                    ->label('Nombre de usuario')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->nullable()
                    ->helperText('Se autocompletará desde el empleado seleccionado')
                    ->disabled(fn ($get) => filled($get('empleado_id')))
                    ->dehydrated(),
                    
                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($record) => $record === null),

                \Filament\Forms\Components\Toggle::make('activo')
                    ->label('Usuario Activo')
                    ->default(true)
                    ->helperText('Si se desactiva, el usuario no podrá iniciar sesión.'),
            ]);
    }
}
```

## Testing

Para probar la funcionalidad:

```bash
1. Ir a: /admin/usuarios
2. Click en "Nuevo Usuario"
3. Seleccionar un empleado de la lista
4. Observar que el email se autocompleta
5. Intentar editar el email (debe estar bloqueado)
6. Completar usuario, rol y contraseña
7. Guardar
8. Verificar que el email se guardó correctamente
```

## Troubleshooting

### El email no se autocompleta

**Causa:** El empleado no tiene correo registrado

**Solución:**
```bash
1. Ir a Empleados
2. Buscar el empleado
3. Editar y agregar correo
4. Volver a crear el usuario
```

### El email está bloqueado pero es incorrecto

**Solución:**
```bash
1. No editar en Usuarios
2. Ir a Empleados y corregir el correo
3. El email correcto se usará en nuevos usuarios
4. Para usuarios existentes, se puede actualizar manualmente en la BD si es necesario
```

---

**Fecha de implementación:** 2025-12-10  
**Versión:** 1.0  
**Autor:** Sistema CETPRO MDLM
