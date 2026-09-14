<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$equipocapital_id = isset($_POST['equipocapital_id']) ? intval($_POST['equipocapital_id']) : (isset($_GET['equipocapital_id']) ? intval($_GET['equipocapital_id']) : 0);

if ($equipocapital_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de equipo capital inválido']);
    exit;
}

try {
    $eqCapital = new equipocapital();
    $result = $eqCapital->getComponentes($equipocapital_id);

    if (!is_array($result)) {
        $result = [];
    }

    echo json_encode([
        'ok' => true,
        'items' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al cargar componentes: ' . $e->getMessage()
    ]);
}
?>
