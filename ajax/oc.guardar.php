<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $ocid = isset($_POST['ocid']) ? (int)$_POST['ocid'] : 0;
    $almacenId = isset($_POST['almacenid']) ? (int)$_POST['almacenid'] : 0;
    $proveedorId = isset($_POST['proveedorid']) ? (int)$_POST['proveedorid'] : 0;
    $statusId = isset($_POST['statusid']) ? (int)$_POST['statusid'] : 1; // Default to Borrador (1)
    $requerimientomaterialid_raw = isset($_POST['requerimientomaterialid']) ? $_POST['requerimientomaterialid'] : null;
    $requerimientomaterialid = [];
    if (!empty($requerimientomaterialid_raw)) {
        if (is_array($requerimientomaterialid_raw)) {
            $requerimientomaterialid = array_map('intval', $requerimientomaterialid_raw);
        } else {
            $requerimientomaterialid = array_map('intval', explode(',', $requerimientomaterialid_raw));
        }
        $requerimientomaterialid = array_filter($requerimientomaterialid, function($v) { return $v > 0; });
    }
    
    $idarticuloarray = isset($_POST['idarticuloarray']) && is_array($_POST['idarticuloarray']) ? $_POST['idarticuloarray'] : [];
    $cantidadarray = isset($_POST['cantidadarray']) && is_array($_POST['cantidadarray']) ? $_POST['cantidadarray'] : [];
    $costoarray = isset($_POST['costoarray']) && is_array($_POST['costoarray']) ? $_POST['costoarray'] : [];
    $descuentoarray = isset($_POST['descuentoarray']) && is_array($_POST['descuentoarray']) ? $_POST['descuentoarray'] : [];
    $ivaarray = isset($_POST['ivaarray']) && is_array($_POST['ivaarray']) ? $_POST['ivaarray'] : [];
    
    $descuentotipoarray = isset($_POST['descuentotipoarray']) && is_array($_POST['descuentotipoarray']) ? $_POST['descuentotipoarray'] : [];
    $descuentomotivoarray = isset($_POST['descuentomotivoarray']) && is_array($_POST['descuentomotivoarray']) ? $_POST['descuentomotivoarray'] : [];
    
    $descuentoglobalpct = isset($_POST['descuentoglobalpct']) ? (float)$_POST['descuentoglobalpct'] : 0.00;
    
    if ($almacenId <= 0) {
        throw new Exception('Debes seleccionar un almacén.');
    }
    if ($proveedorId <= 0) {
        throw new Exception('Debes seleccionar un proveedor.');
    }
    if (count($idarticuloarray) === 0) {
        throw new Exception('Debes agregar al menos un artículo.');
    }
    
    $articulos = [];
    for ($i = 0; $i < count($idarticuloarray); $i++) {
        $articulos[] = [
            'articulo_id' => (int)$idarticuloarray[$i],
            'cantidad' => (float)$cantidadarray[$i],
            'costo' => (float)$costoarray[$i],
            'descuento_pct' => (float)($descuentoarray[$i] ?? 0),
            'iva_pct' => (float)($ivaarray[$i] ?? 16),
            'desc_tipo' => $descuentotipoarray[$i] ?? '',
            'desc_motivo' => $descuentomotivoarray[$i] ?? ''
        ];
    }
    
    $oc = new oc();
    if ($ocid > 0) {
        $res = $oc->editarManual($ocid, $almacenId, $proveedorId, $statusId, $articulos, $requerimientomaterialid, $descuentoglobalpct);
    } else {
        $res = $oc->guardarManual($almacenId, $proveedorId, $statusId, $articulos, $requerimientomaterialid, $descuentoglobalpct);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Orden de Compra guardada correctamente.',
        'oc_id' => (int)$res['oc_id'],
        'folio' => $res['folio']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
