<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $almacenid = isset($_GET['almacenid']) ? intval($_GET['almacenid']) : 0;
    $clienteid = (isset($_GET['clienteid']) && $_GET['clienteid'] !== '') ? intval($_GET['clienteid']) : null;

    if (!$almacenid) {
        echo json_encode([]);
        exit;
    }

    $art = new articulos();
    $result = $art->getEquipoCapitalDisponible($almacenid, $clienteid);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
