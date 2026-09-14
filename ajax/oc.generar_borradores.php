<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $sucursalId = isset($_POST['sucursalid']) ? (int)$_POST['sucursalid'] : 0;
    if ($sucursalId <= 0) {
        throw new Exception("Debes seleccionar una sucursal.");
    }
    
    $oc = new oc();
    $creados = $oc->crearBorradoresSugeridos($sucursalId);
    
    echo json_encode([
        'status' => 'success',
        'message' => "Se generaron {$creados} borradores de órdenes de compra con éxito.",
        'creados' => $creados
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
