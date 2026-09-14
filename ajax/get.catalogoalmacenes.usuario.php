<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');

// Obtener las sucursales asignadas al usuario desde la sesión
$almacenes_ids = [];
$db = new FirebirdConnection(true);
$sqlAlm = "SELECT USUARIOSALMACENES_ALMACENID FROM AMPAR_CAT_USUARIOSALMACENES WHERE USUARIOSALMACENES_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
$resAlm = $db->query($sqlAlm);
if ($resAlm && is_array($resAlm)) {
    foreach ($resAlm as $row) {
        $almacenes_ids[] = (int)$row['USUARIOSALMACENES_ALMACENID'];
    }
}

$isAdmin = (isset($_SESSION['ampar']['usuario']['USUARIO_PERFILID']) && $_SESSION['ampar']['usuario']['USUARIO_PERFILID'] == 1);

// Consultar los almacenes, descartando maletas (tipo 3)
$sql = "SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2) ";

if ($isAdmin) {
    // Si es admin, mostrar todos los almacenes
} else if (count($almacenes_ids) > 0) {
    $sql .= " AND ALMACEN_ID IN (" . implode(",", $almacenes_ids) . ")";
} else {
    $sql .= " AND 1 = 0";
}

$sql .= " ORDER BY ALMACEN_NOMBRE";

$result = $db->query($sql);
$db->close();

$filtered = [];
if ($result && is_array($result)) {
    foreach ($result as $row) {
        $filtered[] = $row;
    }
}

echo json_encode($filtered, JSON_UNESCAPED_UNICODE);
?>
