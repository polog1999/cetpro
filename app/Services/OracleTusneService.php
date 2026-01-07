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
     * Crea la conexión OCI8 a Oracle.
     *
     * @return resource
     * @throws Exception
     */
    protected function getConnection()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $host = config('database.connections.oracle.host');
        $port = config('database.connections.oracle.port', '1521');
        $serviceName = config('database.connections.oracle.service_name');
        $database = config('database.connections.oracle.database');
        $username = config('database.connections.oracle.username');
        $password = config('database.connections.oracle.password');
        $charset = config('database.connections.oracle.charset', 'AL32UTF8');

        // Construir connection string para Oracle
        // Formato: //host:port/service_name
        $connectionString = "//{$host}:{$port}/" . ($serviceName ?: $database);

        $this->connection = @oci_connect($username, $password, $connectionString, $charset);

        if (!$this->connection) {
            $error = oci_error();
            $message = $error ? $error['message'] : 'Error desconocido al conectar a Oracle';
            $this->lastError = $message;
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
     * Busca una persona por su número de documento.
     *
     * @param string $numDoc Número de documento
     * @return object|null Primera coincidencia o null
     */
    public function buscarPorDocumento(string $numDoc): ?object
    {
        $resultados = $this->buscarPersona(numDoc: $numDoc);
        return $resultados->first();
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
     * Usa la vista VU_CETPRO_BUS.
     *
     * @param string $numDoc Número de documento
     * @return string|null Código de contribuyente (CODCON) o null si no existe
     */
    public function verificarContribuyenteExistente(string $numDoc): ?string
    {
        try {
            $sql = "SELECT CODCON FROM ds_valores.VU_CETPRO_BUS WHERE NUMDOC = :numdoc";
            $resultado = $this->executeQuery($sql, [':numdoc' => $numDoc]);
            $codigo = $resultado->first()?->CODCON;
            
            if ($codigo) {
                Log::info('Contribuyente encontrado en Oracle', [
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
            
            // Para TPE01 (persona natural): MCNAPEPAT, MCNAPEMAT, MCNNOMBRE van NULL
            $sql = "
                INSERT INTO SMACARNOM (
                    MCNCONTRIB, MCNESTADO, MCNTIPO, MCNAPEPAT, MCNAPEMAT, MCNNOMBRE,
                    MCNVIAS, MCNDIRE, MCNNUME, MCNDPTO, MCNCODURBA, MCNURBA, MCNMANZ, MCNLOTE,
                    MCNAPENOMB, MCNTIPODI, MCNNRODI, MCNTIPTELE, MCNROTELE, MCNEMAIL,
                    MCNDNI, MCNRUC, DISTRICODI, MCNFECNAC, CODCAT, MCNFECHREG, MCNHORA, SEXO
                ) VALUES (
                    :codigo, :estado, :tipo, NULL, NULL, NULL,
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
        if ($this->connection !== null) {
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
}
