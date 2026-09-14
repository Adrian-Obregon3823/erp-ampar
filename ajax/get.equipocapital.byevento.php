<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $eventoid = isset($_GET['eventoid']) ? intval($_GET['eventoid']) : 0;

    if (!$eventoid) {
        echo json_encode([]);
        exit;
    }

    $db = new FirebirdConnection();

    // Obtener equipos capital asignados al evento (IDs negativos en AMPAR_HIS_EVENTOSMALETAS)
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
            EC.FOLIO || ' - ' || AR.NOMBRE AS NOMBRE_DISPLAY,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID = E.EVENTO_CLIENTEID),
                (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS SUBTOTAL,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID = E.EVENTO_CLIENTEID),
                (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS IVA,
            COALESCE(
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID = E.EVENTO_CLIENTEID),
                (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP
                 WHERE AP.ARTPRECIO_ARTICULOID = AR.ARTICULO_ID
                   AND AP.ARTPRECIO_CLIENTEID IS NULL),
                0
            ) AS TOTAL
        FROM AMPAR_HIS_EVENTOSMALETAS EM
        INNER JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = EM.EVENTOMALETA_EVENTOID
        INNER JOIN AMPAR_EQUIPOCAPITAL EC ON EC.EQUIPOCAPITAL_ID = ABS(EM.EVENTOMALETA_MALETAID)
        INNER JOIN ARTICULOS AR ON AR.ARTICULO_ID = EC.ARTICULO_ID
        LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X
               ON X.ARTICULO_ID = AR.ARTICULO_ID
        WHERE EM.EVENTOMALETA_EVENTOID = {$eventoid}
          AND EM.EVENTOMALETA_MALETAID < 0
          AND EC.ESTATUS = 'A'
        ORDER BY AR.NOMBRE ASC, EC.FOLIO ASC
    ";

    $result = $db->query($sql);
    $db->close();

    echo json_encode(is_array($result) ? $result : []);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
