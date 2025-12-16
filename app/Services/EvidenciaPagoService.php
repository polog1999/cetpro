<?php

namespace App\Services;

use App\Models\Pago;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio para gestión de evidencias de pago.
 * Maneja carga, descarga y validación de archivos de evidencia.
 */
class EvidenciaPagoService
{
    /**
     * Tipos de archivo permitidos
     */
    const TIPOS_PERMITIDOS = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Tamaño máximo permitido en bytes (5MB)
     */
    const TAMANO_MAXIMO = 5 * 1024 * 1024;

    /**
     * Directorio donde se almacenan las evidencias
     */
    const DIRECTORIO_EVIDENCIAS = 'evidencias-pagos';

    /**
     * Valida un archivo de evidencia.
     *
     * @param UploadedFile $archivo
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarArchivo(UploadedFile $archivo): array
    {
        $errores = [];

        // Validar tipo de archivo
        $extension = strtolower($archivo->getClientOriginalExtension());
        if (!in_array($extension, self::TIPOS_PERMITIDOS)) {
            $tiposPermitidos = implode(', ', self::TIPOS_PERMITIDOS);
            $errores[] = "Tipo de archivo no permitido. Tipos permitidos: {$tiposPermitidos}";
        }

        // Validar tamaño
        if ($archivo->getSize() > self::TAMANO_MAXIMO) {
            $tamanioMB = self::TAMANO_MAXIMO / (1024 * 1024);
            $errores[] = "El archivo excede el tamaño máximo permitido de {$tamanioMB}MB";
        }

        // Validar que sea un archivo válido
        if (!$archivo->isValid()) {
            $errores[] = "El archivo no es válido o está corrupto";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Guarda un archivo de evidencia de pago.
     *
     * @param UploadedFile $archivo
     * @param int $pagoId
     * @return string Path del archivo guardado
     * @throws ValidationException
     */
    public function guardarEvidencia(UploadedFile $archivo, int $pagoId): string
    {
        // Validar el archivo
        $validacion = $this->validarArchivo($archivo);
        if (!$validacion['valido']) {
            throw ValidationException::withMessages([
                'evidencia' => $validacion['errores'],
            ]);
        }

        // Obtener el pago
        $pago = Pago::findOrFail($pagoId);

        // Generar nombre único para el archivo
        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = "pago_{$pago->id}_{$pago->codigo}_" . time() . ".{$extension}";

        // Guardar el archivo
        $path = $archivo->storeAs(
            self::DIRECTORIO_EVIDENCIAS,
            $nombreArchivo,
            'private' // Storage privado para controlar acceso
        );

        // Si ya tenía una evidencia anterior, eliminarla
        if ($pago->evidencia_path) {
            $this->eliminarEvidencia($pago->evidencia_path);
        }

        return $path;
    }

    /**
     * Elimina un archivo de evidencia.
     *
     * @param string $path
     * @return bool
     */
    public function eliminarEvidencia(string $path): bool
    {
        if (Storage::disk('private')->exists($path)) {
            return Storage::disk('private')->delete($path);
        }

        return false;
    }

    /**
     * Descarga una evidencia de pago con control de acceso.
     *
     * @param int $pagoId
     * @param int $usuarioId Usuario que solicita la descarga
     * @return StreamedResponse
     * @throws ValidationException
     */
    public function descargarEvidencia(int $pagoId, int $usuarioId): StreamedResponse
    {
        $pago = Pago::findOrFail($pagoId);

        // Verificar que existe evidencia
        if (!$pago->evidencia_path) {
            throw ValidationException::withMessages([
                'evidencia' => 'Este pago no tiene evidencia adjunta.',
            ]);
        }

        // Verificar permisos
        if (!$this->usuarioPuedeVerEvidencia($usuarioId, $pago)) {
            throw ValidationException::withMessages([
                'permiso' => 'No tiene permisos para ver esta evidencia.',
            ]);
        }

        // Verificar que el archivo existe
        if (!Storage::disk('private')->exists($pago->evidencia_path)) {
            throw ValidationException::withMessages([
                'evidencia' => 'El archivo de evidencia no existe o fue eliminado.',
            ]);
        }

        // Retornar respuesta de descarga
        return Storage::disk('private')->download(
            $pago->evidencia_path,
            $this->obtenerNombreDescarga($pago)
        );
    }

