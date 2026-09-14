<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $almacenid = isset($_GET['almacenid']) ? intval($_GET['almacenid']) : 0;
    $clienteid = (isset($_GET['clienteid']) && $_GET['clienteid'] !== '') ? intval($_GET['clienteid']) : null;

    if (!$almacenid) {
        echo json_encode([]);
        exit;
    }

    $clienteCond  = $clienteid !== null ? (int)$clienteid : -999;
    $db = new FirebirdConnection();

    $eventoCond = "";
    $eventoid = isset($_GET['eventoid']) ? intval($_GET['eventoid']) : 0;
    if ($eventoid > 0) {
        $eventoCond = " AND EC.EQUIPOCAPITAL_ID NOT IN (
            SELECT -EVENTOMALETA_MALETAID 
            FROM AMPAR_HIS_EVENTOSMALETAS 
            WHERE EVENTOMALETA_EVENTOID = {$eventoid} AND EVENTOMALETA_MALETAID < 0
        ) ";
    }

    // Filtro global: excluir equipos que ya están asignados a cualquier otro evento activo
    $currentEventId = isset($_GET['current_eventoid']) ? (int)$_GET['current_eventoid'] : 0;
    $fechai = isset($_GET['fechai']) ? trim($_GET['fechai']) : '';
    $fechaf = isset($_GET['fechaf']) ? trim($_GET['fechaf']) : '';

    $inUseCond = " AND EC.EQUIPOCAPITAL_ID NOT IN (
        SELECT -EVENTOMALETA_MALETAID 
        FROM AMPAR_HIS_EVENTOSMALETAS 
        INNER JOIN AMPAR_HIS_EVENTOS ON EVENTO_ID = EVENTOMALETA_EVENTOID
        WHERE EVENTOMALETA_MALETAID < 0 
          AND EVENTO_STATUSGENERAL NOT IN (3, 5) ";

    if ($currentEventId > 0) {
        $inUseCond .= " AND EVENTO_ID != {$currentEventId} ";
    }

    if ($fechai !== '' && $fechaf !== '') {
        // Formatear datetime-local (2026-09-11T09:24) a (2026-09-11 09:24) para Firebird
        $fechai_fb = str_replace('T', ' ', $fechai);
        $fechaf_fb = str_replace('T', ' ', $fechaf);
        // Validación de empalme de fechas
        $inUseCond .= " AND (EVENTO_FECHAI <= '{$fechaf_fb}' AND EVENTO_FECHAF >= '{$fechai_fb}') ";
    }

    $inUseCond .= " ) ";

    $inUseCond .= " AND EC.EQUIPOCAPITAL_ID NOT IN (
        SELECT -PROYECTOMALETA_MALETAID
        FROM AMPAR_HIS_PROYECTOSMALETAS
        INNER JOIN AMPAR_HIS_PROYECTOS ON PROYECTO_ID = PROYECTOMALETA_PROYECTOID
        WHERE PROYECTOMALETA_MALETAID < 0
          AND PROYECTO_STATUSGENERAL NOT IN (3, 5)
    ) ";

    $tipoEventoCond = "";
    $tipoeventoid = isset($_GET['tipoeventoid']) ? intval($_GET['tipoeventoid']) : 0;
    if ($tipoeventoid > 0) {
        $tipoEventoCond = " AND EXISTS (SELECT 1 FROM AMPAR_REL_EQUIPO_TIPOEVENTO RE WHERE RE.EQUIPOCAPITAL_ID = EC.EQUIPOCAPITAL_ID AND RE.TIPOEVENTO_ID = {$tipoeventoid}) ";
    }

    $sql = "
        SELECT
            EC.EQUIPOCAPITAL_ID AS ID,
            EC.FOLIO,
            EC.REFERENCIA,
            EC.MARCA,
            EC.UBICACION,
            AR.ARTICULO_ID,
            AR.NOMBRE AS ARTICULO_NOMBRE,
            X.CLAVE_ARTICULO,
            EC.FOLIO || ' - ' || AR.NOMBRE || ' (' || COALESCE(EC.UBICACION, 'SIN UBICACION') || ')' AS NOMBRE_DISPLAY,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS SUBTOTAL,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS IVA,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteCond}),
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS TOTAL
        FROM AMPAR_EQUIPOCAPITAL EC
        INNER JOIN ARTICULOS AR ON AR.ARTICULO_ID = EC.ARTICULO_ID
        LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X
               ON X.ARTICULO_ID = AR.ARTICULO_ID
        WHERE EC.ESTATUS = 'A'
          AND EC.ALMACEN_ID = {$almacenid}
          {$eventoCond}
          {$inUseCond}
          {$tipoEventoCond}
        ORDER BY AR.NOMBRE ASC, EC.FOLIO ASC
    ";

    $result = $db->query($sql);
    $db->close();

    echo json_encode(is_array($result) ? $result : []);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
