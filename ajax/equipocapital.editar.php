<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$equipocapital_id = isset($_POST['equipocapital_id']) ? intval($_POST['equipocapital_id']) : 0;
$articulo_id = isset($_POST['articulo_id']) ? intval($_POST['articulo_id']) : 0;
$referencia = isset($_POST['referencia']) ? trim($_POST['referencia']) : '';
$ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
$marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
$tipoeventos_ids = isset($_POST['tipoevento_id']) ? $_POST['tipoevento_id'] : [];
$almacen_id = isset($_POST['almacen_id']) && $_POST['almacen_id'] !== '' ? intval($_POST['almacen_id']) : null;

if ($equipocapital_id <= 0 && $articulo_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de artículo o equipo capital inválido']);
    exit;
}

try {
    $eqCapital = new equipocapital();
    $success = $eqCapital->guardarEquipoCapital($equipocapital_id, $articulo_id, $referencia, $ubicacion, $marca, $tipoeventos_ids, $almacen_id);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'msg' => $equipocapital_id > 0 ? 'Equipo capital actualizado correctamente' : 'Equipo capital registrado correctamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo guardar el equipo capital'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al guardar: ' . $e->getMessage()
    ]);
}
?>
