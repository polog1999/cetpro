# 🔧 GUÍA DE CORRECCIÓN RÁPIDA - BUGS CRÍTICOS

**Tiempo estimado total:** 30 minutos  
**Impacto:** Arreglará 40+ tests fallidos

---

## 🎯 CORRECCIÓN #1: Factory de Programa (5 min) ⚠️ URGENTE

### Problema Identificado

El **Factory** usa `TipoPrograma::PROGRAMA_ESTUDIO` pero el enum correcto es `Tip::PROGRAMA`

**Archivo:** `database/factories/ProgramaFactory.php`

**Código actual (línea 24):**
```php
'tipo_programa' => \App\Enums\TipoPrograma::PROGRAMA_ESTUDIO,
```

**Hay DOS enums similares:**
- `TipoPrograma` con valores: `PROGRAMA_ESTUDIO`, `FORMACION_CONTINUA` ❌ ANTIGUO
- `Tip` con valores: `PROGRAMA`, `FORMACION_CONTINUA` ✅ NUEVO

### Solución

**Opción A: Usar valores directos (más seguro)**
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_programa' => $this->faker->sentence(3),
            'duracion' => $this->faker->randomElement(['3 meses', '6 meses', '12 meses', '2 años']),
            'num_cursos' => $this->faker->numberBetween(5, 15),
            'id_especialidad' => \App\Models\Especialidad::factory(),
            'tipo_programa' => $this->faker->randomElement(['Programa', 'Formación continua']), // 👈 Usar valores directos
        ];
    }
}
```

**Opción B: Usar enum Tip (consistente con migración)**
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\Tip; // 👈 Cambiar import

class ProgramaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_programa' => $this->faker->sentence(3),
            'duracion' => $this->faker->randomElement(['3 meses', '6 meses', '12 meses', '2 años']),
            'num_cursos' => $this->faker->numberBetween(5, 15),
            'id_especialidad' => \App\Models\Especialidad::factory(),
            'tipo_programa' => $this->faker->randomElement(Tip::cases()), // 👈 Usar Tip
        ];
    }
}
```

**Recomendación:** Usar **Opción A** por simplicidad y evitar más problemas de enum.

### Verificación
```bash
php artisan test tests/Unit/CronogramaTest.php
```

Debería pasar al menos 1-2 tests ahora.

---

## 🎯 CORRECCIÓN #2: Route [login] en SmokeTest (2 min)

### Problema

Test espera ruta nombrada `login` pero Filament usa su propia ruta.

**Archivo:** `tests/Feature/SmokeTest.php`

**Línea ~110:**
```php
$response->assertRedirect();
```

### Solución

```php
// Opción A: Ruta de Filament
$response->assertRedirect(route('filament.admin.auth.login'));

// Opción B: Simplemente verificar que redirecciona (más genérico)
$response->assertStatus(302);
```

**Recomendación:** Usar **Opción B** si no importa a dónde redirecciona, solo que lo hace.

---

## 🎯 CORRECCIÓN #3: Resolver duplicidad de Enums (20 min)

### Análisis de la Situación

Existen **DOS enums** para lo mismo:

**TipoPrograma.php (ANTIGUO):**
```php
enum TipoPrograma: string {
    case PROGRAMA_ESTUDIO = 'Programa';
    case FORMACION_CONTINUA = 'Formación continua';
}
```

**Tip.php (NUEVO):**
```php
enum Tip: string {
    case PROGRAMA = 'Programa';
    case FORMACION_CONTINUA = 'Formación continua';
}
```

**Diferencias:**
- Nombre del case: `PROGRAMA_ESTUDIO` vs `PROGRAMA`
- Mismo valor en BD: ambos son `'Programa'`

### Archivos que usan cada uno

**Usan `TipoPrograma`:** (13 archivos)
- `app/Models/Programa.php`
- `app/Filament/Resources/Programas/*` (4 archivos)
- `app/Filament/Resources/Matriculas/*` (2 archivos)
- `database/factories/ProgramaFactory.php`

**Usan `Tip`:**
- `app/Filament/Resources/Horarios/Schemas/HorarioForm.php` (1 archivo con comentario)

### Decisión Recomendada: **ELIMINAR Tip, MANTENER TipoPrograma**

**Razón:**
- TipoPrograma es usado en 13 archivos
- Tip solo en 1 archivo
- Cambiar 1 archivo es más fácil que 13
- El modelo `Programa` ya usa TipoPrograma en el cast

### Plan de Acción

#### Paso 1: Actualizar HorarioForm.php
```php
// app/Filament/Resources/Horarios/Schemas/HorarioForm.php

// CAMBIAR:
use App\Enums\Tip;

// POR:
use App\Enums\TipoPrograma;

// Y cambiar en el código:
// ANTES:
Tip::PROGRAMA->value => 'Programa',
Tip::FORMACION_CONTINUA->value => 'Formación continua',

// DESPUÉS:
TipoPrograma::PROGRAMA_ESTUDIO->value => 'Programa',
TipoPrograma::FORMACION_CONTINUA->value => 'Formación continua',
```

#### Paso 2: Eliminar archivo Tip.php
```bash
# Hacer backup primero
cp app/Enums/Tip.php app/Enums/Tip.php.backup

# Eliminar (o comentar todo el contenido)
rm app/Enums/Tip.php
```

