<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$familia_id = isset($_POST['familia_id']) ? $_POST['familia_id'] : (isset($_GET['familia_id']) ? $_GET['familia_id'] : '');
$categoria_id = isset($_POST['categoria_id']) ? $_POST['categoria_id'] : (isset($_GET['categoria_id']) ? $_GET['categoria_id'] : '');
$mostrar_inactivos = isset($_POST['mostrar_inactivos']) ? $_POST['mostrar_inactivos'] : (isset($_GET['mostrar_inactivos']) ? $_GET['mostrar_inactivos'] : '0');

try {
    $eqCapital = new equipocapital();
    $result = $eqCapital->getArticulos($familia_id, $categoria_id, $mostrar_inactivos);

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
        'msg' => 'Error al cargar artículos de equipo capital: ' . $e->getMessage()
    ]);
}
?>
