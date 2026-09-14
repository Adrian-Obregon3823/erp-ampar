<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$almacenid = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;
$clienteid = isset($_GET['clienteid']) && $_GET['clienteid'] !== '' ? (int)$_GET['clienteid'] : -999;
$tipo_proyecto = isset($_GET['proyecto_tipo']) ? (int)$_GET['proyecto_tipo'] : 1; // 1: Consigna, 2: Renta, 3: Comodato
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$res = [];

try {
    $db = new FirebirdConnection(true);
    $like = '%' . $q . '%';

    // 1. Fetch Standard Articles (if Consignacion [1] or Comodato [3])
    if ($tipo_proyecto === 1 || $tipo_proyecto === 3) {
        $sqlArt = "
            SELECT
                ar.ARTICULO_ID AS ID,
                ar.ARTICULO_ID,
                ar.NOMBRE AS ARTICULO_NOMBRE,
                x.CLAVE_ARTICULO,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS TOTAL
            FROM ARTICULOS ar
            LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) x ON x.ARTICULO_ID = ar.ARTICULO_ID
            WHERE ar.ESTATUS = 'A'
              AND (UPPER(ar.NOMBRE) LIKE UPPER(?) OR UPPER(x.CLAVE_ARTICULO) LIKE UPPER(?))
            ORDER BY ar.NOMBRE
            ROWS 30
        ";
        $rowsArt = $db->query($sqlArt, [$like, $like]) ?: [];
        foreach ($rowsArt as $r) {
            $res[] = [
                'ID' => (int)$r['ID'], // Positive ARTICULO_ID
                'FOLIO' => '',
                'ARTICULO_ID' => (int)$r['ARTICULO_ID'],
                'ARTICULO_NOMBRE' => (string)$r['ARTICULO_NOMBRE'],
                'CLAVE_ARTICULO' => isset($r['CLAVE_ARTICULO']) ? (string)$r['CLAVE_ARTICULO'] : '',
                'SERIE' => '',
                'LOTE' => '',
                'CADUCIDAD' => '',
                'TIPO' => 'ARTICULO',
                'SUBTOTAL' => (float)$r['SUBTOTAL'],
                'IVA' => (float)$r['IVA'],
                'TOTAL' => (float)$r['TOTAL']
            ];
        }
    }

    // 2. Fetch Capital Equipment (if Renta [2] or Comodato [3])
    if ($tipo_proyecto === 2 || $tipo_proyecto === 3) {
        $sqlEq = "
            SELECT
                ec.EQUIPOCAPITAL_ID AS ID,
                ec.FOLIO AS FOLIO,
                ar.ARTICULO_ID,
                ar.NOMBRE AS ARTICULO_NOMBRE,
                x.CLAVE_ARTICULO,
                ec.UBICACION,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_SUBTOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS SUBTOTAL,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_IVA FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS IVA,
                COALESCE(
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID = {$clienteid}),
                    (SELECT FIRST 1 AP.ARTPRECIO_TOTAL FROM AMPAR_CAT_ARTPRECIO AP WHERE AP.ARTPRECIO_ARTICULOID = ar.ARTICULO_ID AND AP.ARTPRECIO_CLIENTEID IS NULL),
                    0
                ) AS TOTAL
            FROM AMPAR_EQUIPOCAPITAL ec
            INNER JOIN ARTICULOS ar ON ar.ARTICULO_ID = ec.ARTICULO_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) x ON x.ARTICULO_ID = ar.ARTICULO_ID
            WHERE ec.ESTATUS = 'A' AND ec.OCUPADO = 'N'
              AND (UPPER(ar.NOMBRE) LIKE UPPER(?) OR UPPER(ec.FOLIO) LIKE UPPER(?) OR UPPER(x.CLAVE_ARTICULO) LIKE UPPER(?))
            ORDER BY ar.NOMBRE
            ROWS 30
        ";
        $rowsEq = $db->query($sqlEq, [$like, $like, $like]) ?: [];
        foreach ($rowsEq as $r) {
            $res[] = [
                'ID' => -(int)$r['ID'], // Negative for Capital Equipment
                'FOLIO' => (string)$r['FOLIO'],
                'ARTICULO_ID' => (int)$r['ARTICULO_ID'],
                'ARTICULO_NOMBRE' => (string)$r['ARTICULO_NOMBRE'],
                'CLAVE_ARTICULO' => isset($r['CLAVE_ARTICULO']) ? (string)$r['CLAVE_ARTICULO'] : '',
                'SERIE' => '',
                'LOTE' => '',
                'CADUCIDAD' => '',
                'TIPO' => 'EQUIPO',
                'SUBTOTAL' => (float)$r['SUBTOTAL'],
                'IVA' => (float)$r['IVA'],
                'TOTAL' => (float)$r['TOTAL']
            ];
        }
    }

    $db->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode($res);
