<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * Controlador base para la API REST.
 * 
 * Proporciona métodos helper para respuestas consistentes.
 */
#[OA\Info(
    version: "1.0.0",
    title: "CETPRO MDLM API",
    description: "API REST para el Sistema de Gestión del Centro de Educación Técnico Productiva María de Lourdes Muñoz.

## Módulos Disponibles
- **Estudiantes**: Gestión de estudiantes y apoderados
- **Matrículas**: Inscripciones, cronogramas de pago y pagos
- **Académico**: Programas, cursos, especialidades y horarios
- **RRHH**: Docentes y empleados
- **Sistema**: Usuarios, roles y permisos

## Autenticación
Esta API utiliza **Laravel Sanctum** para autenticación mediante Bearer Token.

### Pasos para autenticarse:
1. **Obtener token:** `POST /api/v1/auth/login` con `usuario` y `password`
2. **Usar token:** Incluir header `Authorization: Bearer {token}` en cada petición
3. **Cerrar sesión:** `POST /api/v1/auth/logout`

### Endpoint público (sin autenticación):
- `POST /api/v1/auth/login`

### Endpoints protegidos (requieren token):
- Todos los demás endpoints",
    contact: new OA\Contact(
        name: "Soporte CETPRO",
        email: "soporte@cetpro-mdlm.edu.pe"
    )
)]
#[OA\Server(
    url: "http://cetpro-mdlm.test/api/v1",
    description: "Servidor Laravel Herd"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Token de autenticación Sanctum. Obtener via POST /auth/login"
)]
#[OA\Tag(name: "Autenticación", description: "Login, logout y gestión de sesión")]
#[OA\Tag(name: "Pagos", description: "Gestión de pagos de cuotas")]
#[OA\Tag(name: "Matrículas", description: "Gestión de matrículas de estudiantes")]
#[OA\Tag(name: "Estudiantes", description: "Gestión de estudiantes")]
#[OA\Tag(name: "Programas", description: "Gestión de programas académicos")]
#[OA\Tag(name: "Cursos", description: "Gestión de cursos")]
#[OA\Tag(name: "Horarios", description: "Gestión de horarios")]
#[OA\Tag(name: "Docentes", description: "Gestión de docentes")]

// Esquemas de respuesta comunes
#[OA\Schema(
    schema: "SuccessResponse",
    type: "object",
    properties: [
        new OA\Property(property: "data", type: "object", description: "Datos de la respuesta")
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Error al procesar la solicitud"),
        new OA\Property(property: "errors", type: "object", description: "Detalles de los errores de validación")
    ]
)]
#[OA\Schema(
    schema: "NotFoundResponse",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", example: "Recurso no encontrado")
    ]
)]
abstract class ApiController extends Controller
{
    /**
     * Respuesta exitosa.
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status);
    }

    /**
     * Respuesta de error.
     *
     * @param string $message
     * @param array $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de recurso creado.
     *
     * @param mixed $data
     * @return JsonResponse
     */
    protected function created($data): JsonResponse
    {
        return $this->success($data, 201);
    }

    /**
     * Respuesta sin contenido (para deletes exitosos).
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
