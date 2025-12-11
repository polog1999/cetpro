<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\HorarioResource;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;

class HorarioController extends ApiController
{
    public function index(): JsonResponse
    {
        $horarios = Horario::with(['programa', 'docente'])->get();
        return $this->success(HorarioResource::collection($horarios));
    }

    public function show(int $id): JsonResponse
    {
        $horario = Horario::with(['programa', 'docente', 'matriculas'])->find($id);

        if (!$horario) {
            return $this->error('Horario no encontrado', [], 404);
        }

        return $this->success(new HorarioResource($horario));
    }

    /**
     * Obtener vacantes disponibles de un horario.
     */
    public function vacantes(int $id): JsonResponse
    {
        $horario = Horario::find($id);

        if (!$horario) {
            return $this->error('Horario no encontrado', [], 404);
        }

        $matriculados = \App\Models\Matricula::where('horario_id', $id)
            ->where('estado', '!=', \App\Enums\EstadoMatricula::ANULADO)
            ->count();

        $vacantesDisponibles = $horario->vacantes - $matriculados;

        return $this->success([
            'horario_id' => $id,
            'vacantes_totales' => $horario->vacantes,
            'matriculados' => $matriculados,
            'vacantes_disponibles' => max(0, $vacantesDisponibles),
        ]);
    }

    public function store(\App\Http\Requests\Api\V1\StoreHorarioRequest $request): JsonResponse
    {
        $horario = Horario::create($request->validated());
        return $this->created(new HorarioResource($horario));
    }

    public function update(\App\Http\Requests\Api\V1\UpdateHorarioRequest $request, int $id): JsonResponse
    {
        $horario = Horario::find($id);

        if (!$horario) {
            return $this->error('Horario no encontrado', [], 404);
        }

        $horario->update($request->validated());
        return $this->success(new HorarioResource($horario->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $horario = Horario::find($id);

        if (!$horario) {
            return $this->error('Horario no encontrado', [], 404);
        }

        if ($horario->matriculas()->exists()) {
            return $this->error('No se puede eliminar el horario porque tiene matrículas asociadas', [], 422);
        }

        $horario->delete();
        return $this->noContent();
    }
}
