<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $almacenId = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '1,2';
    $proveedorId = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
    $soloOptimos = isset($_GET['solo_optimos']) && $_GET['solo_optimos'] == '1';

    $rm = new requerimientosmaterial();
    $oc = new oc();
    $res = $rm->getcatalogorequerimientos($almacenId > 0 ? $almacenId : '', $status);

    $items = [];
    if ($res && $res != 0) {
        foreach ($res as $r) {
            $reqId = $r['ID'];
            $cumplimiento = $rm->obtenerCumplimientoDetallado($reqId);
            
            $articulosPendientes = 0;
            if (!empty($cumplimiento) && is_array($cumplimiento)) {
                foreach ($cumplimiento as $c) {
                    $cantFaltante = max(0, $c['cantidad_requerida'] - $c['cantidad_transferida'] - $c['cantidad_comprada']);
                    if ($cantFaltante > 0) {
                        $articuloId = (int)$c['articulo_id'];
                        $shouldAdd = true;
                        if ($soloOptimos && $proveedorId > 0) {
                            $mejor = $oc->getCheapestProviderForArticle($articuloId);
                            if ($mejor && $mejor['PROVEEDOR_ID']) {
                                if ($mejor['PROVEEDOR_ID'] != $proveedorId) {
                                    $shouldAdd = false;
                                }
                            }
                        }
                        if ($shouldAdd) {
                            $articulosPendientes++;
                        }
                    }
                }
            } else {
                // If no detail, we assume all are pending, but we can't filter optimally here
                $articulosPendientes = (int)$r['ARTICULOS'];
            }
            
            if ($articulosPendientes > 0) {
                $r['ARTICULOS_PENDIENTES'] = $articulosPendientes;
                $items[] = $r;
            }
        }
    }

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