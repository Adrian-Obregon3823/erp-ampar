<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $almacenId = isset($_POST['almacenid']) ? (int)$_POST['almacenid'] : 0;
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    $statusId = isset($_POST['statusid']) ? (int)$_POST['statusid'] : 1;

    $articuloIds = isset($_POST['articuloid']) && is_array($_POST['articuloid']) ? $_POST['articuloid'] : [];
    $cantidades = isset($_POST['cantidad']) && is_array($_POST['cantidad']) ? $_POST['cantidad'] : [];

    if ($almacenId <= 0) {
        throw new Exception('Debes seleccionar un almacén.');
    }
    if (count($articuloIds) === 0) {
        throw new Exception('Debes agregar al menos un artículo.');
    }

    $articulos = [];
    for ($i = 0; $i < count($articuloIds); $i++) {
        $articulos[] = [
            'articulo_id' => (int)$articuloIds[$i],
            'cantidad' => (float)($cantidades[$i] ?? 0)
        ];
    }

    $rm = new requerimientosmaterial();
    $usuario = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? null;
    $res = $rm->guardar($almacenId, $observaciones, $articulos, $usuario, $statusId);

    echo json_encode([
        'ok' => true,
        'msg' => 'Requerimiento guardado correctamente.',
        'folio' => $res['folio'] ?? '',
        'id' => $res['reqmaterial_id'] ?? null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>