<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para consultas a base de datos Oracle externa (TUSNE).
 * 
 * Consulta las vistas:
 * - ds_valores.VU_CETPRO_BUS (búsqueda de personas)
 * - DS_VALORES.VU_BUSCA_TUSNE_PER_Pen (personas pendientes)
 * 
 * Usa la extensión OCI8 nativa de PHP para conectar a Oracle.
 */
class OracleTusneService
{
    /**
     * Recurso de conexión OCI8.
     */
    protected $connection = null;

    /**
     * Esquema donde se encuentran las vistas.
     */
    protected string $schema = 'DS_VALORES';

    /**
     * Mensaje del último error.
     */
    protected ?string $lastError = null;

    /**
     * Obtiene la conexión a Oracle. Lanza excepción si falla.
     * @return resource
     * @throws Exception
     */
    protected function getConnection()
    {
        // Verificar si hay una conexión válida (recurso, no null ni false)
        if ($this->connection !== null && $this->connection !== false) {
            return $this->connection;
        }

        $host = config('database.connections.oracle.host');
        $port = config('database.connections.oracle.port', '1521');
        // $sid = config('database.connections.oracle.sid');
        $serviceName = config('database.connections.oracle.service_name');
        $database = config('database.connections.oracle.database');
        $username = config('database.connections.oracle.username');
        $password = config('database.connections.oracle.password');
        $charset = config('database.connections.oracle.charset', 'AL32UTF8');

        // Construir connection string para Oracle usando SID
        // Formato: (DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=host)(PORT=port))(CONNECT_DATA=(SID=sid)))
        // $connectionString = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SID={$sid})))";
        
        // Alternativa usando SERVICE_NAME (descomentar si se necesita):
        $connectionString = "//{$host}:{$port}/" . ($serviceName ?: $database);

        $this->connection = @oci_connect($username, $password, $connectionString, $charset);

        if (!$this->connection) {
            $error = oci_error();
            $message = $error ? $error['message'] : 'Error desconocido al conectar a Oracle';
            $this->lastError = $message;
            $this->connection = null; // Resetear a null para permitir reintentos
            throw new Exception($message);
        }

        return $this->connection;
    }

