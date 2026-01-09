<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Cronograma;
use App\Models\Pago;
use App\Models\Usuario;
use App\Models\Role;
use App\Enums\TipoDocumento;
use App\Enums\TipoGenero;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Enums\DistritoLima;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class LegacyRegistrationService
{
    public function __construct(
        private OracleTusneService $oracleService,
        private EstudianteService $estudianteService
    ) {}

    /**
     * Registra un alumno antiguo desde Oracle en PostgreSQL.
     * 
     * @param array $oracleData Datos obtenidos de Oracle (SMACARNOM + PAGOS)
     * @param int $horarioId ID del horario local al que se matriculará
     * @param array $selectedPagos Indices de los pagos seleccionados para importar
     * @return Estudiante
     * @throws Exception
     */
    public function registrarDesdeOracle(array $oracleData, int $horarioId, array $selectedPagos = []): Estudiante
    {
        return DB::transaction(function () use ($oracleData, $horarioId, $selectedPagos) {
            // 1. Crear o actualizar Estudiante
            $estudiante = $this->crearOActualizarEstudiante($oracleData);

            // 2. Crear Matrícula
            $matricula = $this->crearMatricula($estudiante, $horarioId);

            // 3. Crear Cronograma
            $cronograma = $this->crearCronograma($matricula);

            // 4. Importar Pagos seleccionados
            $this->importarPagos($cronograma, $oracleData['PAGOS'] ?? [], $selectedPagos);

            return $estudiante;
        });
    }

    /**
     * Crea o actualiza el registro de Estudiante en PostgreSQL.
     */
    private function crearOActualizarEstudiante(array $data): Estudiante
    {
        $nroDocumento = trim($data['MCNNRODI']);
        $tipoDocStr = $this->mapearTipoDocumento($data['MCNTIPODI'] ?? 'DOI01');
        
        $estudiante = Estudiante::where('nro_documento', $nroDocumento)->first();

        $estudianteData = [
            'tipo_documento'    => $tipoDocStr,
            'nro_documento'     => $nroDocumento,
            'nombres'           => trim($data['MCNNOMBRE'] ?? ''),
            'apellido_paterno'  => trim($data['MCNAPEPAT'] ?? ''),
            'apellido_materno'  => trim($data['MCNAPEMAT'] ?? ''),
            'genero'            => $data['SEXO'] === 'F' ? TipoGenero::FEMENINO : TipoGenero::MASCULINO,
            'fecha_nacimiento'  => !empty($data['MCNFECNAC']) ? Carbon::parse($data['MCNFECNAC']) : null,
            'telefono'          => $data['MCNROTELE'] ?? $data['MCNTELE'] ?? null,
            'direccion'         => $data['MCNDIRE'] ?? null,
            'email'             => $data['MCNEMAIL'] ?? null,
            'codigo_contribuyente' => $data['MCNCONTRIB'],
            'distrito'          => $this->mapearDistrito($data['DISTRICODI'] ?? null, $data['DISTRIDESC'] ?? null),
        ];

        if ($estudiante) {
            $estudiante->update($estudianteData);
        } else {
            // Usar el servicio para crear con usuario automático
            // Nota: El servicio requiere repositorios en el constructor, 
            // pero aquí el modelo es más directo si no queremos pasar por validaciones de "nuevo"
            $estudiante = Estudiante::create($estudianteData);
            
            // Llamar a crear usuario del servicio (es protected, así que lo haré manual o usaré el service si es accesible)
            $this->crearUsuarioParaEstudiante($estudiante);
        }

        return $estudiante;
    }

    private function crearUsuarioParaEstudiante(Estudiante $estudiante): void
    {
        $rolAlumno = Role::where('nombre', 'Alumno')->first();
        if ($rolAlumno) {
            Usuario::firstOrCreate(
                ['usuario' => $estudiante->nro_documento],
                [
                    'password' => $estudiante->nro_documento,
                    'estudiante_id' => $estudiante->id,
                    'role_id' => $rolAlumno->id,
                    'activo' => true,
                ]
            );
        }
    }

    /**
     * Crea la Matrícula para el alumno.
     */
    private function crearMatricula(Estudiante $estudiante, int $horarioId): Matricula
    {
        // Verificar si ya tiene una matrícula activa en este horario para evitar duplicados
        $existente = Matricula::where('estudiante_id', $estudiante->id)
            ->where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->first();

        if ($existente) {
            return $existente;
        }

        // Generar código de inscripción (simulado de MatriculaService)
        $year = now()->format('Y');
        $codigoInscripcion = "{$year}{$estudiante->nro_documento}{$horarioId}";

        $matricula = new Matricula([
            'codigo_inscripcion' => $codigoInscripcion,
            'estudiante_id'      => $estudiante->id,
            'horario_id'         => $horarioId,
            'estado'             => EstadoMatricula::ENPROCESO,
            'tipo_matricula'     => TipoMatricula::PROGRAMA, // Por defecto programa para antiguos
        ]);
        
        // IMPORTANTE: Evitar generar cronograma automático (lo crearemos manualmente con datos de Oracle)
        $matricula->skipCronogramaGeneration = true;
        $matricula->save();

        return $matricula;
    }

    /**
     * Crea el Cronograma básico.
     */
    private function crearCronograma(Matricula $matricula): Cronograma
    {
        $cronograma = $matricula->cronograma;
        if (!$cronograma) {
            $cronograma = $matricula->cronograma()->create([
                'num_cuotas'  => 0, // Se actualizará al importar pagos
                'monto_total' => 0,
            ]);
        }
        return $cronograma;
    }

    /**
     * Importa los pagos seleccionados desde Oracle.
     * NOTA: Se asume que $pagosOracle ya contiene SOLO los pagos seleccionados (pre-filtrados en el Page).
     */
    private function importarPagos(Cronograma $cronograma, array $pagosOracle, array $selectedIndices = []): void
    {
        $montoTotal = $cronograma->monto_total ?? 0;
        
        // Obtener la última cuota existente para seguir la secuencia
        $ultimaCuota = $cronograma->pagos()->max('nro_cuota') ?? 0;
        $numCuotas = $ultimaCuota;

        // Obtener liquidaciones ya importadas para evitar duplicados
        $liquidacionesExistentes = $cronograma->pagos()
            ->whereNotNull('num_liquidacion')
            ->pluck('num_liquidacion')
            ->toArray();

        foreach ($pagosOracle as $pagoData) {
            // Evitar duplicar el mismo pago si ya fue importado
            if (in_array($pagoData->LIQUIDACION, $liquidacionesExistentes)) {
                continue;
            }

            $numCuotas++;
            $monto = (float) $pagoData->IMPORTE;
            $montoTotal += $monto;

            $estadoOracle = $pagoData->ESTADO;
            
            Pago::create([
                'cronograma_id'     => $cronograma->id,
                'nro_cuota'         => $numCuotas,
                'monto'             => $monto,
                'estado'            => $estadoOracle,
                'fecha_vencimiento' => !empty($pagoData->EMITIDO) ? Carbon::createFromFormat('d/m/Y', $pagoData->EMITIDO) : null,
                'fecha_pago'        => !empty($pagoData->PAGADO) ? Carbon::createFromFormat('d/m/Y', $pagoData->PAGADO) : null,
                'metodo_pago'       => 'IMPORTADO',
                'num_liquidacion'   => $pagoData->LIQUIDACION,
                'fecha_liquidacion' => !empty($pagoData->EMITIDO) ? Carbon::createFromFormat('d/m/Y', $pagoData->EMITIDO) : null,
            ]);
        }

        $cronograma->update([
            'num_cuotas'  => $cronograma->pagos()->count(), // Recalcular total real
            'monto_total' => $montoTotal,
        ]);
    }

    private function mapearTipoDocumento(string $tipo): string
    {
        $mapeo = [
            'DOI01' => 'DNI',
            'DOI06' => 'Carnet de extranjeria',
            'DOI11' => 'Pasaporte',
            'DOI19' => 'PTP',
            'DOI03' => 'RUC',
        ];
        return $mapeo[$tipo] ?? 'DNI';
    }

    private function mapearDistrito(?string $codigo, ?string $nombre = null): ?DistritoLima
    {
        // 1. Intentar buscar por nombre (si viene de SMADISTRITO.DISTRIDESC)
        if ($nombre) {
            $nombre = trim($nombre);
            // Intentar match directo o case-insensitive
            foreach (DistritoLima::cases() as $distritoEnum) {
                if (mb_strtolower($distritoEnum->value) === mb_strtolower($nombre)) {
                    return $distritoEnum;
                }
            }
        }

        // 2. Fallback por código (mapeo hardcoded por si acaso)
        if (!$codigo) return null;
        
        $mapeo = [
            '01' => DistritoLima::LIMA, '02' => DistritoLima::ANCON, '03' => DistritoLima::ATE,
            '04' => DistritoLima::BARRANCO, '05' => DistritoLima::BRENA, '06' => DistritoLima::CARABAYLLO,
            '07' => DistritoLima::COMAS, '08' => DistritoLima::CHACLACAYO, '09' => DistritoLima::CHORRILLOS,
            '10' => DistritoLima::EL_AGUSTINO, '11' => DistritoLima::JESUS_MARIA, '12' => DistritoLima::LA_MOLINA,
            '13' => DistritoLima::LA_VICTORIA, '14' => DistritoLima::LINCE, '15' => DistritoLima::LURIGANCHO, 
            '16' => DistritoLima::LURIN, '17' => DistritoLima::MAGDALENA_DEL_MAR, '18' => DistritoLima::MIRAFLORES,
            '19' => DistritoLima::PACHACAMAC, '20' => DistritoLima::PUCUSANA, '21' => DistritoLima::PUEBLO_LIBRE,
            '22' => DistritoLima::PUENTE_PIEDRA, '23' => DistritoLima::PUNTA_NEGRA, '24' => DistritoLima::PUNTA_HERMOSA,
            '25' => DistritoLima::RIMAC, '26' => DistritoLima::SAN_BARTOLO, '27' => DistritoLima::SAN_ISIDRO,
            '28' => DistritoLima::INDEPENDENCIA, '29' => DistritoLima::SAN_JUAN_DE_MIRAFLORES, '30' => DistritoLima::SAN_LUIS,
            '31' => DistritoLima::SAN_MARTIN_DE_PORRES, '32' => DistritoLima::SAN_MIGUEL, '33' => DistritoLima::SANTIAGO_DE_SURCO,
            '34' => DistritoLima::SURQUILLO, '35' => DistritoLima::VILLA_MARIA_DEL_TRIUNFO, '36' => DistritoLima::SAN_JUAN_DE_LURIGANCHO,
            '37' => DistritoLima::SANTA_MARIA_DEL_MAR, '38' => DistritoLima::SANTA_ROSA, '39' => DistritoLima::LOS_OLIVOS,
            '40' => DistritoLima::CIENEGUILLA, '41' => DistritoLima::SAN_BORJA, '42' => DistritoLima::VILLA_EL_SALVADOR,
            '43' => DistritoLima::SANTA_ANITA
        ];
        
        return $mapeo[$codigo] ?? null;
    }
}
