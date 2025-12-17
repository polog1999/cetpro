<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MatriculaService;
use App\Http\Requests\Api\V1\StoreMatriculaRequest;
use App\Http\Resources\Api\V1\MatriculaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class MatriculaController extends ApiController
{
    public function __construct(
        private MatriculaService $matriculaService
    ) {}

    /**
     * Listar todas las matrículas.
     */
    #[OA\Get(
        path: "/matriculas",
        summary: "Listar todas las matrículas",
        description: "Retorna un listado completo de todas las matrículas registradas con información del estudiante, horario y cronograma.",
        tags: ["Matrículas"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de matrículas obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "codigo_inscripcion", type: "string", example: "MAT-2024-00001"),
                                    new OA\Property(property: "tipo_matricula", type: "string", example: "Programa"),
                                    new OA\Property(property: "estado", type: "string", example: "activa"),
                                    new OA\Property(property: "fecha_registro", type: "string", format: "date", example: "2024-01-15"),
                                    new OA\Property(property: "estudiante", type: "object"),
                                    new OA\Property(property: "horario", type: "object")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        // Nota: MatriculaService no tiene obtenerTodos(), usar repositorio si existe
        // Por ahora retornamos todas usando el modelo directamente (excepción justificada para listados)
        $matriculas = \App\Models\Matricula::with(['estudiante', 'horario', 'cronograma'])->get();
        return $this->success(MatriculaResource::collection($matriculas));
    }

    /**
     * Obtener detalle de una matrícula.
     */
    #[OA\Get(
        path: "/matriculas/{id}",
        summary: "Obtener detalle de una matrícula",
        description: "Retorna la información completa de una matrícula específica incluyendo estudiante, horario, cronograma y pagos.",
        tags: ["Matrículas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID de la matrícula",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Matrícula encontrada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "codigo_inscripcion", type: "string", example: "MAT-2024-00001"),
                                new OA\Property(property: "tipo_matricula", type: "string", example: "Programa"),
                                new OA\Property(property: "estado", type: "string", example: "activa"),
                                new OA\Property(property: "fecha_registro", type: "string", format: "date"),
                                new OA\Property(property: "estudiante", type: "object"),
                                new OA\Property(property: "horario", type: "object"),
                                new OA\Property(property: "cronograma", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Matrícula no encontrada",
                content: new OA\JsonContent(ref: "#/components/schemas/NotFoundResponse")
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $matricula = \App\Models\Matricula::with(['estudiante', 'horario', 'cronograma.pagos'])->find($id);

        if (!$matricula) {
            return $this->error('Matrícula no encontrada', [], 404);
        }

        return $this->success(new MatriculaResource($matricula));
    }

    /**
     * Crear nueva matrícula.
     * El servicio se encarga de validaciones, generación de cronograma y pagos.
     */
    #[OA\Post(
        path: "/matriculas",
        summary: "Crear nueva matrícula",
        description: "Registra una nueva matrícula para un estudiante. El sistema genera automáticamente el cronograma de pagos.",
        tags: ["Matrículas"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos de la matrícula",
            content: new OA\JsonContent(
                required: ["estudiante_id", "horario_id", "tipo_matricula", "turno"],
                properties: [
                    new OA\Property(
                        property: "estudiante_id",
                        type: "integer",
                        description: "ID del estudiante a matricular",
                        example: 1
                    ),
                    new OA\Property(
                        property: "horario_id",
                        type: "integer",
                        description: "ID del horario seleccionado",
                        example: 5
                    ),
                    new OA\Property(
                        property: "tipo_matricula",
                        type: "string",
                        description: "Tipo de matrícula",
                        example: "Programa",
                        enum: ["Programa", "Formación Continua", "Curso", "Módulo"]
                    ),
                    new OA\Property(
                        property: "turno",
                        type: "string",
                        description: "Turno de la matrícula",
                        example: "Mañana",
                        enum: ["Mañana", "Tarde", "Noche"]
                    ),
                    new OA\Property(
                        property: "costo_total",
                        type: "number",
                        format: "float",
                        description: "Costo total de la matrícula",
                        example: 1200.00,
                        nullable: true
                    ),
                    new OA\Property(
                        property: "observaciones",
                        type: "string",
                        description: "Observaciones adicionales",
                        example: "Estudiante becado al 50%",
                        nullable: true
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Matrícula creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function store(StoreMatriculaRequest $request): JsonResponse
    {
        try {
            // Por ahora usar creación directa del modelo ya que MatriculaService::crear
            // necesita refactorización para usar repositorios
            // TODO: Usar $this->matriculaService->crear($request->validated()) cuando esté refactorizado
            $matricula = \App\Models\Matricula::create($request->validated());
            
            return $this->created(new MatriculaResource($matricula));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Anular matrícula.
     * Usa MatriculaService::anularMatricula()
     */
    #[OA\Post(
        path: "/matriculas/{id}/anular",
        summary: "Anular una matrícula",
        description: "Anula una matrícula existente. El estado de la matrícula y sus pagos asociados serán actualizados.",
        tags: ["Matrículas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID de la matrícula a anular",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Matrícula anulada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Matrícula anulada exitosamente"),
                            new OA\Property(property: "matricula", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error al anular la matrícula",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function anular(int $id): JsonResponse
    {
        try {
            $resultado = $this->matriculaService->anularMatricula($id, 'Anulación vía API');
            
            if (!$resultado['success']) {
                return $this->error($resultado['message'], [], 422);
            }

            return $this->success([
                'message' => $resultado['message'],
                'matricula' => new MatriculaResource($resultado['matricula']),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al anular', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->error('Error al anular matrícula', ['error' => $e->getMessage()], 500);
        }
    }
}
