<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php include_once("../includes/services/dashboard.inventario.service.php"); ?>
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    $db = new FirebirdConnection(true);
    $periodoDias = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 90;
    $almacenId = $_GET['almacen_id'] ?? 'global';

    $esAdmin = !empty($GLOBALS['isAdmin']);
    if (!$esAdmin) {
        $almacenes_ids = [];
        $sqlAlm = "SELECT USUARIOSALMACENES_ALMACENID FROM AMPAR_CAT_USUARIOSALMACENES WHERE USUARIOSALMACENES_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
        $resAlm = $db->query($sqlAlm);
        if ($resAlm && is_array($resAlm)) {
            foreach ($resAlm as $row) {
                $almacenes_ids[] = (int)$row['USUARIOSALMACENES_ALMACENID'];
            }
        }
        if (empty($almacenes_ids)) $almacenes_ids = [-1];

        // Fetch their allowed warehouses
        $sqlList = "SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2) AND ALMACEN_ID IN (" . implode(",", $almacenes_ids) . ") ORDER BY ALMACEN_NOMBRE";
        $listaResult = $db->query($sqlList);
        $allowedIds = [];
        $firstAllowed = null;
        if ($listaResult && is_array($listaResult)) {
            foreach ($listaResult as $al) {
                $nombre = strtoupper($al['NOMBRE']);
                if (strpos($nombre, 'CADUCADO') === false && strpos($nombre, 'MALETA') === false) {
                    $allowedIds[] = (string)$al['ID'];
                    if ($firstAllowed === null) $firstAllowed = (string)$al['ID'];
                }
            }
        }
        
        if ($almacenId === 'global' || !in_array((string)$almacenId, $allowedIds)) {
            $almacenId = $firstAllowed ?? '-1';
        }
    }

    $dashboardViewModel = inv_get_dashboard_inventario_data($db, $periodoDias, $almacenId);

    $payload = [
        'ok' => true,
        'periodoDias' => (int)$dashboardViewModel['periodoDias'],
        'almacenId' => $dashboardViewModel['almacenId'],
        'chartData' => $dashboardViewModel['chartData'],
        'kpis' => $dashboardViewModel['dashboardInitialPayload']['kpis'],
        'flujo' => $dashboardViewModel['dashboardInitialPayload']['flujo'],
        'articulosPorCaducidad' => $dashboardViewModel['articulosPorCaducidad'],
        'topCaducidad' => $dashboardViewModel['topCaducidad'],
        'topCaducidadArticulos' => (int)$dashboardViewModel['topCaducidadArticulos'],
        'topCaducidadUnidadesDia' => (int)$dashboardViewModel['topCaducidadUnidadesDia'],
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error real: ' . $e->getMessage() . ' en la linea ' . $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

exit;
?>