<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$almacen_id = $_GET['almacen_id'] ?? 'global';
$grupo_linea_id = $_GET['grupo_linea_id'] ?? '';
$division_id = $_GET['division_id'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

$db = new FirebirdConnection(true);

$where = "ST.STOCK_STOCKSTATUSID IN (1, 2) ";

// Excluir maletas
$where .= " AND (A.ALMACEN_TIPOMALETAID IS NULL OR A.ALMACEN_TIPOMALETAID = 0) AND (A.ALMACEN_NOMBRE IS NULL OR UPPER(A.ALMACEN_NOMBRE) NOT CONTAINING 'MALETA')";

if ($almacen_id === 'sin_almacen') {
    $where .= " AND (ST.STOCK_ALMACENIDACTUAL IS NULL OR ST.STOCK_ALMACENIDACTUAL = 0)";
} elseif ($almacen_id !== 'global' && $almacen_id != '') {
    $where .= " AND ST.STOCK_ALMACENIDACTUAL = " . (int)$almacen_id;
} else {
    // Si es global, verificar permisos para no admins
    $esAdmin = !empty($GLOBALS['isAdmin']);
    if (!$esAdmin) {
        $sqlAlm = "SELECT USUARIOSALMACENES_ALMACENID FROM AMPAR_CAT_USUARIOSALMACENES WHERE USUARIOSALMACENES_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
        $resAlm = $db->query($sqlAlm);
        $almacenes_ids = [];
        if ($resAlm && is_array($resAlm)) {
            foreach ($resAlm as $row) {
                $almacenes_ids[] = (int)$row['USUARIOSALMACENES_ALMACENID'];
            }
        }
        if (count($almacenes_ids) > 0) {
            $where .= " AND ST.STOCK_ALMACENIDACTUAL IN (" . implode(",", $almacenes_ids) . ")";
        } else {
            $where .= " AND 1=0"; // No tiene acceso a ningún almacén
        }
    }
}

if ($busqueda != "") {
    $buscar = strtoupper($busqueda);
    $where .= " AND (
        UPPER(X.CLAVE_ARTICULO) CONTAINING '" . $buscar . "'
        OR UPPER(AR.NOMBRE) CONTAINING '" . $buscar . "'
        OR UPPER(ST.STOCK_FOLIO) CONTAINING '" . $buscar . "'
    )";
}

if ($categoria_id != '') {
    $where .= " AND AR.LINEA_ARTICULO_ID = " . (int)$categoria_id;
} elseif ($division_id != '') {
    $where .= " AND RLD.DIVISION_ID = " . (int)$division_id;
} elseif ($grupo_linea_id != '') {
    $where .= " AND LA.GRUPO_LINEA_ID = " . (int)$grupo_linea_id;
}

$sql = "
    SELECT 
        ST.STOCK_FOLIO AS FOLIO,
        X.CLAVE_ARTICULO AS REFERENCIA,
        AR.NOMBRE AS DESCRIPCION,
        ST.STOCK_LOTE AS LOTE,
        ST.STOCK_CADUCIDAD AS CADUCIDAD,
        A.ALMACEN_NOMBRE
    FROM AMPAR_HIS_STOCK ST
    LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
    LEFT JOIN LINEAS_ARTICULOS LA ON LA.LINEA_ARTICULO_ID = AR.LINEA_ARTICULO_ID
    LEFT JOIN AMPAR_REL_LINEA_DIVISION RLD ON RLD.LINEA_ARTICULO_ID = LA.LINEA_ARTICULO_ID
    LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
    WHERE $where
    ORDER BY A.ALMACEN_NOMBRE, AR.NOMBRE, ST.STOCK_CADUCIDAD ASC
";

$items = $db->query($sql);
$db->close();

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=inventario_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Inventario</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta charset="utf-8">';
echo '</head><body>';
echo '<table border="1" style="font-family: Arial, sans-serif; font-size: 12px; border-collapse: collapse;">';
echo '<tr>';
if ($almacen_id === 'global') {
    echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">ALMACÉN</th>';
}
echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">FOLIO</th>';
echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">REFERENCIA</th>';
echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">DESCRIPCIÓN</th>';
echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">LOTE</th>';
echo '<th style="background-color: #1f3bb3; color: white; font-weight: bold; text-align: center;">CADUCIDAD</th>';
echo '</tr>';

if ($items) {
    foreach ($items as $row) {
        echo '<tr>';
        if ($almacen_id === 'global') {
            echo '<td>' . htmlspecialchars($row['ALMACEN_NOMBRE'] ?? 'N/A') . '</td>';
        }
        echo '<td>' . htmlspecialchars($row['FOLIO']) . '</td>';
        echo '<td>' . htmlspecialchars($row['REFERENCIA']) . '</td>';
        echo '<td>' . htmlspecialchars($row['DESCRIPCION']) . '</td>';
        echo '<td>' . htmlspecialchars($row['LOTE'] ?? '—') . '</td>';
        
        $caducidad = $row['CADUCIDAD'] ? date('Y-m-d', strtotime($row['CADUCIDAD'])) : '—';
        echo '<td>' . $caducidad . '</td>';
        echo '</tr>';
    }
}
echo '</table></body></html>';
