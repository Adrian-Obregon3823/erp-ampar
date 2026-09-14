<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $reqIdsParam = isset($_GET['reqid']) ? $_GET['reqid'] : null;
    $proveedorId = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
    
    $reqIds = [];
    if (is_array($reqIdsParam)) {
        $reqIds = array_map('intval', $reqIdsParam);
    } elseif (is_string($reqIdsParam)) {
        $reqIds = array_map('intval', explode(',', $reqIdsParam));
    } elseif (is_numeric($reqIdsParam)) {
        $reqIds = [(int)$reqIdsParam];
    }
    $reqIds = array_filter($reqIds, function($v) { return $v > 0; });

    if (count($reqIds) === 0) {
        throw new Exception('Requerimiento inválido.');
    }

    $rm = new requerimientosmaterial();
    $oc = new oc();
    
    $combinedItems = [];

    foreach ($reqIds as $reqId) {
        $rows = $rm->getrequerimientobyid($reqId);
        if (!$rows || $rows == 0) continue;

        $cumplimiento = $rm->obtenerCumplimientoDetallado($reqId);

        $cumpMap = [];
        if (!empty($cumplimiento) && is_array($cumplimiento)) {
            foreach ($cumplimiento as $c) {
                $cumpMap[$c['articulo_id']] = $c;
            }
        }

        foreach ($rows as $r) {
            if (empty($r['REQMATERIALDET_ID'])) {
                continue;
            }

            $articuloId = (int)$r['REQMATERIALDET_ARTICULOID'];
            
            $cantOriginal = (float)$r['REQMATERIALDET_CANTIDAD'];
            $cantFaltante = $cantOriginal;
            
            if (isset($cumpMap[$articuloId])) {
                $c = $cumpMap[$articuloId];
                $cantFaltante = max(0, $c['cantidad_requerida'] - $c['cantidad_transferida'] - $c['cantidad_comprada']);
            }

            if ($cantFaltante > 0) {
                if (!isset($combinedItems[$articuloId])) {
                    $mejor = $oc->getCheapestProviderForArticle($articuloId);
                    
                    $combinedItems[$articuloId] = [
                        'articulo_id' => $articuloId,
                        'clave' => $r['CLAVE_ARTICULO'] ?? '',
                        'nombre' => $r['ARTICULO_NOMBRE'] ?? '',
                        'cantidad' => 0,
                        'costo' => (float)$oc->getCostForArticleByProvider($articuloId, $proveedorId),
                        'mejor_proveedor_id' => $mejor ? $mejor['PROVEEDOR_ID'] : null,
                        'mejor_proveedor_nombre' => $mejor ? $mejor['PROVEEDOR_NOMBRE'] : null,
                        'mejor_proveedor_costo' => $mejor ? (float)$mejor['COSTO'] : null
                    ];
                }
                $combinedItems[$articuloId]['cantidad'] += $cantFaltante;
            }
        }
    }

    $items = array_values($combinedItems);

    echo json_encode([
        'ok' => true,
        'items' => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>