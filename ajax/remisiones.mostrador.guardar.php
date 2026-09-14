<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $invalmacen = isset($_POST['invalmacen']) ? (int)$_POST['invalmacen'] : 0;
    $tiposalida = isset($_POST['tiposalida']) ? strtoupper(trim((string)$_POST['tiposalida'])) : '';

    $idarticuloarray = isset($_POST['idarticuloarray']) && is_array($_POST['idarticuloarray']) ? $_POST['idarticuloarray'] : [];
    $invdetidarray = isset($_POST['invdetidarray']) && is_array($_POST['invdetidarray']) ? $_POST['invdetidarray'] : [];
    $lotearray = isset($_POST['lotearray']) && is_array($_POST['lotearray']) ? $_POST['lotearray'] : [];
    $caducidadarray = isset($_POST['caducidadarray']) && is_array($_POST['caducidadarray']) ? $_POST['caducidadarray'] : [];
    $seriearray = isset($_POST['seriearray']) && is_array($_POST['seriearray']) ? $_POST['seriearray'] : [];

    if ($invalmacen <= 0) {
        throw new Exception('Debes seleccionar un almacén.');
    }
    if (!in_array($tiposalida, ['PROCEDIMIENTO', 'VENTA_DIRECTA'], true)) {
        throw new Exception('Tipo de salida inválido.');
    }
    if (count($idarticuloarray) === 0 || count($invdetidarray) === 0) {
        throw new Exception('Debes agregar al menos un artículo.');
    }

    $clienteid = isset($_POST['cliente_mostrador']) && $_POST['cliente_mostrador'] !== '' ? (int)$_POST['cliente_mostrador'] : null;
    if ($clienteid !== null && $clienteid < 0) {
        $clienteid = abs($clienteid);
    }
    if ($tiposalida === 'VENTA_DIRECTA' && $clienteid === null) {
        throw new Exception('Debes seleccionar un cliente para la venta directa.');
    }

    $subtotalarray = isset($_POST['subtotalarray']) && is_array($_POST['subtotalarray']) ? $_POST['subtotalarray'] : [];
    $ivaarray = isset($_POST['ivaarray']) && is_array($_POST['ivaarray']) ? $_POST['ivaarray'] : [];
    $totalarray = isset($_POST['totalarray']) && is_array($_POST['totalarray']) ? $_POST['totalarray'] : [];

    $remisiones = new remisiones();
    $creada = $remisiones->guardarremisionmostrador(
        $almacenid ?? $invalmacen,
        $tiposalida,
        $invdetidarray,
        $subtotalarray,
        $ivaarray,
        $totalarray,
        $clienteid
    );

    if (!$creada || !isset($creada['remisionid'])) {
        throw new Exception('No fue posible guardar la remisión mostrador.');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Salida por Remisión Mostrador registrada y finalizada correctamente.',
        'remisionid' => (int)$creada['remisionid'],
        'folio' => $creada['folio'] ?? ''
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>