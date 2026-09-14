<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');

$almacenid = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;
if ($almacenid <= 0) {
    echo json_encode([]);
    exit;
}

$db = new FirebirdConnection(true);
$sql = "
    SELECT
        A.ALMACEN_ID AS ID,
        A.ALMACEN_NOMBRE AS NOMBRE,
        A.ALMACEN_FOLIO AS FOLIO
    FROM AMPAR_HIS_ALMACEN A
    WHERE A.ALMACEN_TIPOALMACEN = 3
      AND A.ALMACEN_ALMACEN_MS = {$almacenid}
      AND A.DELETED_AT IS NULL
    ORDER BY A.ALMACEN_NOMBRE
";
$res = $db->query($sql);
$db->close();

if ($res && $res !== 0) {
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([]);
}
?>