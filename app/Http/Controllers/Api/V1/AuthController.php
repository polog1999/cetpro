<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * Controlador de Autenticación para la API.
 * 
 * Maneja login, logout y consulta del usuario autenticado.
 */
class AuthController extends ApiController
{
    /**
     * Login - Obtener token de acceso
     */
    #[OA\Post(
        path: "/auth/login",
        summary: "Iniciar sesión y obtener token",
        tags: ["Autenticación"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["usuario", "password"],
                properties: [
                    new OA\Property(property: "usuario", type: "string", example: "admin"),
                    new OA\Property(property: "password", type: "string", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login exitoso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "user", type: "object"),
                            new OA\Property(property: "token", type: "string"),
                            new OA\Property(property: "token_type", type: "string", example: "Bearer")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Credenciales inválidas")
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = Usuario::where('usuario', $request->usuario)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'usuario' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->activo) {
            throw ValidationException::withMessages([
                'usuario' => ['El usuario está desactivado. Contacte al administrador.'],
            ]);
        }

        // Revocar tokens anteriores (opcional - para sesión única)
        // $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'usuario' => $user->usuario,
                'nombre' => $user->getFilamentName(),
                'role' => $user->role?->nombre,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout - Revocar token actual
     */
    #[OA\Post(
        path: "/auth/logout",
        summary: "Cerrar sesión (revocar token)",
        tags: ["Autenticación"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Sesión cerrada exitosamente"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return $this->success([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Me - Obtener información del usuario autenticado
     */
    #[OA\Get(
        path: "/auth/me",
        summary: "Obtener usuario autenticado",
        tags: ["Autenticación"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Información del usuario",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "usuario", type: "string"),
                            new OA\Property(property: "nombre", type: "string"),
                            new OA\Property(property: "role", type: "string")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->success([
            'id' => $user->id,
            'usuario' => $user->usuario,
            'nombre' => $user->getFilamentName(),
            'role' => $user->role?->nombre,
            'empleado' => $user->empleado ? [
                'id' => $user->empleado->id,
                'nombre' => $user->empleado->nombre,
                'apellido_paterno' => $user->empleado->apellido_paterno,
            ] : null,
        ]);
    }

    /**
     * Logout All - Revocar todos los tokens del usuario
     */
    #[OA\Post(
        path: "/auth/logout-all",
        summary: "Cerrar todas las sesiones",
        tags: ["Autenticación"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Todas las sesiones cerradas"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        
        return $this->success([
            'message' => 'Todas las sesiones han sido cerradas'
        ]);
    }
}