    /**
     * Verifica si la conexión Oracle está disponible.
     *
     * @return bool
     */
    public function verificarConexion(): bool
    {
        try {
            $this->getConnection();
            return true;
        } catch (Exception $e) {
            Log::error('Error de conexión Oracle: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene mensaje de error de conexión.
     *
     * @return string|null
     */
    public function obtenerErrorConexion(): ?string
    {
        try {
            $this->getConnection();
            return null;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Ejecuta una consulta y retorna los resultados como Collection.
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros nombrados [:param => value]
     * @return Collection
     * @throws Exception
     */
    protected function executeQuery(string $sql, array $params = []): Collection
    {
        $conn = $this->getConnection();
        $stmt = oci_parse($conn, $sql);

        if (!$stmt) {
            $error = oci_error($conn);
            throw new Exception($error['message'] ?? 'Error al parsear SQL');
        }

        // Bind de parámetros
        foreach ($params as $key => $value) {
            // Remover : del inicio si existe para el bind
            $bindKey = ltrim($key, ':');
            oci_bind_by_name($stmt, ":{$bindKey}", $params[$key]);
        }

        // Usar OCI_COMMIT_ON_SUCCESS para asegurar commit de operaciones DML internas
        // Por ejemplo, la función fu_digito_generar puede hacer INSERTs internamente
        $result = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message'] ?? 'Error al ejecutar consulta');
        }

        $rows = [];
        while ($row = oci_fetch_object($stmt)) {
            $rows[] = $row;
        }

        oci_free_statement($stmt);

        return collect($rows);
    }

    /**
     * Busca personas en la vista VU_CETPRO_BUS.
     * 
     * Permite búsqueda por:
     * - Nombre (búsqueda parcial con LIKE)
     * - Número de documento (búsqueda exacta)
     * - Código (búsqueda exacta)
     *
     * @param string|null $nombre Nombre a buscar (parcial)
     * @param string|null $numDoc Número de documento (exacto)
     * @param string|null $codigo Código (exacto)
     * @return Collection
     * @throws Exception Si hay error en la consulta
     */
    public function buscarPersona(
        ?string $nombre = null,
        ?string $numDoc = null,
        ?string $codigo = null
    ): Collection {
        try {
            $conditions = [];
            $params = [];

            if (!empty($nombre)) {
                $conditions[] = "NOMBRE LIKE :nombre";
                $params[':nombre'] = '%' . strtoupper($nombre) . '%';
            }

            if (!empty($numDoc)) {
                $conditions[] = "NUMDOC = :numdoc";
                $params[':numdoc'] = $numDoc;
            }

            if (!empty($codigo)) {
                $conditions[] = "CODIGO = :codigo";
                $params[':codigo'] = strtoupper($codigo);
            }

            if (empty($conditions)) {
                return collect([]);
            }

            $whereClause = implode(' OR ', $conditions);
            $sql = "SELECT * FROM {$this->schema}.VU_CETPRO_BUS WHERE {$whereClause}";

            return $this->executeQuery($sql, $params);
        } catch (Exception $e) {
            Log::error('Error en buscarPersona Oracle: ' . $e->getMessage(), [
                'nombre' => $nombre,
                'numDoc' => $numDoc,
                'codigo' => $codigo,
            ]);
            throw $e;
        }
    }

    /**
     * Busca personas pendientes por código en la vista VU_BUSCA_TUSNE_PER_Pen.
     *
     * @param string $codigo Código de la persona
     * @return Collection
     * @throws Exception Si hay error en la consulta
     */
    public function buscarPersonaPendiente(string $codigo): Collection
    {
        try {
            $sql = "SELECT * FROM {$this->schema}.VU_BUSCA_TUSNE_PER_Pen WHERE CODIGO = :codigo";
            $codigoUpper = strtoupper($codigo);
            
            return $this->executeQuery($sql, [':codigo' => $codigoUpper]);
        } catch (Exception $e) {
            Log::error('Error en buscarPersonaPendiente Oracle: ' . $e->getMessage(), [
                'codigo' => $codigo,
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene el código de contribuyente más reciente por número de documento.
     * 
     * Retorna únicamente el código con la fecha de emisión (EMITIDO) más reciente,
     * realizando un JOIN entre VU_CETPRO_BUS y VU_BUSCA_TUSNE_PER_Pen.
     * 
     * IMPORTANTE: Solo considera códigos que empiecen con 'C'.
     *
     * @param string $numDoc Número de documento
     * @return object|null Objeto con CODIGO, EMITIDO y CONCEPTO, o null si no existe
     * @throws Exception Si hay error en la consulta
     */
    public function obtenerCodigoContribuyenteMasReciente(string $numDoc): ?object
    {
        try {
            $sql = "
                SELECT 
                    l.CODIGO,
                    l.EMITIDO,
                    l.CONCEPTO
                FROM 
                    {$this->schema}.VU_CETPRO_BUS p
                JOIN 
                    {$this->schema}.VU_BUSCA_TUSNE_PER_Pen l ON p.CODCON = l.CODIGO
                WHERE 
                    p.NUMDOC = :numdoc
                AND 
                    l.CODIGO LIKE 'C%'
                ORDER BY 
                    TO_DATE(l.EMITIDO, 'DD/MM/YYYY') DESC
                FETCH FIRST 1 ROWS ONLY
            ";
            
            $resultados = $this->executeQuery($sql, [':numdoc' => $numDoc]);
            return $resultados->first();
        } catch (Exception $e) {
            Log::error('Error en obtenerCodigoContribuyenteMasReciente Oracle: ' . $e->getMessage(), [
                'numDoc' => $numDoc,
            ]);
            throw $e;
        }
    }

    /**
     * Genera un código de liquidación usando la función Oracle fu_digito_generar.
     * 
     * Esta función Oracle genera códigos únicos de liquidación para los pagos
     * de estudiantes matriculados.
     *
     * @param string $codigoEspecialidad Código B000X según especialidad (B0001, B0002, B0003)
     * @param string $codigoContribuyente Código de contribuyente del estudiante
     * @return string|null Código de liquidación generado o null si falla
     * @throws Exception Si hay error en la consulta
     */
    public function generarCodigoLiquidacion(
        string $codigoEspecialidad,
        string $codigoContribuyente
    ): ?string {
        try {
            $sql = "
                SELECT ds_valores.fu_digito_generar(
                    '1312',
                    '23',
                    :codigo_especialidad,
                    :codigo_contribuyente,
                    'CETPRO'
                ) AS LIQUIDACION
                FROM DUAL
            ";
            
            $resultado = $this->executeQuery($sql, [
                ':codigo_especialidad' => $codigoEspecialidad,
                ':codigo_contribuyente' => $codigoContribuyente,
            ]);
            
            return $resultado->first()?->LIQUIDACION;
        } catch (Exception $e) {
            Log::error('Error generando código de liquidación Oracle: ' . $e->getMessage(), [
                'codigo_especialidad' => $codigoEspecialidad,
                'codigo_contribuyente' => $codigoContribuyente,
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene el estado de una liquidación desde Oracle.
     * 
     * Consulta la vista VU_BUSCA_TUSNE_PER_Pen para obtener el estado
     * de un número de liquidación específico.
     *
     * @param string $numLiquidacion Número de liquidación (ej: 1312202605662284)
     * @return string|null Estado de la liquidación (ej: 'Pendiente 2026') o null si no existe
     */
    public function obtenerEstadoLiquidacion(string $numLiquidacion): ?string
    {
        try {
            $sql = "SELECT ESTADO FROM {$this->schema}.VU_BUSCA_TUSNE_PER_Pen WHERE LIQUIDACION = :liquidacion";
            
            $resultado = $this->executeQuery($sql, [':liquidacion' => $numLiquidacion]);
            
            return $resultado->first()?->ESTADO;
        } catch (Exception $e) {
            Log::warning('Error obteniendo estado de liquidación Oracle: ' . $e->getMessage(), [
                'num_liquidacion' => $numLiquidacion,
            ]);
            return null;
        }
    }

    /**
     * Busca una persona por su número de documento.
     *
     * @param string $numDoc Número de documento
     * @return object|null Primera coincidencia o null
     */
    public function buscarPorDocumento(string $numDoc): ?object
    {
        $resultados = $this->buscarPersona(numDoc: $numDoc);
        $persona = $resultados->first();
        
        if ($persona) {
            // Si encontramos a la persona, también buscamos sus pagos
            // Usamos el campo CODCON (o MCNCONTRIB dependiendo de la vista)
            $codigo = $persona->CODCON ?? $persona->MCNCONTRIB ?? null;
            if ($codigo) {
                $persona->PAGOS = $this->buscarPersonaPendiente($codigo);
            } else {
                $persona->PAGOS = collect([]);
            }
        }
        
        return $persona;
    }

    /**
     * Obtiene los datos completos de una persona desde Oracle SMACARNOM.
     * Incluye datos personales y su historial de pagos.
     * 
     * @param string $nroDoc Número de documento (DNI)
     * @return object|null 
     */
    public function obtenerDatosCompletosPersona(string $nroDoc): ?object
    {
        try {
            $nroDoc = trim($nroDoc);

            // 1. Intentar búsqueda directa en SMACARNOM
            $sqlPersona = "SELECT * FROM SMACARNOM WHERE TRIM(MCNNRODI) = :nrodi";
            $persona = $this->executeQuery($sqlPersona, [':nrodi' => $nroDoc])->first();

            // 2. Si no encuentra, intentar por VU_CETPRO_BUS (puede mapear DNI a CODCON)
            if (!$persona) {
                $enBusqueda = $this->buscarPorDocumento($nroDoc);
                $codigo = $enBusqueda->CODCON ?? $enBusqueda->MCNCONTRIB ?? null;

                if ($codigo) {
                    $sqlPersonaCod = "SELECT * FROM SMACARNOM WHERE MCNCONTRIB = :codigo";
                    $persona = $this->executeQuery($sqlPersonaCod, [':codigo' => $codigo])->first();
                }
            }

            if (!$persona) {
                return null;
            }

            // 3. Obtener pagos
            $codigo = $persona->MCNCONTRIB;
            if ($codigo) {
                $persona->PAGOS = $this->buscarPersonaPendiente($codigo);
            } else {
                $persona->PAGOS = collect([]);
            }

            return $persona;
        } catch (Exception $e) {
            Log::error('Error en obtenerDatosCompletosPersona Oracle: ' . $e->getMessage(), [
                'nroDoc' => $nroDoc,
            ]);
            throw $e;
        }
    }

    /**
     * Busca una persona por su código.
     *
     * @param string $codigo Código de la persona
     * @return object|null Primera coincidencia o null
     */
    public function buscarPorCodigo(string $codigo): ?object
    {
        $resultados = $this->buscarPersona(codigo: $codigo);
        return $resultados->first();
    }

    /**
     * Busca historial completo (datos + pagos/liquidaciones) por DNI.
     * Agrupa los resultados por Código de Contribuyente.
     * 
     * @param string $dni
     * @return array [ 'C001' => ['datos' => [...], 'pagos' => [...]], ... ]
     */
    public function buscarHistorialPorDni(string $dni): array
    {
        try {
            $dni = trim($dni);
            
            $sql = "
                SELECT 
                    a.MCNAPENOMB, 
                    a.MCNNRODI, 
                    a.MCNFECNAC,
                    a.SEXO,
                    a.MCNAPEPAT, -- Agregado
                    a.MCNAPEMAT, -- Agregado
                    a.MCNNOMBRE, -- Agregado
                    d.DISTRIDESC, -- Agregado (Nombre distrito)
                    a.MCNCONTRIB as CODIGO_ORIGEN,
                    b.CODIGO as CODIGO_PAGO,
                    b.CONCEPTO, 
                    b.LIQUIDACION, 
                    b.IMPORTE, 
                    b.EMITIDO, 
                    b.PAGADO, 
                    b.ESTADO
                FROM SMACARNOM a
                JOIN DS_VALORES.VU_BUSCA_TUSNE_PER_PEN b
                  ON a.MCNCONTRIB = b.CODIGO
                LEFT JOIN SMADISTRITO d 
                  ON a.DISTRICODI = d.DISTRICODI -- Join para distrito
                WHERE TRIM(a.MCNNRODI) = :dni
                ORDER BY b.EMITIDO DESC
            ";

            $resultados = $this->executeQuery($sql, [':dni' => $dni]);
            
            // Agrupar por código de contribuyente
            $historial = [];
            
            foreach ($resultados as $row) {
                // Usamos el código que viene del pago (que es el nexo), o el de la persona
                $codigoRaw = $row->CODIGO_PAGO ?? $row->CODIGO_ORIGEN;
                $codigo = trim((string)$codigoRaw); 
                
                if (!isset($historial[$codigo])) {
                    $historial[$codigo] = [
                        'datos_personales' => [ // Array asociativo
                            'MCNAPENOMB' => trim((string)$row->MCNAPENOMB),
                            'MCNNRODI' => trim((string)$row->MCNNRODI),
                            'MCNCONTRIB' => $codigo,
                            'MCNFECNAC' => $row->MCNFECNAC,
                            'SEXO'       => trim((string)$row->SEXO),
                            'MCNAPEPAT'  => trim((string)$row->MCNAPEPAT), 
                            'MCNAPEMAT'  => trim((string)$row->MCNAPEMAT),
                            'MCNNOMBRE'  => trim((string)$row->MCNNOMBRE),
                            'DISTRIDESC' => trim((string)$row->DISTRIDESC),
                        ],
                        'pagos' => [] // Array simple
                    ];
                }
                
                // Agregar pago a la lista
                if ($row->LIQUIDACION) { 
                    $liquid = trim((string)$row->LIQUIDACION);
                    $historial[$codigo]['pagos'][] = [ // Array asociativo
                        'CONCEPTO' => trim((string)$row->CONCEPTO),
                        'LIQUIDACION' => $liquid,
                        'IMPORTE' => $row->IMPORTE,
                        'EMITIDO' => $row->EMITIDO,
                        'PAGADO' => trim((string)$row->PAGADO),
                        'ESTADO' => trim((string)$row->ESTADO),
                    ];
                }
            }
            
            return $historial;
            
        } catch (Exception $e) {
            Log::error('Error buscarHistorialPorDni: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca personas por nombre (búsqueda parcial).
     *
     * @param string $nombre Nombre a buscar
     * @return Collection
     */
    public function buscarPorNombre(string $nombre): Collection
    {
        return $this->buscarPersona(nombre: $nombre);
    }

    /**
     * Ejecuta una consulta SQL SELECT personalizada en Oracle.
     * 
     * ⚠️ ADVERTENCIA: Use solo para consultas SELECT.
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros de la consulta
     * @return Collection
     */
    public function consultaSelect(string $sql, array $params = []): Collection
    {
        try {
            return $this->executeQuery($sql, $params);
        } catch (Exception $e) {
            Log::error('Error en consulta Oracle personalizada: ' . $e->getMessage(), [
                'sql' => $sql,
            ]);
            throw $e;
        }
    }

    /**
     * Ejecuta un INSERT/UPDATE/DELETE y retorna si fue exitoso.
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros nombrados [:param => value]
     * @return bool
     * @throws Exception
     */
    protected function executeInsert(string $sql, array $params = []): bool
    {
        $conn = $this->getConnection();
        $stmt = oci_parse($conn, $sql);

        if (!$stmt) {
            $error = oci_error($conn);
            throw new Exception($error['message'] ?? 'Error al parsear SQL');
        }

        // Bind de parámetros
        foreach ($params as $key => $value) {
            $bindKey = ltrim($key, ':');
            oci_bind_by_name($stmt, ":{$bindKey}", $params[$key], -1);
        }

        $result = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$result) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'Error al ejecutar INSERT');
        }

        oci_free_statement($stmt);
        return true;
    }

    /**
     * Obtiene el siguiente código de contribuyente secuencial.
     * Formato: C0000001, C0000002, etc.
     *
     * @return string
     * @throws Exception
     */
    public function obtenerSiguienteCodigoContribuyente(): string
    {
        try {
            $sql = "
                SELECT MAX(MCNCONTRIB) AS ULTIMO_CODIGO 
                FROM SMACARNOM 
                WHERE MCNCONTRIB LIKE 'C%'
            ";
            
            $resultado = $this->executeQuery($sql, []);
            $ultimoCodigo = $resultado->first()?->ULTIMO_CODIGO;
            
            if (empty($ultimoCodigo)) {
                return 'C0000001';
            }
            
            $numero = (int) substr($ultimoCodigo, 1);
            $nuevoNumero = $numero + 1;
            
            return 'C' . str_pad($nuevoNumero, 7, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            Log::error('Error obteniendo siguiente código contribuyente: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mapea el enum DistritoLima al código Oracle.
     */
    public function mapearDistritoACodigo(?string $distrito): ?string
    {
        if (empty($distrito)) {
            return null;
        }

        $mapeo = [
            'Lima' => '01', 'Ancón' => '02', 'Ate' => '03', 'Barranco' => '04',
            'Breña' => '05', 'Carabayllo' => '06', 'Comas' => '07', 'Chaclacayo' => '08',
            'Chorrillos' => '09', 'El Agustino' => '10', 'Jesús María' => '11',
            'La Molina' => '12', 'La Victoria' => '13', 'Lince' => '14',
            'Lurigancho' => '15', 'Lurín' => '16', 'Magdalena del Mar' => '17',
            'Miraflores' => '18', 'Pachacámac' => '19', 'Pucusana' => '20',
            'Pueblo Libre' => '21', 'Puente Piedra' => '22', 'Punta Negra' => '23',
            'Punta Hermosa' => '24', 'Rímac' => '25', 'San Bartolo' => '26',
            'San Isidro' => '27', 'Independencia' => '28', 'San Juan de Miraflores' => '29',
            'San Luis' => '30', 'San Martín de Porres' => '31', 'San Miguel' => '32',
            'Santiago de Surco' => '33', 'Surquillo' => '34', 'Villa María del Triunfo' => '35',
            'San Juan de Lurigancho' => '36', 'Santa María del Mar' => '37', 'Santa Rosa' => '38',
            'Los Olivos' => '39', 'Cieneguilla' => '40', 'San Borja' => '41',
            'Villa El Salvador' => '42', 'Santa Anita' => '43',
        ];

        return $mapeo[$distrito] ?? null;
    }

    /**
     * Mapea el tipo de documento al código Oracle.
     * Catálogo de Oracle:
     * DOI01=DNI, DOI06=Carnet de extranjeria, DOI11=Pasaporte, DOI19=PTP, DOI03=RUC
     */
    public function mapearTipoDocumento(string $tipoDocumento): string
    {
        $mapeo = [
            'DNI' => 'DOI01',
            'Carnet de extranjeria' => 'DOI06',
            'Pasaporte' => 'DOI11',
            'PTP' => 'DOI19',
            'RUC' => 'DOI03',
        ];

        return $mapeo[$tipoDocumento] ?? 'DOI01';
    }

    /**
     * Mapea el género al código Oracle.
     */
    public function mapearGenero(string $genero): string
    {
        return match ($genero) {
            'Masculino' => 'M',
            'Femenino' => 'F',
            default => 'M',
        };
    }

    /**
     * Verifica si existe un contribuyente en Oracle por número de documento.
     * Busca directamente en SMACARNOM sin requerir pagos previos.
     * 
     * Solo considera códigos que empiecen con "C" (Generados por sistema nuevo).
     * Ignora códigos antiguos con prefijo "S".
     *
     * @param string $numDoc Número de documento
     * @return string|null Código de contribuyente (MCNCONTRIB) o null si no existe
     */
    public function verificarContribuyenteExistente(string $numDoc): ?string
    {
        try {
            // Buscamos en SMACARNOM el código más reciente (por fecha registro)
            // IMPORTANTE: Solo buscamos códigos que empiecen con 'C'
            $sql = "
                SELECT MCNCONTRIB
                FROM SMACARNOM
                WHERE TRIM(MCNNRODI) = :numdoc
                AND MCNCONTRIB LIKE 'C%' 
                ORDER BY MCNFECHREG DESC
                FETCH FIRST 1 ROWS ONLY
            ";

            $resultado = $this->executeQuery($sql, [':numdoc' => $numDoc]);
            $codigo = $resultado->first()?->MCNCONTRIB;
            
            if ($codigo) {
                Log::info('Contribuyente existente (tipo C) encontrado en SMACARNOM', [
                    'codigo' => trim($codigo),
                    'nro_documento' => $numDoc,
                ]);
                return trim($codigo);
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Error verificando contribuyente existente: ' . $e->getMessage(), [
                'nro_documento' => $numDoc,
            ]);
            return null;
        }
    }

    /**
     * Crea un contribuyente en Oracle (tabla SMACARNOM).
     * 
     * Primero verifica si ya existe en VU_CETPRO_BUS.
     * Si existe, retorna el código existente.
     * Si no existe, crea uno nuevo con prefijo 'C'.
     *
     * @param \App\Models\Estudiante $estudiante
     * @return string|null Código de contribuyente generado/existente o null si falla
     */
    public function crearContribuyente(\App\Models\Estudiante $estudiante): ?string
    {
        try {
            // PASO 1: Verificar si ya existe en Oracle
            $codigoExistente = $this->verificarContribuyenteExistente($estudiante->nro_documento);
            if ($codigoExistente) {
                return $codigoExistente;
            }

            // PASO 2: Si no existe, crear nuevo contribuyente
            $codigoContribuyente = $this->obtenerSiguienteCodigoContribuyente();
            
            $tipoDocumento = $this->mapearTipoDocumento($estudiante->tipo_documento->value ?? 'DNI');
            $codigoDistrito = $this->mapearDistritoACodigo($estudiante->distrito?->value);
            $sexo = $this->mapearGenero($estudiante->genero?->value ?? 'Masculino');
            
            // Nombre completo para MCNAPENOMB: "APELLIDOS, NOMBRES"
            $nombreCompleto = trim(strtoupper(
                $estudiante->apellido_paterno . ' ' . 
                $estudiante->apellido_materno . ', ' . 
                $estudiante->nombres
            ));
            
            $fechaNacimiento = $estudiante->fecha_nacimiento?->format('d/m/Y');
            
            // Preparar apellidos y nombres en mayúsculas
            $apellidoPaterno = trim(strtoupper($estudiante->apellido_paterno ?? ''));
            $apellidoMaterno = trim(strtoupper($estudiante->apellido_materno ?? ''));
            $nombres = trim(strtoupper($estudiante->nombres ?? ''));
            
            // Para TPE01 (persona natural): guardamos TODOS los campos de nombre
            $sql = "
                INSERT INTO SMACARNOM (
                    MCNCONTRIB, MCNESTADO, MCNTIPO, MCNAPEPAT, MCNAPEMAT, MCNNOMBRE,
                    MCNVIAS, MCNDIRE, MCNNUME, MCNDPTO, MCNCODURBA, MCNURBA, MCNMANZ, MCNLOTE,
                    MCNAPENOMB, MCNTIPODI, MCNNRODI, MCNTIPTELE, MCNROTELE, MCNEMAIL,
                    MCNDNI, MCNRUC, DISTRICODI, MCNFECNAC, CODCAT, MCNFECHREG, MCNHORA, SEXO
                ) VALUES (
                    :codigo, :estado, :tipo, :apepat, :apemat, :nombre,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    :nomcom, :tipodi, :nrodi, NULL, :telefono, :email,
                    :dni, NULL, :distrito, TO_DATE(:fecnac, 'DD/MM/YYYY'), NULL, SYSDATE,
                    TO_CHAR(SYSDATE, 'HH24:MI:SS'), :sexo
                )
            ";
            
            $params = [
                ':codigo' => $codigoContribuyente,
                ':estado' => 'ERE04',
                ':tipo' => 'TPE01',
                ':apepat' => $apellidoPaterno ?: null,
                ':apemat' => $apellidoMaterno ?: null,
                ':nombre' => $nombres ?: null,
                ':nomcom' => $nombreCompleto,
                ':tipodi' => $tipoDocumento,
                ':nrodi' => $estudiante->nro_documento,
                ':telefono' => $estudiante->telefono,
                ':email' => $estudiante->email,
                ':dni' => $estudiante->nro_documento,
                ':distrito' => $codigoDistrito,
                ':fecnac' => $fechaNacimiento,
                ':sexo' => $sexo,
            ];
            
            $this->executeInsert($sql, $params);
            
            Log::info('Contribuyente creado en Oracle', [
                'codigo' => $codigoContribuyente,
                'nro_documento' => $estudiante->nro_documento,
            ]);
            
            return $codigoContribuyente;
        } catch (Exception $e) {
            Log::error('Error al crear contribuyente en Oracle: ' . $e->getMessage(), [
                'estudiante_id' => $estudiante->id,
                'nro_documento' => $estudiante->nro_documento,
            ]);
            return null;
        }
    }

    /**
     * Cierra la conexión OCI8.
     */
    public function cerrarConexion(): void
    {
        if ($this->connection !== null && $this->connection !== false) {
            oci_close($this->connection);
            $this->connection = null;
        }
    }

    /**
     * Destructor para cerrar conexión automáticamente.
     */
    public function __destruct()
    {
        $this->cerrarConexion();
    }

    /**
     * Obtiene datos de una liquidación desde Oracle (estado y fecha de pago).
     *
     * @param string $numLiquidacion Número de liquidación
     * @return object|null Objeto con ESTADO y PAGADO, o null si no existe
     */
    public function obtenerDatosLiquidacion(string $numLiquidacion): ?object
    {
        try {
            $sql = "SELECT ESTADO, PAGADO, IMPORTE FROM {$this->schema}.VU_BUSCA_TUSNE_PER_Pen WHERE LIQUIDACION = :liquidacion";
            
            $resultado = $this->executeQuery($sql, [':liquidacion' => $numLiquidacion]);
            
            return $resultado->first();
        } catch (Exception $e) {
            Log::warning('Error obteniendo datos de liquidación Oracle: ' . $e->getMessage(), [
                'num_liquidacion' => $numLiquidacion,
            ]);
            return null;
        }
    }

    /**
     * Sincroniza los pagos de un cronograma con los datos actuales de Oracle.
     * Actualiza estado y fecha_pago de cada pago que tenga num_liquidacion.
     * IMPORTANTE: Los valores de Oracle SIEMPRE sobrescriben los de PostgreSQL.
     *
     * @param \App\Models\Cronograma $cronograma
     * @return int Número de pagos actualizados
     */
    public function sincronizarPagosCronograma(\App\Models\Cronograma $cronograma): int
    {
        $actualizados = 0;
        
        $pagosConLiquidacion = $cronograma->pagos()
            ->whereNotNull('num_liquidacion')
            ->get();
        
        foreach ($pagosConLiquidacion as $pago) {
            $datosOracle = $this->obtenerDatosLiquidacion($pago->num_liquidacion);
            
            if ($datosOracle) {
                $cambios = [];
                
                // Actualizar estado SIEMPRE desde Oracle (es la fuente de verdad)
                if ($datosOracle->ESTADO !== null && $datosOracle->ESTADO !== $pago->estado) {
                    $cambios['estado'] = $datosOracle->ESTADO;
                }
                
                // Actualizar fecha de pago SIEMPRE desde Oracle
                // Si Oracle tiene NULL, PostgreSQL también debe tener NULL
                if (!empty($datosOracle->PAGADO)) {
                    try {
                        $fechaPago = \Carbon\Carbon::createFromFormat('d/m/Y', $datosOracle->PAGADO);
                        if ($pago->fecha_pago != $fechaPago) {
                            $cambios['fecha_pago'] = $fechaPago;
                        }
                    } catch (\Exception $e) {
                        try {
                            $fechaPago = \Carbon\Carbon::parse($datosOracle->PAGADO);
                            if ($pago->fecha_pago != $fechaPago) {
                                $cambios['fecha_pago'] = $fechaPago;
                            }
                        } catch (\Exception $e2) {
                            Log::warning('Error parseando fecha de pago Oracle', [
                                'pago_id' => $pago->id,
                                'fecha_oracle' => $datosOracle->PAGADO,
                            ]);
                        }
                    }
                } else {
                    // Oracle tiene NULL, si PostgreSQL tiene fecha, hay que limpiarla
                    if ($pago->fecha_pago !== null) {
                        $cambios['fecha_pago'] = null;
                    }
                }
                
                // Aplicar cambios si hay alguno
                if (!empty($cambios)) {
                    $pago->update($cambios);
                    $actualizados++;
                    
                    Log::info('Pago sincronizado desde Oracle', [
                        'pago_id' => $pago->id,
                        'num_liquidacion' => $pago->num_liquidacion,
                        'cambios' => $cambios,
                    ]);
                }
            }
        }
        
        return $actualizados;
    }
}