    /**
     * Obtiene la URL segura para visualizar una evidencia.
     *
     * @param int $pagoId
     * @param int $usuarioId
     * @return string
     * @throws ValidationException
     */
    public function obtenerUrlVisualizacion(int $pagoId, int $usuarioId): string
    {
        $pago = Pago::findOrFail($pagoId);

        // Verificar permisos
        if (!$this->usuarioPuedeVerEvidencia($usuarioId, $pago)) {
            throw ValidationException::withMessages([
                'permiso' => 'No tiene permisos para ver esta evidencia.',
            ]);
        }

        // Generar URL temporal (válida por 1 hora)
        if (Storage::disk('private')->exists($pago->evidencia_path)) {
            return Storage::disk('private')->temporaryUrl(
                $pago->evidencia_path,
                now()->addHour()
            );
        }

        throw ValidationException::withMessages([
            'evidencia' => 'El archivo de evidencia no existe.',
        ]);
    }

    /**
     * Verifica si un usuario puede ver la evidencia de un pago.
     *
     * @param int $usuarioId
     * @param Pago $pago
     * @return bool
     */
    protected function usuarioPuedeVerEvidencia(int $usuarioId, Pago $pago): bool
    {
        $usuario = \App\Models\Usuario::find($usuarioId);

        if (!$usuario) {
            return false;
        }

        // Administradores pueden ver todo
        if ($usuario->role?->es_admin) {
            return true;
        }

        // Verificar permisos específicos
        if ($usuario->canAccessResource('pagos')) {
            return true;
        }

        // El usuario que registró el pago puede verlo
        if ($pago->usuario_id === $usuarioId) {
            return true;
        }

        // Si es el estudiante dueño de la matrícula, puede verlo
        $estudiante = $pago->cronograma?->matricula?->estudiante;
        if ($estudiante && $estudiante->usuario_id === $usuarioId) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene un nombre descriptivo para la descarga.
     *
     * @param Pago $pago
     * @return string
     */
    protected function obtenerNombreDescarga(Pago $pago): string
    {
        $extension = pathinfo($pago->evidencia_path, PATHINFO_EXTENSION);
        $estudiante = $pago->cronograma?->matricula?->estudiante;
        $nombreEstudiante = $estudiante 
            ? str_replace(' ', '_', $estudiante->nombre_completo)
            : 'estudiante';

        return "evidencia_pago_{$nombreEstudiante}_{$pago->codigo}.{$extension}";
    }

    /**
     * Obtiene información sobre las evidencias del sistema.
     *
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        $totalPagos = Pago::count();
        $pagosConEvidencia = Pago::whereNotNull('evidencia_path')->count();
        $pagosSinEvidencia = $totalPagos - $pagosConEvidencia;

        $porcentajeConEvidencia = $totalPagos > 0 
            ? round(($pagosConEvidencia / $totalPagos) * 100, 2) 
            : 0;

        return [
            'total_pagos' => $totalPagos,
            'con_evidencia' => $pagosConEvidencia,
            'sin_evidencia' => $pagosSinEvidencia,
            'porcentaje_con_evidencia' => $porcentajeConEvidencia,
        ];
    }

    /**
     * Limpia evidencias huérfanas (archivos sin registro en BD).
     *
     * @return int Número de archivos eliminados
     */
    public function limpiarEvidenciasHuerfanas(): int
    {
        $archivosEnDisco = Storage::disk('private')->files(self::DIRECTORIO_EVIDENCIAS);
        $pathsEnBD = Pago::whereNotNull('evidencia_path')
            ->pluck('evidencia_path')
            ->toArray();

        $eliminados = 0;

        foreach ($archivosEnDisco as $archivo) {
            if (!in_array($archivo, $pathsEnBD)) {
                Storage::disk('private')->delete($archivo);
                $eliminados++;
            }
        }

        return $eliminados;
    }
}
