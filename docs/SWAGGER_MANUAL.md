# Manual de Swagger/OpenAPI - CETPRO MDLM

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Instalación Realizada](#instalación-realizada)
3. [Acceso a la Documentación](#acceso-a-la-documentación)
4. [Estructura de Anotaciones](#estructura-de-anotaciones)
5. [Cómo Documentar Nuevos Endpoints](#cómo-documentar-nuevos-endpoints)
6. [Comandos Útiles](#comandos-útiles)
7. [Ejemplos Completos](#ejemplos-completos)
8. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

### ¿Qué es Swagger/OpenAPI?

**OpenAPI** (anteriormente conocido como Swagger) es una especificación estándar para describir APIs REST. Permite documentar de forma estructurada:

- Los endpoints disponibles (URLs)
- Los métodos HTTP (GET, POST, PUT, DELETE)
- Los parámetros esperados
- Los formatos de respuesta
- Los códigos de estado HTTP

**L5-Swagger** es un paquete para Laravel que permite generar documentación OpenAPI automáticamente a partir de anotaciones PHP en los controladores.

### Beneficios

✅ **Documentación interactiva**: Los desarrolladores pueden probar la API directamente desde el navegador  
✅ **Actualización automática**: La documentación se mantiene sincronizada con el código  
✅ **Estandarización**: Formato universal que facilita la integración con herramientas externas  
✅ **Generación de clientes**: Posibilidad de generar SDKs automáticamente

---

## Instalación Realizada

### Paquetes Instalados

Se instaló el paquete **L5-Swagger v9.0.1** que incluye:

| Paquete | Versión | Descripción |
|---------|---------|-------------|
| `darkaonline/l5-swagger` | 9.0.1 | Integración de Swagger con Laravel |
| `zircote/swagger-php` | 5.7.6 | Parser de anotaciones OpenAPI |
| `swagger-api/swagger-ui` | 5.31.0 | Interfaz visual de Swagger |

### Archivos Creados/Modificados

```
c:\Jhimmy\cetpro-mdlm\
├── config/
│   └── l5-swagger.php           # Configuración de Swagger
├── storage/
│   └── api-docs/
│       └── api-docs.json        # Documentación generada (JSON)
├── resources/views/vendor/
│   └── l5-swagger/              # Vistas personalizables
└── app/Http/Controllers/Api/V1/
    ├── ApiController.php        # Anotaciones principales (@Info, @Server, @Tags)
    ├── PagoController.php       # Documentación completa de endpoints
    └── MatriculaController.php  # Documentación completa de endpoints
```

### Configuración Aplicada

En `config/l5-swagger.php`:

| Configuración | Valor | Propósito |
|--------------|-------|-----------|
| `title` | "CETPRO MDLM API v1" | Título en la UI |
| `generate_always` | `true` | Regenerar docs en cada request |
| `dark_mode` | `true` | Tema oscuro habilitado |
| `doc_expansion` | `"list"` | Expandir tags por defecto |
| `L5_SWAGGER_CONST_HOST` | `http://localhost:8000` | URL base de la API |

---

## Acceso a la Documentación

### URL de la Documentación

Una vez que el servidor esté corriendo, accede a:

```
http://localhost:8000/api/documentation
```

### Pasos para Visualizar

1. **Iniciar el servidor de desarrollo**:
   ```bash
   php artisan serve
   ```

2. **Abrir el navegador** y navegar a:
   ```
   http://localhost:8000/api/documentation
   ```

3. **Explorar la documentación**:
   - Los endpoints están agrupados por tags (Pagos, Matrículas, etc.)
   - Haz clic en un endpoint para ver sus detalles
   - Usa el botón "Try it out" para probar el endpoint

### Captura de Pantalla Esperada

La interfaz mostrará:
- **Header**: Título "CETPRO MDLM API v1"
- **Tags**: Pagos, Matrículas, Estudiantes, etc.
- **Endpoints**: Listados bajo cada tag
- **Detalles**: Parámetros, body y respuestas esperadas

---

## Estructura de Anotaciones

### Ubicación de Anotaciones Principales

Las anotaciones principales están en `ApiController.php`:

```php
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "CETPRO MDLM API",
    description: "Descripción de la API..."
)]
#[OA\Server(
    url: "http://localhost:8000/api/v1",
    description: "Servidor de Desarrollo Local"
)]
#[OA\Tag(name: "Pagos", description: "Gestión de pagos de cuotas")]
#[OA\Tag(name: "Matrículas", description: "Gestión de matrículas")]
```

### Sintaxis de Atributos PHP 8

L5-Swagger usa la sintaxis de **atributos de PHP 8** (no DocBlocks):

```php
// ✅ CORRECTO - Atributos PHP 8
#[OA\Get(path: "/endpoint")]

// ❌ INCORRECTO - DocBlocks (sintaxis antigua)
/** @OA\Get(path="/endpoint") */
```

---

## Cómo Documentar Nuevos Endpoints

### Plantilla Básica

Para documentar un nuevo endpoint, agrega anotaciones antes del método:

```php
use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/mi-recurso",
    summary: "Breve descripción",
    description: "Descripción detallada del endpoint",
    tags: ["MiTag"],
    responses: [
        new OA\Response(response: 200, description: "Éxito")
    ]
)]
public function index(): JsonResponse
{
    // ...
}
```

### Endpoint con Parámetros de Ruta

```php
#[OA\Get(
    path: "/recursos/{id}",
    summary: "Obtener recurso por ID",
    tags: ["Recursos"],
    parameters: [
        new OA\Parameter(
            name: "id",
            description: "ID del recurso",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer", example: 1)
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Recurso encontrado"),
        new OA\Response(response: 404, description: "No encontrado")
    ]
)]
public function show(int $id): JsonResponse
{
    // ...
}
```

### Endpoint con Request Body (POST/PUT)

```php
#[OA\Post(
    path: "/recursos",
    summary: "Crear nuevo recurso",
    tags: ["Recursos"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["nombre", "email"],
            properties: [
                new OA\Property(
                    property: "nombre",
                    type: "string",
                    example: "Juan Pérez"
                ),
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "juan@ejemplo.com"
                ),
                new OA\Property(
                    property: "activo",
                    type: "boolean",
                    example: true,
                    nullable: true
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Creado exitosamente"),
        new OA\Response(response: 422, description: "Error de validación")
    ]
)]
public function store(Request $request): JsonResponse
{
    // ...
}
```

### Respuestas con Esquemas Reutilizables

En `ApiController.php` se definieron esquemas comunes:

```php
#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string"),
        new OA\Property(property: "errors", type: "object")
    ]
)]
```

Para usarlos en las respuestas:

```php
new OA\Response(
    response: 422,
    description: "Error de validación",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)
```

---

## Comandos Útiles

### Generar Documentación

```bash
php artisan l5-swagger:generate
```

> **Nota**: Con `generate_always = true` en la configuración, la documentación se regenera automáticamente en cada request (recomendado solo para desarrollo).

### Verificar Configuración

```bash
php artisan config:show l5-swagger
```

### Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan l5-swagger:generate
```

### Ver Rutas de API

```bash
php artisan route:list --path=api
```

---

## Ejemplos Completos

### Ejemplo: Endpoint GET con Filtros

```php
#[OA\Get(
    path: "/estudiantes",
    summary: "Listar estudiantes",
    tags: ["Estudiantes"],
    parameters: [
        new OA\Parameter(
            name: "search",
            description: "Buscar por nombre o documento",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string")
        ),
        new OA\Parameter(
            name: "estado",
            description: "Filtrar por estado",
            in: "query",
            required: false,
            schema: new OA\Schema(
                type: "string",
                enum: ["activo", "inactivo", "egresado"]
            )
        ),
        new OA\Parameter(
            name: "page",
            description: "Número de página",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "integer", default: 1)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista de estudiantes",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(type: "object")
                    ),
                    new OA\Property(
                        property: "meta",
                        type: "object",
                        properties: [
                            new OA\Property(property: "total", type: "integer"),
                            new OA\Property(property: "per_page", type: "integer"),
                            new OA\Property(property: "current_page", type: "integer")
                        ]
                    )
                ]
            )
        )
    ]
)]
public function index(Request $request): JsonResponse
{
    // ...
}
```

### Ejemplo: Endpoint DELETE

```php
#[OA\Delete(
    path: "/recursos/{id}",
    summary: "Eliminar recurso",
    tags: ["Recursos"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 204, description: "Eliminado correctamente"),
        new OA\Response(response: 404, description: "No encontrado"),
        new OA\Response(response: 409, description: "Conflicto - tiene dependencias")
    ]
)]
public function destroy(int $id): JsonResponse
{
    // ...
}
```

---

## Solución de Problemas

### Error: "No operations defined"

**Causa**: No se encontraron anotaciones válidas.

**Solución**:
1. Verificar que uses `OpenApi\Attributes as OA`
2. Asegurar que la sintaxis de atributos PHP 8 es correcta
3. Ejecutar `php artisan l5-swagger:generate` manualmente

### Error: "Unable to find @OA\Info"

**Causa**: Falta la anotación `@OA\Info` principal.

**Solución**: Agregar en `ApiController.php`:
```php
#[OA\Info(version: "1.0.0", title: "Mi API")]
```

### La documentación no se actualiza

**Solución**:
```bash
# Limpiar caché
php artisan cache:clear

# Regenerar documentación
php artisan l5-swagger:generate
```

### Error 500 al acceder a /api/documentation

**Solución**:
1. Verificar logs: `storage/logs/laravel.log`
2. Asegurar que `storage/api-docs/` existe y tiene permisos de escritura
3. Ejecutar `php artisan storage:link` si es necesario

---

## Checklist para Nuevos Controladores

Al crear un nuevo controlador API:

- [ ] Agregar `use OpenApi\Attributes as OA;`
- [ ] Documentar cada método público con anotaciones `#[OA\Get/Post/Put/Delete...]`
- [ ] Incluir el tag correspondiente
- [ ] Documentar parámetros de ruta (`in: "path"`)
- [ ] Documentar parámetros de query si aplica (`in: "query"`)
- [ ] Documentar el request body para POST/PUT
- [ ] Incluir todas las respuestas posibles (200, 201, 404, 422, etc.)
- [ ] Ejecutar `php artisan l5-swagger:generate` para verificar

---

## Recursos Adicionales

- [Documentación Oficial L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger/wiki)
- [Especificación OpenAPI 3.0](https://swagger.io/specification/)
- [zircote/swagger-php Attributes](https://zircote.github.io/swagger-php/guide/attributes.html)
