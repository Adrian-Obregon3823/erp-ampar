<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');

$almacenid = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;
$tiposalida = isset($_GET['tiposalida']) ? trim((string)$_GET['tiposalida']) : '';
$maletaid = isset($_GET['maletaid']) ? (int)$_GET['maletaid'] : 0;
$clienteid_raw = isset($_GET['clienteid']) && $_GET['clienteid'] !== '' ? (int)$_GET['clienteid'] : null;
// Hospitals use positive IDs, clients use negative IDs in ARTPRECIO_CLIENTEID
// If the value arrives positive (from REMISION_CLIENTEID stored via abs()), negate it so it matches stored negative client prices
// If already negative (from select2 which returns CLIENTE_ID * -1), keep as-is
// Zero is treated as null (no client filter)
$clienteid = ($clienteid_raw === 0 || $clienteid_raw === null) ? null : $clienteid_raw;

if ($almacenid <= 0 || $tiposalida === '') {
    echo json_encode([]);
    exit;
}

$articulos = new articulos();
$res = $articulos->getcatalogoarticulosremisionmostrador($almacenid, $tiposalida, $maletaid, $clienteid);

if ($res && $res !== 0) {
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([]);
}
?>