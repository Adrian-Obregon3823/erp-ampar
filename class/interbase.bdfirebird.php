<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase Sybase
*********************************************************************************
*/
?>
<?php

class FirebirdConnection
{
    private $conn;
    private $trans;
    private $autoCommit = true; // Modo actual: ejecuta y cierra
    private $inTransaction = false;

    public function __construct($autoCommit = true)
    {
        global $host, $dbname, $username, $password;
        $this->autoCommit = $autoCommit;
        $this->connect($host, $dbname, $username, $password);
    }

    public function connect($host, $dbname, $username, $password)
    {
        $this->conn = ibase_connect($host . ':' . $dbname, $username, $password, 'UTF8');

        if (!$this->conn) {
            echo "Error de conexión: " . ibase_errmsg() . "<br>";
        }

        if (!$this->autoCommit) {
            $this->trans = ibase_trans($this->conn);
            $this->inTransaction = true;
        }
    }

    public function query($sql, $params = [])
    {
        $stmt = ibase_prepare($this->conn, $sql);
        if (!$stmt) {
            echo "Error al preparar la consulta: " . ibase_errmsg() . "<br>";
            return [];
        }

        $query = $this->inTransaction
            ? ibase_execute($this->trans, $stmt, ...$params)
            : ibase_execute($stmt, ...$params);

        if ($query) {
            $result = [];
            while ($row = ibase_fetch_assoc($query)) {
                $result[] = $row;
            }
            return $result;
        } else {
            echo "Error en la consulta: " . ibase_errmsg() . "<br>";
            return [];
        }
    }

    public function execute($sql, $params = [])
    {
        $stmt = ibase_prepare($this->conn, $sql);
        if (!$stmt) {
            echo "Error al preparar la consulta: " . ibase_errmsg() . "<br>";
            return false;
        }

        $result = $this->inTransaction
            ? ibase_execute($this->trans, $stmt, ...$params)
            : ibase_execute($stmt, ...$params);

        if (!$result) {
            echo "Error al ejecutar la consulta: " . ibase_errmsg() . "<br>";
        }

        return $result ? true : false;
    }

    public function executeconreturning($sql, $id_field = null, $params = [])
    {
        if (stripos($sql, 'INSERT INTO') !== false && $id_field) {
            $sql .= " RETURNING $id_field";
        }

        $stmt = ibase_prepare($this->conn, $sql);
        if (!$stmt) {
            throw new Exception("Error al preparar: " . ibase_errmsg());
        }

        $success = $this->inTransaction
            ? ibase_execute($this->trans, $stmt, ...$params)
            : ibase_execute($stmt, ...$params);

        if (!$success) {
            throw new Exception("Error al ejecutar la consulta: " . ibase_errmsg());
        }

        // Aquí el cambio clave:
        // En modo transaccional ibase_execute devuelve true/false, no un resource
        // ibase_fetch_assoc acepta el statement para obtener el resultado RETURNING
        if ($this->inTransaction) {
            $row = ibase_fetch_assoc($stmt);
        } else {
            $row = ibase_fetch_assoc($success);
        }

        return $row ? reset($row) : false;
    }

    public function commit()
    {
        if ($this->inTransaction) {
            ibase_commit($this->trans);
            $this->inTransaction = false;
        }
    }

    public function rollback()
    {
        if ($this->inTransaction) {
            ibase_rollback($this->trans);
            $this->inTransaction = false;
        }
    }

    public function close()
    {
        if ($this->conn) {
            if ($this->inTransaction) {
                $this->rollback(); // Si olvidaste el commit, se hace rollback
            }
            ibase_close($this->conn);
        }
    }
}

?>