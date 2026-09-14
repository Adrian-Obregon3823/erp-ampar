<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase FirebirdConnection PDO
*********************************************************************************
*/


class FirebirdConnection
{
    private $conn;
    private $autoCommit;
    private $inTransaction = false;

    public function __construct($autoCommit = false) // Por defecto false
    {
        global $host, $dbname, $username, $password;
        $this->autoCommit = $autoCommit;
        $this->connect($host, $dbname, $username, $password);
    }

    public function connect($host, $dbname, $username, $password)
    {
        try {
            $this->conn = new PDO(
                "firebird:dbname={$host}:{$dbname};charset=UTF8",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false, // conexión persistente desactivada para evitar Active Transaction
                ]
            );

            if (!$this->autoCommit) {
                $this->conn->beginTransaction();
                $this->inTransaction = true;
            }
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->conn->beginTransaction();
            $this->inTransaction = true;
        }
    }

    public function query($sql, $params = [])
    {
        $cleanParams = [];
        $parts = explode('?', $sql);
        if (count($parts) - 1 === count($params)) {
            $newSql = $parts[0];
            for ($i = 0; $i < count($params); $i++) {
                if ($params[$i] === null) {
                    $newSql .= "NULL" . $parts[$i + 1];
                } else {
                    $newSql .= "?" . $parts[$i + 1];
                    $cleanParams[] = $params[$i];
                }
            }
            $sql = $newSql;
            $params = $cleanParams;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            return empty($result) ? [] : $result;
        } catch (Exception $e) {
            throw new Exception("Error in query(): " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        }
    }

    public function execute($sql, $params = [])
    {
        $cleanParams = [];
        $parts = explode('?', $sql);
        if (count($parts) - 1 === count($params)) {
            $newSql = $parts[0];
            for ($i = 0; $i < count($params); $i++) {
                if ($params[$i] === null) {
                    $newSql .= "NULL" . $parts[$i + 1];
                } else {
                    $newSql .= "?" . $parts[$i + 1];
                    $cleanParams[] = $params[$i];
                }
            }
            $sql = $newSql;
            $params = $cleanParams;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            throw new Exception("Error in execute(): " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        }
    }

    public function executeReturning($sql, $id_field = null, $params = [])
    {
        if (stripos($sql, 'INSERT INTO') !== false && $id_field) {
            $sql .= " RETURNING $id_field";
        }

        $cleanParams = [];
        $parts = explode('?', $sql);
        if (count($parts) - 1 === count($params)) {
            $newSql = $parts[0];
            for ($i = 0; $i < count($params); $i++) {
                if ($params[$i] === null) {
                    $newSql .= "NULL" . $parts[$i + 1];
                } else {
                    $newSql .= "?" . $parts[$i + 1];
                    $cleanParams[] = $params[$i];
                }
            }
            $sql = $newSql;
            $params = $cleanParams;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            if ($id_field) {
                $row = $stmt->fetch();
                return $row ? reset($row) : false;
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error in executeReturning(): " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        }
    }

    public function executeconreturning($sql, $id_field = null, $params = [])
    {
        try {
            // Si es un INSERT y se pidió devolver un campo
            if (stripos($sql, 'INSERT INTO') !== false && $id_field) {
                $sql .= " RETURNING $id_field";
            }

            $cleanParams = [];
            $parts = explode('?', $sql);
            if (count($parts) - 1 === count($params)) {
                $newSql = $parts[0];
                for ($i = 0; $i < count($params); $i++) {
                    if ($params[$i] === null) {
                        $newSql .= "NULL" . $parts[$i + 1];
                    } else {
                        $newSql .= "?" . $parts[$i + 1];
                        $cleanParams[] = $params[$i];
                    }
                }
                $sql = $newSql;
                $params = $cleanParams;
            }

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute($params);

            if (!$success) {
                return false;
            }

            // Si se pidió un campo RETURNING
            if ($id_field) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? reset($row) : false;
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error in executeconreturning(): " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        }
    }

    public function commit()
    {
        if ($this->inTransaction) {
            $this->conn->commit();
            $this->inTransaction = false;
        }
    }

    public function rollback()
    {
        if ($this->inTransaction) {
            $this->conn->rollBack();
            $this->inTransaction = false;
        }
    }

    public function close()
    {
        if ($this->conn) {
            if ($this->inTransaction) {
                $this->rollback();
            }
            $this->conn = null;
        }
    }
}

/*

class FirebirdConnection
{
    private $conn = null;            // recurso de conexión ibase
    private $tr = null;              // recurso de transacción ibase
    private $autoCommit;
    private $inTransaction = false;
    private $persistent = true;      // cambia a false si no quieres persistente
    private $charset = 'UTF8';       // ajusta si tu DB usa otro charset (WIN1252, ISO8859_1, etc.)

    public function __construct($autoCommit = false)
    {
        global $host, $dbname, $username, $password;
        $this->autoCommit = $autoCommit;
        $this->connect($host, $dbname, $username, $password);
    }

    public function connect($host, $dbname, $username, $password)
    {
        // Formato: "host:/ruta/database.fdb" o "host:alias"
        $dsn = "{$host}:{$dbname}";

        // Conectar (persistente o normal)
        if ($this->persistent) {
            $this->conn = @ibase_pconnect($dsn, $username, $password, $this->charset);
        } else {
            $this->conn = @ibase_connect($dsn, $username, $password, $this->charset);
        }

        if (!$this->conn) {
            $err = ibase_errmsg();
            die("Error de conexión: " . ($err ?: 'No se pudo conectar a Firebird'));
        }

        if (!$this->autoCommit) {
            $this->beginTransaction();
        }
    }

    public function beginTransaction()
    {
        if ($this->inTransaction) return;

        // Modo recomendado: COMMITTED, READ COMMITTED, NO RECORD VERSION, NOWAIT
        $this->tr = @ibase_trans(
            IBASE_COMMITTED | IBASE_REC_NO_VERSION | IBASE_NOWAIT,
            $this->conn
        );
        if (!$this->tr) {
            throw new Exception("No se pudo iniciar la transacción: " . ibase_errmsg());
        }
        $this->inTransaction = true;
    }

   
    public function query($sql, $params = [])
    {
        // Preparar dentro de la transacción si existe, si no, con la conexión
        $prep = @ibase_prepare($this->tr ?: $this->conn, $sql);
        if (!$prep) {
            throw new Exception("Error al preparar query: " . ibase_errmsg() . " | SQL: {$sql}");
        }

        // Ejecutar con parámetros variables
        $res = @ibase_execute($prep, ...array_values($params));
        if ($res === false) {
            throw new Exception("Error al ejecutar query: " . ibase_errmsg() . " | SQL: {$sql}");
        }

        $rows = [];
        // Nota: ibase_fetch_assoc devuelve claves en MAYÚSCULAS como en el metadata de Firebird
        while ($row = ibase_fetch_assoc($res)) {
            $rows[] = $row;
        }
        ibase_free_result($res);

        return empty($rows) ? [] : $rows;
    }

    
    public function execute($sql, $params = [])
    {
        $prep = @ibase_prepare($this->tr ?: $this->conn, $sql);
        if (!$prep) {
            throw new Exception("Error al preparar execute: " . ibase_errmsg() . " | SQL: {$sql}");
        }

        $ok = @ibase_execute($prep, ...array_values($params));
        if ($ok === false) {
            throw new Exception("Error al ejecutar: " . ibase_errmsg() . " | SQL: {$sql}");
        }

        // $ok es un resource para SELECT/RETURNING o true para DML sin resultset
        if (is_resource($ok)) {
            // Si accidentalmente vino un resultset (p.ej. por un SELECT), libéralo
            ibase_free_result($ok);
        }

        return true;
    }

    
    public function executeReturning($sql, $id_field = null, $params = [])
    {
        // Si es INSERT y nos dieron un campo, añade RETURNING
        if (stripos($sql, 'INSERT INTO') !== false && $id_field) {
            $sql .= " RETURNING {$id_field}";
        }

        $prep = @ibase_prepare($this->tr ?: $this->conn, $sql);
        if (!$prep) {
            throw new Exception("Error al preparar executeReturning: " . ibase_errmsg() . " | SQL: {$sql}");
        }

        $res = @ibase_execute($prep, ...array_values($params));
        if ($res === false) {
            throw new Exception("Error al ejecutar (executeReturning): " . ibase_errmsg() . " | SQL: {$sql}");
        }

        if ($id_field) {
            // Hay resultset con el/los campos del RETURNING
            $row = ibase_fetch_assoc($res);
            ibase_free_result($res);
            if (!$row) return false;

            // El índice puede venir en MAYÚSCULAS
            $keyUpper = strtoupper($id_field);
            return $row[$keyUpper] ?? reset($row);
        }

        // Sin RETURNING
        if (is_resource($res)) {
            ibase_free_result($res);
        }
        return true;
    }

    
    public function executeconreturning($sql, $id_field = null, $params = [])
    {
        try {
            if (stripos($sql, 'INSERT INTO') !== false && $id_field) {
                $sql .= " RETURNING {$id_field}";
            }

            $prep = @ibase_prepare($this->tr ?: $this->conn, $sql);
            if (!$prep) {
                throw new Exception("Error al preparar executeconreturning: " . ibase_errmsg() . " | SQL: {$sql}");
            }

            $res = @ibase_execute($prep, ...array_values($params));
            if ($res === false) {
                return false;
            }

            if ($id_field) {
                $row = ibase_fetch_assoc($res);
                ibase_free_result($res);
                if (!$row) return false;

                $keyUpper = strtoupper($id_field);
                return $row[$keyUpper] ?? reset($row);
            }

            if (is_resource($res)) {
                ibase_free_result($res);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al ejecutar la consulta: " . $e->getMessage());
        }
    }

    public function commit()
    {
        if ($this->inTransaction && $this->tr) {
            @ibase_commit($this->tr);
            $this->tr = null;
            $this->inTransaction = false;
        }
    }

    public function rollback()
    {
        if ($this->inTransaction && $this->tr) {
            @ibase_rollback($this->tr);
            $this->tr = null;
            $this->inTransaction = false;
        }
    }

    public function close()
    {
        if ($this->conn) {
            if ($this->inTransaction) {
                $this->rollback();
            }
            // Las conexiones persistentes no se cierran explícitamente,
            // pero si no es persistente, podemos cerrar:
            if (!$this->persistent) {
                @ibase_close($this->conn);
            }
            $this->conn = null;
        }
    }
}
*/