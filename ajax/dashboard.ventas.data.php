<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $periodo = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 90;
    $almacenId = isset($_GET['almacen_id']) ? $_GET['almacen_id'] : 'global';
    
    $db = new FirebirdConnection();
    
    $filtroAlmacen = "";
    if ($almacenId !== 'global') {
        $almId = (int)$almacenId;
        // La remisión puede estar asociada a un evento (que tiene sucursal/almacen) o ser directa
        $filtroAlmacen = " AND (RE.REMISION_ALMACENID = {$almId} OR E.EVENTO_SUCURSALID IN (SELECT SUCURSAL_ID FROM AMPAR_CAT_SUCURSALES WHERE SUCURSAL_ALMACENID = {$almId})) ";
    }
    
    // 1. KPIs Generales
    $sqlKpis = "
        SELECT 
            COUNT(DISTINCT RE.REMISION_ID) AS TOTAL_REMISIONES,
            COALESCE(SUM(RA.REMISIONARTICULO_TOTAL), 0) AS TOTAL_INGRESOS
        FROM AMPAR_HIS_REMISIONES RE
        LEFT JOIN AMPAR_HIS_REMISIONESARTICULOS RA ON RA.REMISIONARTICULO_REMISIONID = RE.REMISION_ID
        LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = RE.REMISION_EVENTOID
        WHERE RE.REMISION_FECHA >= DATEADD(-{$periodo} DAY TO CURRENT_DATE)
          AND RE.REMISION_STATUS IN (1, 3) /* 1 Activa, 3 Finalizada */
          {$filtroAlmacen}
    ";
    $resKpis = $db->query($sqlKpis);
    
    $totalRemisiones = 0;
    $totalIngresos = 0;
    
    if ($resKpis && is_array($resKpis)) {
        $totalRemisiones = (int)$resKpis[0]['TOTAL_REMISIONES'];
        $totalIngresos = (float)$resKpis[0]['TOTAL_INGRESOS'];
    }
    
    $ticketPromedio = $totalRemisiones > 0 ? ($totalIngresos / $totalRemisiones) : 0;
    
    // 2. Tendencia (Últimos 6 meses)
    $tendenciaLabels = [];
    $tendenciaDatos = [];
    
    $sqlTendencia = "
        SELECT 
            EXTRACT(YEAR FROM RE.REMISION_FECHA) AS ANIO,
            EXTRACT(MONTH FROM RE.REMISION_FECHA) AS MES,
            COALESCE(SUM(RA.REMISIONARTICULO_TOTAL), 0) AS TOTAL_MES
        FROM AMPAR_HIS_REMISIONES RE
        JOIN AMPAR_HIS_REMISIONESARTICULOS RA ON RA.REMISIONARTICULO_REMISIONID = RE.REMISION_ID
        LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = RE.REMISION_EVENTOID
        WHERE RE.REMISION_FECHA >= DATEADD(-6 MONTH TO CURRENT_DATE)
          AND RE.REMISION_STATUS IN (1, 3)
          {$filtroAlmacen}
        GROUP BY EXTRACT(YEAR FROM RE.REMISION_FECHA), EXTRACT(MONTH FROM RE.REMISION_FECHA)
        ORDER BY ANIO, MES
    ";
    $resTendencia = $db->query($sqlTendencia);
    
    $mesesNombres = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
    
    // Inicializar los últimos 6 meses en 0 para mantener la gráfica completa aunque no haya datos
    $mesActual = (int)date('n');
    $anioActual = (int)date('Y');
    
    for ($i = 5; $i >= 0; $i--) {
        $m = $mesActual - $i;
        $a = $anioActual;
        if ($m <= 0) {
            $m += 12;
            $a--;
        }
        $key = $a . "_" . $m;
        $tendenciaLabels[$key] = $mesesNombres[$m - 1] . " " . substr($a, -2);
        $tendenciaDatos[$key] = 0;
    }
    
    if ($resTendencia && is_array($resTendencia)) {
        foreach ($resTendencia as $row) {
            $key = $row['ANIO'] . "_" . $row['MES'];
            if (isset($tendenciaDatos[$key])) {
                $tendenciaDatos[$key] = (float)$row['TOTAL_MES'];
            }
        }
    }
    
    // 3. Top 5 Artículos
    $sqlTop = "
        SELECT FIRST 5
            A.NOMBRE AS ARTICULO,
            COALESCE(SUM(RA.REMISIONARTICULO_TOTAL), 0) AS TOTAL_INGRESO
        FROM AMPAR_HIS_REMISIONES RE
        JOIN AMPAR_HIS_REMISIONESARTICULOS RA ON RA.REMISIONARTICULO_REMISIONID = RE.REMISION_ID
        JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = RA.REMISIONARTICULO_STOCKID
        JOIN ARTICULOS A ON A.ARTICULO_ID = S.STOCK_ARTICULOID
        LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = RE.REMISION_EVENTOID
        WHERE RE.REMISION_FECHA >= DATEADD(-{$periodo} DAY TO CURRENT_DATE)
          AND RE.REMISION_STATUS IN (1, 3)
          {$filtroAlmacen}
        GROUP BY A.NOMBRE
        ORDER BY TOTAL_INGRESO DESC
    ";
    $resTop = $db->query($sqlTop);
    
    $topArticulos = [];
    if ($resTop && is_array($resTop)) {
        foreach ($resTop as $row) {
            $topArticulos[] = [
                'nombre' => $row['ARTICULO'],
                'ingreso' => (float)$row['TOTAL_INGRESO']
            ];
        }
    }
    
    $db->close();
    
    echo json_encode([
        'status' => 'success',
        'kpis' => [
            'totalIngresos' => $totalIngresos,
            'totalRemisiones' => $totalRemisiones,
            'ticketPromedio' => $ticketPromedio
        ],
        'chart' => [
            'labels' => array_values($tendenciaLabels),
            'data' => array_values($tendenciaDatos)
        ],
        'topArticulos' => $topArticulos
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
