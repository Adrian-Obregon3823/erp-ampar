<?php
 /*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase de Paginación Firebird
*********************************************************************************
*/
?>
<?php
class FirebirdPaginator {
    private $conn;       // instancia de FirebirdConnection
    private $baseSql;    // SQL sin orden ni límite
    private $params;     // parámetros para filtros (array)
    private $orderBy;    // cadena ORDER BY
    private $page;       // página actual
    private $perPage;    // registros por página

    public function __construct($conn, $baseSql, $params = [], $orderBy = "BITACORA_ID DESC", $page = 1, $perPage = 10) {
        $this->conn = $conn;
        $this->baseSql = $baseSql;
        $this->params = $params;
        $this->orderBy = $orderBy;
        $this->page = max(1, intval($page));
        $this->perPage = max(1, intval($perPage));
    }

    public function getTotalRows() {
        $sql = "SELECT COUNT(*) AS TOTAL FROM (" . $this->baseSql . ") T";
        $res = $this->conn->query($sql, $this->params);
        if ($res && isset($res[0]['TOTAL'])) {
            return (int) $res[0]['TOTAL'];
        }
        return 0;
    }

    public function getPageResults() {
        $start = ($this->page - 1) * $this->perPage + 1;
        $end = $start + $this->perPage -1;
        $sql = $this->baseSql . " ORDER BY " . $this->orderBy . " ROWS $start TO $end";
        return $this->conn->query($sql, $this->params);
    }

    public function getPaginationInfo() {
        $totalRows = $this->getTotalRows();
        $totalPages = (int) ceil($totalRows / $this->perPage);
        return [
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'currentPage' => $this->page,
            'perPage' => $this->perPage,
            'hasPrev' => $this->page > 1,
            'hasNext' => $this->page < $totalPages
        ];
    }
}
