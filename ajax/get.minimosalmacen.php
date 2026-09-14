<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$almacenId = isset($_GET['almacenid']) ? intval($_GET['almacenid']) : 0;

if ($almacenId <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $db = new FirebirdConnection();
    $sql = "SELECT MINIMO_ARTICULOID AS ARTICULO_ID, MINIMO_CANTIDAD AS CANTIDAD FROM AMPAR_HIS_MINIMOS WHERE MINIMO_ALMACENID = ?";
    $res = $db->query($sql, [$almacenId]);
    $res = is_array($res) ? $res : [];
    echo json_encode($res);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
