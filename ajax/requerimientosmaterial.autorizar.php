<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$reqId = isset($_POST['req_id']) ? intval($_POST['req_id']) : 0;
$articulos = isset($_POST['articulos']) ? $_POST['articulos'] : [];

if ($reqId <= 0 || empty($articulos)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

try {
    $db = new FirebirdConnection();

    // 1. Obtener info del requerimiento
    $reqInfo = $db->query("SELECT REQMATERIAL_ALMACENID FROM AMPAR_HIS_REQUERIMIENTOMATERIAL WHERE REQMATERIAL_ID = ?", [$reqId]);
    if (!$reqInfo) {
        throw new Exception("Requerimiento no encontrado.");
    }
    $almacenDestino = $reqInfo[0]['REQMATERIAL_ALMACENID'];

    $ocArticulos = [];
    $traspasoPorAlmacen = [];

    $oc = new oc();

    foreach ($articulos as $art) {
        $artId = intval($art['id']);
        $reqCant = floatval($art['requerido'] ?? 0);
        $cantOC = floatval($art['cant_oc'] ?? 0);
        $cantTras = floatval($art['cant_traspaso'] ?? 0);
        $almOrigen = intval($art['almacen_origen'] ?? 0);
        
        if ($reqCant > 0) {
            $dbReq = new FirebirdConnection(true);
            $dbReq->execute("UPDATE AMPAR_HIS_REQDET SET REQMATERIALDET_CANTIDAD = ? WHERE REQMATERIALDET_REQUERIMIENTOID = ? AND REQMATERIALDET_ARTICULOID = ?", [$reqCant, $reqId, $artId]);
            $dbReq->close();
        }

        if ($cantOC > 0) {
            $costo = $oc->getDefaultCostForArticle($artId);
            $ocArticulos[] = [
                'articulo_id' => $artId,
                'cantidad' => $cantOC,
                'costo' => $costo,
                'descuento_pct' => 0,
                'iva_pct' => 16
            ];
        }

        if ($cantTras > 0 && $almOrigen > 0) {
            if (!isset($traspasoPorAlmacen[$almOrigen])) {
                $traspasoPorAlmacen[$almOrigen] = [];
            }
            // Para el traspaso necesitamos reservar stock.
            $sqlStock = "
                SELECT FIRST ? STOCK_ID 
                FROM AMPAR_HIS_STOCK 
                WHERE STOCK_ARTICULOID = ? AND STOCK_ALMACENIDACTUAL = ? AND STOCK_STOCKSTATUSID = 1
            ";
            $stockDisp = $db->query($sqlStock, [$cantTras, $artId, $almOrigen]);

            if ($stockDisp && count($stockDisp) > 0) {
                foreach ($stockDisp as $st) {
                    $traspasoPorAlmacen[$almOrigen][] = $st['STOCK_ID'];
                }
            } else {
                throw new Exception("No hay suficiente stock en el almacén origen para el artículo ID: $artId");
            }
        }
    }
    // No cerramos $db aquí porque se utiliza más adelante.

    $mensajes = [];

    // 2. No crear OC automáticamente (solo se deja pendiente para crearla manualmente)
    if (count($ocArticulos) > 0) {
        $mensajes[] = "Cantidades para Orden de Compra registradas. (Pendiente de crear OC manualmente)";
    }

    // 3. Crear Traspasos por cada almacén origen
    if (count($traspasoPorAlmacen) > 0) {
        // Obtener nombres de almacenes
        $almIds = implode(',', array_map('intval', array_keys($traspasoPorAlmacen)));
        $almNombres = [];
        $resAlm = $db->query("SELECT ALMACEN_ID, ALMACEN_NOMBRE FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID IN ($almIds)");
        if ($resAlm) {
            foreach ($resAlm as $a) {
                $almNombres[(int)$a['ALMACEN_ID']] = $a['ALMACEN_NOMBRE'];
            }
        }

        $trasObj = new traspasos();
        foreach ($traspasoPorAlmacen as $almOrigen => $detalles) {
            $motivo = "Generado automáticamente desde Requerimiento Material";
            $archivos = [];
            ob_start();
            $trasObj->guardartraspaso($almOrigen, $almacenDestino, $motivo, $detalles, $archivos, $reqId);
            $out = ob_get_clean();

            if (strpos($out, 'OK|') !== false || strpos($out, 'OK_SPECIAL|') !== false) {
                $nombreAlm = $almNombres[$almOrigen] ?? "ID:$almOrigen";
                $mensajes[] = "Traspaso desde <strong>{$nombreAlm}</strong> guardado.";
            } else {
                throw new Exception("Error al crear traspaso: " . $out);
            }
        }
    }

    // 4. Actualizar estado del requerimiento a En Proceso
    $dbReq = new FirebirdConnection(true);
    $dbReq->execute("UPDATE AMPAR_HIS_REQUERIMIENTOMATERIAL SET REQMATERIAL_STATUS = 2 WHERE REQMATERIAL_ID = ?", [$reqId]);
    $dbReq->close();

    $rm = new requerimientosmaterial();
    $rm->actualizarStatusAutomatico($reqId);

    if (isset($db)) {
        $db->close();
    }

    echo json_encode(['ok' => true, 'msg' => implode("<br>", $mensajes)]);
} catch (Throwable $e) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>