<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PagoService;
use App\Http\Requests\Api\V1\RegistrarPagoRequest;
use App\Http\Requests\Api\V1\RevertirPagoRequest;
use App\Http\Resources\Api\V1\PagoResource;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class PagoController extends ApiController
{
    public function __construct(
        private PagoService $pagoService
    ) {}

    /**
     * Obtener detalle de un pago.
     */
    #[OA\Get(
        path: "/pagos/{id}",
        summary: "Obtener detalle de un pago",
        description: "Retorna la información detallada de un pago específico incluyendo datos del cronograma, matrícula y estudiante.",
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del pago",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago encontrado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "monto", type: "number", format: "float", example: 150.00),
                            new OA\Property(property: "fecha_vencimiento", type: "string", format: "date", example: "2024-12-31"),
                            new OA\Property(property: "fecha_pago", type: "string", format: "date", example: "2024-12-15", nullable: true),
                            new OA\Property(property: "estado", type: "string", example: "pagado", enum: ["pendiente", "pagado", "vencido", "anulado"]),
                            new OA\Property(property: "metodo_pago", type: "string", example: "efectivo", nullable: true),
                            new OA\Property(property: "numero_cuota", type: "integer", example: 1)
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $pago = Pago::with(['cronograma.matricula.estudiante'])->find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        return $this->success(new PagoResource($pago));
    }

    /**
     * Registrar pago de una cuota.
     */
    #[OA\Post(
        path: "/pagos/{id}/registrar",
        summary: "Registrar pago de una cuota",
        description: "Marca una cuota como pagada registrando el método de pago y opcionalmente la evidencia del pago.",
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del pago a registrar",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos del pago",
            content: new OA\JsonContent(
                required: ["metodo_pago"],
                properties: [
                    new OA\Property(
                        property: "metodo_pago",
                        type: "string",
                        description: "Método de pago utilizado",
                        example: "efectivo",
                        enum: ["efectivo", "transferencia", "yape", "plin", "deposito"]
                    ),
                    new OA\Property(
                        property: "evidencia_path",
                        type: "string",
                        description: "Ruta del archivo de evidencia del pago (voucher, captura, etc.)",
                        example: "pagos/voucher_001.pdf",
                        nullable: true
                    ),
                    new OA\Property(
                        property: "usuario_id",
                        type: "integer",
                        description: "ID del usuario que registra el pago (opcional, por defecto el usuario autenticado)",
                        example: 1,
                        nullable: true
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago registrado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Pago registrado exitosamente"),
                            new OA\Property(property: "pago", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function registrar(RegistrarPagoRequest $request, int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->registrarPago(
                pago: $pago,
                metodoPago: $request->metodo_pago,
                evidenciaPath: $request->evidencia_path ?? null,
                usuarioId: $request->usuario_id ?? auth()->id()
            );

            return $this->success([
                'message' => 'Pago registrado exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al registrar pago', $e->errors(), 422);
        }
    }

    /**
     * Anular pago.
     */
    #[OA\Post(
        path: "/pagos/{id}/anular",
        summary: "Anular un pago",
        description: "Marca un pago como anulado, revirtiendo su estado a pendiente.",
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del pago a anular",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago anulado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Pago anulado exitosamente"),
                            new OA\Property(property: "pago", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Error al anular el pago",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function anular(int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->anularPago($pago);

            return $this->success([
                'message' => 'Pago anulado exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al anular pago', $e->errors(), 422);
        }
    }

    /**
     * Revertir pago (requiere permisos especiales).
     */
    #[OA\Post(
        path: "/pagos/{id}/revertir",
        summary: "Revertir un pago",
        description: "Revierte completamente un pago registrado, devolviendo el estado a pendiente. Requiere permisos especiales y un motivo justificado.",
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del pago a revertir",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Motivo de la reversión",
            content: new OA\JsonContent(
                required: ["motivo"],
                properties: [
                    new OA\Property(
                        property: "motivo",
                        type: "string",
                        description: "Motivo por el cual se revierte el pago",
                        example: "Error en el monto registrado"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago revertido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Pago revertido exitosamente"),
                            new OA\Property(property: "pago", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Error al revertir el pago",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function revertir(RevertirPagoRequest $request, int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->revertirPago($pago, $request->motivo);

            return $this->success([
                'message' => 'Pago revertido exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al revertir pago', $e->errors(), 422);
        }
    }
}