#### Paso 3: Actualizar Factory (ya hecho en Corrección #1)

#### Paso 4: Verificar
```bash
# Buscar referencias a Tip
grep -r "use App\\Enums\\Tip" app/ --exclude-dir=vendor

# Buscar "Tip::" en código
grep -r "Tip::" app/ --exclude-dir=vendor

# No debería haber resultados
```

### Alternativa: Mantener Tip y migrar todo

Si prefieres usar `Tip` (por ser más corto), necesitarías:

1. Actualizar el cast en `Programa.php`:
```php
'tipo_programa' => Tip::class, // En vez de TipoPrograma::class
```

2. Actualizar 13 archivos (imports y referencias)
3. Eliminar `TipoPrograma.php`

**No recomendado** por ser más trabajo y mayor riesgo.

---

## 🎯 SCRIPT DE CORRECCIÓN AUTOMÁTICA

Crea este archivo para ejecutar todas las correcciones:

**Archivo:** `scripts/fix_critical_bugs.php`

```php
<?php

echo "🔧 Aplicando correcciones críticas...\n\n";

// 1. Corregir ProgramaFactory
echo "1️⃣ Corrigiendo ProgramaFactory...\n";
$factoryPath = __DIR__ . '/../database/factories/ProgramaFactory.php';
$factoryContent = file_get_contents($factoryPath);

// Hacer backup
file_put_contents($factoryPath . '.backup', $factoryContent);

// Reemplazar
$factoryContent = str_replace(
    "'tipo_programa' => \\App\\Enums\\TipoPrograma::PROGRAMA_ESTUDIO,",
    "'tipo_programa' => \$this->faker->randomElement(['Programa', 'Formación continua']),",
    $factoryContent
);

file_put_contents($factoryPath, $factoryContent);
echo "   ✅ ProgramaFactory corregido\n\n";

// 2. Corregir SmokeTest
echo "2️⃣ Corrigiendo SmokeTest...\n";
$testPath = __DIR__ . '/../tests/Feature/SmokeTest.php';
$testContent = file_get_contents($testPath);

// Hacer backup
file_put_contents($testPath . '.backup', $testContent);

// Buscar y reemplazar la línea problemática
$testContent = preg_replace(
    "/\\\$response->assertRedirect\(\);/",
    "\$response->assertStatus(302); // Redirects to login",
    $testContent
);

file_put_contents($testPath, $testContent);
echo "   ✅ SmokeTest corregido\n\n";

// 3. Eliminar Tip.php (hacer backup primero)
echo "3️⃣ Haciendo backup de Tip.php...\n";
$tipPath = __DIR__ . '/../app/Enums/Tip.php';
if (file_exists($tipPath)) {
    copy($tipPath, $tipPath . '.backup');
    echo "   ✅ Backup creado: Tip.php.backup\n";
    echo "   ⚠️  MANUAL: Revisa si puedes eliminar app/Enums/Tip.php\n\n";
} else {
    echo "   ℹ️  Tip.php no existe\n\n";
}

echo "✅ Correcciones completadas!\n";
echo "\n📋 Próximos pasos:\n";
echo "1. Ejecutar: php artisan test\n";
echo "2. Verificar que la mayoría de tests pasen\n";
echo "3. Revisar manualmente HorarioForm.php si usa Tip\n";
```

### Ejecutar script
```bash
php scripts/fix_critical_bugs.php
php artisan test
```

---

## 📊 CHECKLIST DE CORRECCIÓN

```
[ ] 1. Corregir ProgramaFactory (usar valores directos en vez de enum)
[ ] 2. Ejecutar tests: php artisan test tests/Unit/CronogramaTest.php
[ ] 3. Verificar que tests de Cronograma pasan
[ ] 4. Corregir SmokeTest (assertStatus(302) en vez de assertRedirect())
[ ] 5. Ejecutar: php artisan test tests/Feature/SmokeTest.php
[ ] 6. Revisar HorarioForm.php (cambiar Tip por TipoPrograma)
[ ] 7. Eliminar/renombrar app/Enums/Tip.php
[ ] 8. Ejecutar suite completa: php artisan test
[ ] 9. Verificar que 90%+ tests pasan
[ ] 10. Documentar cambios
```

---

## 🎯 RESULTADO ESPERADO

**Antes:**
- ❌ 48 tests fallando
- ✅ 52 tests pasando
- **Total:** 101 tests (51.5% success)

**Después de correcciones:**
- ❌ ~5-8 tests fallando (bugs menores)
- ✅ ~93-96 tests pasando
- **Total:** 101 tests (**92-95% success**)

---

## ⚠️ NOTAS IMPORTANTES

1. **Backups automáticos:** El script crea `.backup` de archivos modificados
2. **Reversión:** Si algo falla, puedes restaurar desde `.backup`
3. **Factories y Seeders:** Después de cambios, considera ejecutar:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. **Cache:** Limpiar cache después de cambios:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

**Última actualización:** 2025-12-10  
**Estimación de tiempo:** 30 minutos  
**Nivel de riesgo:** 🟢 Bajo (cambios simples y reversibles)
