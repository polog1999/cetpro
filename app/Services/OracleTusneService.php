<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para consultas a base de datos Oracle externa (TUSNE).
 * 
 * Consulta las vistas:
 * - ds_valores.VU_BUSCA_TUSNE_PER (búsqueda de personas)
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

        $result = @oci_execute($stmt);

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
     * Busca personas en la vista VU_BUSCA_TUSNE_PER.
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
            $sql = "SELECT * FROM {$this->schema}.VU_BUSCA_TUSNE_PER WHERE {$whereClause}";

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
