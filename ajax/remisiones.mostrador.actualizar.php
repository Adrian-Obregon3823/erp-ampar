<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $remisionid = isset($_POST['remisionid']) ? (int)$_POST['remisionid'] : 0;
    
    $idarticuloarray = isset($_POST['idarticuloarray']) && is_array($_POST['idarticuloarray']) ? $_POST['idarticuloarray'] : [];
    $invdetidarray = isset($_POST['invdetidarray']) && is_array($_POST['invdetidarray']) ? $_POST['invdetidarray'] : [];
    $subtotalarray = isset($_POST['subtotalarray']) && is_array($_POST['subtotalarray']) ? $_POST['subtotalarray'] : [];
    $ivaarray = isset($_POST['ivaarray']) && is_array($_POST['ivaarray']) ? $_POST['ivaarray'] : [];
    $totalarray = isset($_POST['totalarray']) && is_array($_POST['totalarray']) ? $_POST['totalarray'] : [];

    if ($remisionid <= 0) {
        throw new Exception('ID de remisión inválido.');
    }
    if (count($invdetidarray) === 0) {
        throw new Exception('Debes agregar al menos un artículo.');
    }

    $remisiones = new remisiones();
    $result = $remisiones->actualizarremisionmostrador(
        $remisionid,
        $idarticuloarray,
        $invdetidarray,
        $subtotalarray,
        $ivaarray,
        $totalarray
    );

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
