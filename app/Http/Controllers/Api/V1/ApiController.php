<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Controlador base para la API REST.
 * 
 * Proporciona métodos helper para respuestas consistentes.
 */
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
