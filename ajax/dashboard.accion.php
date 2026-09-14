<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../includes/services/dashboard.inventario.service.php");

if (!isset($_SESSION['ampar']['usuario'])) {
    echo json_encode(['ok' => false, 'message' => 'Sesión expirada.']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = new FirebirdConnection(true);

if ($action === 'evaluar' || $action === 'ejecutar') {
    $stockId = (int)($_POST['stock_id'] ?? 0);

    if ($stockId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Stock ID inválido.']);
        exit;
    }

    // 1. Obtener detalles del item por caducar (Item A)
    $sqlA = "
        SELECT ST.STOCK_ARTICULOID, ST.STOCK_CADUCIDAD, ST.STOCK_ALMACENIDACTUAL, 
               A.ALMACEN_ALMACEN_MS, A.ALMACEN_TIPOALMACEN, A.ALMACEN_NOMBRE,
               AR.NOMBRE AS ARTICULO_NOMBRE
        FROM AMPAR_HIS_STOCK ST
        LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
        LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
        WHERE ST.STOCK_ID = ? AND ST.STOCK_STOCKSTATUSID = 1
    ";
    $itemA = $db->query($sqlA, [$stockId]);

    if (!$itemA || count($itemA) === 0) {
        echo json_encode(['ok' => false, 'message' => 'El artículo ya no se encuentra físicamente en el almacén.']);
        exit;
    }
    $itemA = $itemA[0];

    if ($itemA['ALMACEN_TIPOALMACEN'] == 3) {
        echo json_encode(['ok' => false, 'message' => 'La acción inteligente solo aplica para artículos que no están en maleta.']);
        exit;
    }

    $articuloId = $itemA['STOCK_ARTICULOID'];
    $almacenPadreId = $itemA['ALMACEN_ALMACEN_MS'] ? $itemA['ALMACEN_ALMACEN_MS'] : $itemA['STOCK_ALMACENIDACTUAL'];
    $almacenActualId = $itemA['STOCK_ALMACENIDACTUAL'];
    $caducidadA = $itemA['STOCK_CADUCIDAD'];
    $nombArticulo = $itemA['ARTICULO_NOMBRE'];

if ($action === 'evaluar') {
    // A) Buscar maletas en el MISMO ALMACEN_PADRE que necesiten este articulo
    $sqlFaltante = "
        SELECT M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID, 
               TMD.TIPOMALETADET_CANTIDADSUGERIDA,
               (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                WHERE S.STOCK_ALMACENIDACTUAL = M.ALMACEN_ID 
                  AND S.STOCK_ARTICULOID = ? 
                  AND S.STOCK_STOCKSTATUSID IN (1, 2)) AS ACTUAL
        FROM AMPAR_HIS_ALMACEN M
        JOIN AMPAR_HIS_TIPOMALETADET TMD ON TMD.TIPOMALETADET_TIPOMALETAID = M.ALMACEN_TIPOMALETAID
        WHERE M.ALMACEN_TIPOALMACEN = 3 
          AND M.ALMACEN_ALMACEN_MS = ?
          AND M.DELETED_AT IS NULL
          AND TMD.TIPOMALETADET_ARTICULOID = ?
    ";
    $maletas = $db->query($sqlFaltante, [$articuloId, $almacenPadreId, $articuloId]) ?: [];
    
    $maletaDestino = null;
    foreach ($maletas as $m) {
        if ($m['ACTUAL'] < $m['TIPOMALETADET_CANTIDADSUGERIDA']) {
            $maletaDestino = $m;
            break;
        }
    }

    if ($maletaDestino) {
        echo json_encode([
            'ok' => true,
            'suggestion' => 'transfer',
            'message' => "La maleta <strong>{$maletaDestino['MALETA_NOMBRE']}</strong> tiene un faltante del artículo {$nombArticulo}. ¿Deseas generar una solicitud de traspaso hacia esta maleta?",
            'data' => [
                'target_maleta_id' => $maletaDestino['MALETA_ID']
            ]
        ]);
        exit;
    }

    // B) Ninguna maleta lo necesita. Buscar maleta que lo tenga con CADUCIDAD > CADUCIDAD_A
    $sqlSwap = "
        SELECT FIRST 1 S.STOCK_ID AS SWAP_STOCK_ID, S.STOCK_CADUCIDAD AS SWAP_CADUCIDAD,
               M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID
        FROM AMPAR_HIS_STOCK S
        JOIN AMPAR_HIS_ALMACEN M ON M.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
        WHERE M.ALMACEN_TIPOALMACEN = 3 
          AND M.ALMACEN_ALMACEN_MS = ?
          AND S.STOCK_ARTICULOID = ?
          AND S.STOCK_STOCKSTATUSID = 1
          AND S.STOCK_CADUCIDAD > ?
        ORDER BY S.STOCK_CADUCIDAD DESC
    ";
    $swapResult = $db->query($sqlSwap, [$almacenPadreId, $articuloId, $caducidadA]);

    if ($swapResult && count($swapResult) > 0) {
        $swapData = $swapResult[0];
        $fechaVencimientoFresca = date('d/m/Y', strtotime($swapData['SWAP_CADUCIDAD']));
        
        echo json_encode([
            'ok' => true,
            'suggestion' => 'swap',
            'message' => "La maleta <strong>{$swapData['MALETA_NOMBRE']}</strong> ya tiene el artículo {$nombArticulo}, pero expira después ({$fechaVencimientoFresca}).<br><br>¿Deseas generar <strong>dos solicitudes de traspaso</strong> para ingresar el que está por caducar a la maleta, y sacar el más nuevo al almacén?",
            'data' => [
                'target_maleta_id' => $swapData['MALETA_ID'],
                'swap_stock_id' => $swapData['SWAP_STOCK_ID']
            ]
        ]);
        exit;
    }

    echo json_encode([
        'ok' => false, 
        'message' => 'No se encontraron maletas que necesiten este artículo o que tengan uno con mayor caducidad en el mismo Almacén Principal.'
    ]);
    exit;

} elseif ($action === 'ejecutar') {
    $suggestion = $_POST['suggestion'] ?? '';
    $targetMaletaId = (int)($_POST['target_maleta_id'] ?? 0);
    $swapStockId = (int)($_POST['swap_stock_id'] ?? 0);

    if ($targetMaletaId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Maleta destino no válida.']);
        exit;
    }

    $clsTraspasos = new traspasos();

    if ($suggestion === 'transfer') {
        $db->close(); 
        
        $motivo = "INGRESO A MALETA POR PRÓXIMA CADUCIDAD";
        $articulos = [$stockId];
        
        ob_start();
        $clsTraspasos->guardartraspaso($almacenActualId, $targetMaletaId, $motivo, $articulos);
        $out = ob_get_clean();
        if ($out) {
            echo json_encode(['ok' => false, 'message' => $out]);
            exit;
        }

        // Invalidar cache del dashboard después de crear traspaso
        if (function_exists('inv_dashboard_cache_delete')) {
            inv_dashboard_cache_delete();
        }

        echo json_encode(['ok' => true, 'message' => 'Solicitud de traspaso generada correctamente.']);
        exit;
    } elseif ($suggestion === 'swap') {
        if ($swapStockId <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Stock destino para swap no válido.']);
            exit;
        }

        $db->close();
        
        ob_start();
        // Traspaso 1: Del almacén general a la maleta (el que va a caducar)
        $motivo1 = "SWAP (INGRESO): ENTRADA A MALETA DE PRODUCTO POR CADUCAR";
        $articulos1 = [$stockId];
        $clsTraspasos->guardartraspaso($almacenActualId, $targetMaletaId, $motivo1, $articulos1);

        // Traspaso 2: De la maleta al almacén general (el que tiene más vida)
        $motivo2 = "SWAP (RETIRO): SALIDA DE MALETA DE PRODUCTO MÁS NUEVO";
        $articulos2 = [$swapStockId];
        $clsTraspasos->guardartraspaso($targetMaletaId, $almacenActualId, $motivo2, $articulos2);
        
        $out = ob_get_clean();
        if ($out) {
            echo json_encode(['ok' => false, 'message' => $out]);
            exit;
        }

        // Invalidar cache del dashboard después de crear traspasos
        if (function_exists('inv_dashboard_cache_delete')) {
            inv_dashboard_cache_delete();
        }

        echo json_encode(['ok' => true, 'message' => 'Se generaron 2 solicitudes de traspaso (Ingreso y Retiro) correctamente.']);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción de ejecución desconocida.']);
    exit;
}

} elseif ($action === 'evaluar_bulk') {
    $stockIdsRaw = $_POST['stock_ids'] ?? '';
    // sanitize
    $idsRaw = explode(',', $stockIdsRaw);
    $ids = [];
    foreach($idsRaw as $v) {
        if ((int)$v > 0) $ids[] = (int)$v;
    }
    
    if (empty($ids)) { 
        echo json_encode(['ok' => false, 'message' => 'No hay items seleccionados.']);
        exit; 
    }

    // Fetch details for the first one to determine context
    $firstId = $ids[0];
    $sqlA = "SELECT ST.STOCK_ARTICULOID, ST.STOCK_CADUCIDAD, ST.STOCK_ALMACENIDACTUAL, A.ALMACEN_ALMACEN_MS, A.ALMACEN_NOMBRE, AR.NOMBRE AS ARTICULO_NOMBRE
             FROM AMPAR_HIS_STOCK ST LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
             LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
             WHERE ST.STOCK_ID = ?";
    $itemAData = $db->query($sqlA, [$firstId]);
    $itemA = $itemAData[0] ?? null;
    
    if (!$itemA) {
        echo json_encode(['ok' => false, 'message' => 'El artículo ya no se encuentra.']);
        exit;
    }
    
    $articuloId = $itemA['STOCK_ARTICULOID'];
    $almacenPadreId = $itemA['ALMACEN_ALMACEN_MS'] ? $itemA['ALMACEN_ALMACEN_MS'] : $itemA['STOCK_ALMACENIDACTUAL'];
    $almacenActualId = $itemA['STOCK_ALMACENIDACTUAL'];
    $caducidadA = $itemA['STOCK_CADUCIDAD'];
    $nombArticulo = $itemA['ARTICULO_NOMBRE'];

    // 1. Find all maletas lacking
    $sqlFaltante = "
        SELECT M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID, 
               TMD.TIPOMALETADET_CANTIDADSUGERIDA,
               (SELECT COUNT(*) FROM AMPAR_HIS_STOCK S 
                WHERE S.STOCK_ALMACENIDACTUAL = M.ALMACEN_ID 
                  AND S.STOCK_ARTICULOID = ? 
                  AND S.STOCK_STOCKSTATUSID IN (1, 2)) AS ACTUAL
        FROM AMPAR_HIS_ALMACEN M
        JOIN AMPAR_HIS_TIPOMALETADET TMD ON TMD.TIPOMALETADET_TIPOMALETAID = M.ALMACEN_TIPOMALETAID
        WHERE M.ALMACEN_TIPOALMACEN = 3 
          AND M.ALMACEN_ALMACEN_MS = ?
          AND M.DELETED_AT IS NULL
          AND TMD.TIPOMALETADET_ARTICULOID = ?
    ";
    $maletas = $db->query($sqlFaltante, [$articuloId, $almacenPadreId, $articuloId]) ?: [];
    
    // 2. Find all potential swaps
    $sqlSwap = "
        SELECT S.STOCK_ID AS SWAP_STOCK_ID, S.STOCK_CADUCIDAD AS SWAP_CADUCIDAD,
               M.ALMACEN_ID AS MALETA_ID, M.ALMACEN_NOMBRE AS MALETA_NOMBRE, M.ALMACEN_TIPOMALETAID
        FROM AMPAR_HIS_STOCK S
        JOIN AMPAR_HIS_ALMACEN M ON M.ALMACEN_ID = S.STOCK_ALMACENIDACTUAL
        WHERE M.ALMACEN_TIPOALMACEN = 3 
          AND M.ALMACEN_ALMACEN_MS = ?
          AND S.STOCK_ARTICULOID = ?
          AND S.STOCK_STOCKSTATUSID = 1
          AND S.STOCK_CADUCIDAD > ?
        ORDER BY S.STOCK_CADUCIDAD DESC
    ";
    $swaps = $db->query($sqlSwap, [$almacenPadreId, $articuloId, $caducidadA]) ?: [];

    // Distribute
    $operations = [];
    $directCount = 0;
    $swapCount = 0;
    $unassignedCount = 0;

    foreach ($ids as $sId) {
        $assigned = false;
        
        // Try direct transfer
        foreach ($maletas as &$m) {
            if ($m['ACTUAL'] < $m['TIPOMALETADET_CANTIDADSUGERIDA']) {
                $operations[] = ['type' => 'transfer', 'stock_id' => $sId, 'target_maleta_id' => $m['MALETA_ID']];
                $m['ACTUAL']++;
                $directCount++;
                $assigned = true;
                break;
            }
        }
        
        if (!$assigned) {
            // Try swap
            if (!empty($swaps)) {
                $swp = array_shift($swaps);
                $operations[] = ['type' => 'swap', 'stock_id' => $sId, 'target_maleta_id' => $swp['MALETA_ID'], 'swap_stock_id' => $swp['SWAP_STOCK_ID']];
                $swapCount++;
                $assigned = true;
            }
        }
        
        if (!$assigned) {
            $unassignedCount++;
        }
    }

    if ($directCount == 0 && $swapCount == 0) {
        echo json_encode(['ok' => false, 'message' => "No se requieren ingresos directos ni existen swaps viables para el grupo de {$nombArticulo} en las maletas asociadas."]);
        exit;
    }

    $msgParts = [];
    if ($directCount > 0) $msgParts[] = "<strong>$directCount traspasos directos</strong> por necesidad";
    if ($swapCount > 0) $msgParts[] = "<strong>$swapCount swaps</strong> por mejora de caducidad";
    $msg = "Identifiqué capacidad óptima para:<br><br>" . implode("<br>", $msgParts) . ".<br><br>¿Proceder con la generación agrupada (y registrar todo)?";

    if ($unassignedCount > 0) {
        $msg .= "<br><br><small><i>Nota: $unassignedCount artículos se quedarán en el almacén (no hay destino).</i></small>";
    }

    echo json_encode([
        'ok' => true,
        'message' => $msg,
        'data' => [
            'almacen_actual_id' => $almacenActualId,
            'ops' => $operations
        ]
    ]);
    exit;

} elseif ($action === 'ejecutar_bulk') {
    $rawData = json_decode($_POST['suggestion_data'] ?? '{}', true);
    $ops = $rawData['ops'] ?? [];
    $almacenActualId = (int)($rawData['almacen_actual_id'] ?? 0);

    if (empty($ops)) { 
        echo json_encode(['ok' => false, 'message' => 'No hay operaciones para ejecutar.']); 
        exit; 
    }

    $db->close(); 
    $clsTraspasos = new traspasos();
    ob_start();
    $success = 0;
    $groupedIngresos = []; // Mezcla de Transfers y SwapIns
    $groupedSwapOuts = [];

    foreach ($ops as $op) {
        if ($op['type'] == 'transfer') {
            $groupedIngresos[$op['target_maleta_id']][] = $op['stock_id'];
        } elseif ($op['type'] == 'swap') {
            $groupedIngresos[$op['target_maleta_id']][] = $op['stock_id'];
            $groupedSwapOuts[$op['target_maleta_id']][] = $op['swap_stock_id'];
        }
    }

    foreach ($groupedIngresos as $maletaId => $stockIds) {
        $clsTraspasos->guardartraspaso($almacenActualId, $maletaId, "INGRESO A MALETA (ACCIÓN INTELIGENTE)", $stockIds);
        $success++;
    }
    foreach ($groupedSwapOuts as $maletaId => $swapStockIds) {
        $clsTraspasos->guardartraspaso($maletaId, $almacenActualId, "RETIRO DESDE MALETA (ACCIÓN INTELIGENTE)", $swapStockIds);
        $success++;
    }
    
    $out = ob_get_clean();
    if ($out) {
        echo json_encode(['ok' => false, 'message' => "Se procesaron $success registros antes de un fallo: $out"]);
        exit;
    }

    if (function_exists('inv_dashboard_cache_delete')) {
        inv_dashboard_cache_delete();
    }

    echo json_encode(['ok' => true, 'message' => "Procesamiento grupal completado exitosamente."]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Acción desconocida.']);
exit;
