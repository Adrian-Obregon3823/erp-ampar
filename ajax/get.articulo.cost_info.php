<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $articuloId = isset($_GET['articulo_id']) ? (int)$_GET['articulo_id'] : 0;
    $proveedorId = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
    
    if ($articuloId <= 0) {
        throw new Exception("ID de artículo inválido.");
    }
    
    $oc = new oc();
    $cost = $oc->getCostForArticleByProvider($articuloId, $proveedorId);
    $mejor = $oc->getCheapestProviderForArticle($articuloId);

    echo json_encode([
        'status' => 'success',
        'cost' => $cost,
        'mejor_proveedor_id' => $mejor ? $mejor['PROVEEDOR_ID'] : null,
        'mejor_proveedor_nombre' => $mejor ? $mejor['PROVEEDOR_NOMBRE'] : null,
        'mejor_proveedor_costo' => $mejor ? (float)$mejor['COSTO'] : null
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
