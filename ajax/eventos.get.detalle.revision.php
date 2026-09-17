<?php
include_once(__DIR__ . "/../includes/sesion.php");
include_once(__DIR__ . "/../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['eventoid']) || empty($_POST['eventoid'])) {
    echo json_encode(['error' => 'ID de evento no especificado']);
    exit;
}

$eventoid = (int)$_POST['eventoid'];
$db = new FirebirdConnection();

// 1. Cabecera del Evento
$sqlCab = "
    SELECT FIRST 1
        E.EVENTO_ID, E.EVENTO_FOLIO, E.EVENTO_ARCHIVO_REMISION, E.EVENTO_STATUSGENERAL, E.EVENTO_FECHAI, E.EVENTO_FECHAF,
        SU.NOMBRE AS SUCURSAL_NOMBRE,
        ALMEV.ALMACEN_NOMBRE AS EVENTO_ALMACEN_NOMBRE,
        TE.TIPOEVENTO_NOMBRE,
        C.NOMBRE AS CLIENTE_NOMBRE,
        H.HOSPITAL_NOMBRE
    FROM AMPAR_HIS_EVENTOS E
    LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = E.EVENTO_SUCURSALID
    LEFT JOIN AMPAR_HIS_ALMACEN ALMEV ON ALMEV.ALMACEN_ID = E.EVENTO_ALMACENID
    LEFT JOIN AMPAR_CAT_TIPOEVENTO TE ON TE.TIPOEVENTO_ID = E.EVENTO_TIPOEVENTO
    LEFT JOIN CLIENTES C ON C.CLIENTE_ID = E.EVENTO_CLIENTEID
    LEFT JOIN AMPAR_CAT_HOSPITALES H ON H.HOSPITAL_ID = E.EVENTO_HOSPITALID
    WHERE E.EVENTO_ID = {$eventoid}
";
$cab = $db->query($sqlCab);
if (!$cab || !isset($cab[0])) {
    echo json_encode(['error' => 'Evento no encontrado']);
    $db->close();
    exit;
}
$cabecera = $cab[0];

// 1.5. Remisiones y archivos (AMPAR_HIS_REMISIONES)
try { $db->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $t) {}
$sqlRemisiones = "
    SELECT R.REMISION_ID, R.REMISION_FOLIO, R.REMISION_FECHA, R.REMISION_ARCHIVO
    FROM AMPAR_HIS_REMISIONES R
    WHERE R.REMISION_EVENTOID = {$eventoid}
    ORDER BY R.REMISION_ID ASC
";
$remisionesLista = $db->query($sqlRemisiones);
if (!$remisionesLista || !is_array($remisionesLista)) {
    $remisionesLista = [];
}

// 2. Evidencias de recepción (App móvil)
$sqlEvid = "
    SELECT EVIDENCIA_ID, RUTA_FOTO, TIPO_OPERACION, SELLO_NUMERO, FECHA_REGISTRO
    FROM AMPAR_ENTREGAEVENTO
    WHERE EVENTO_ID = {$eventoid}
    ORDER BY FECHA_REGISTRO DESC
";
$evidencias = $db->query($sqlEvid);
if (!$evidencias || !is_array($evidencias)) {
    $evidencias = [];
}

// 3. Maletas / Equipos asignados (AMPAR_HIS_EVENTOSMALETAS)
$sqlMaletas = "
    SELECT EM.EVENTOMALETA_ID, EM.EVENTOMALETA_MALETAID AS ALMACEN_ID,
        COALESCE(A.ALMACEN_FOLIO, EC.FOLIO) AS FOLIO,
        COALESCE(A.ALMACEN_NOMBRE, ART_EQ.NOMBRE || ' (EQUIPO CAPITAL)') AS NOMBRE,
        'MALETA_EQUIPO' AS TIPO_ITEM,
        CASE WHEN EM.EVENTOMALETA_MALETAID < 0 THEN 1 ELSE 0 END AS ES_EQUIPO_CAPITAL
    FROM AMPAR_HIS_EVENTOSMALETAS EM
    LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID > 0
    LEFT JOIN AMPAR_EQUIPOCAPITAL EC ON EC.EQUIPOCAPITAL_ID = -EM.EVENTOMALETA_MALETAID AND EM.EVENTOMALETA_MALETAID < 0
    LEFT JOIN ARTICULOS ART_EQ ON ART_EQ.ARTICULO_ID = EC.ARTICULO_ID
    WHERE EM.EVENTOMALETA_EVENTOID = {$eventoid}
";
$maletas = $db->query($sqlMaletas);
if (!$maletas || !is_array($maletas)) {
    $maletas = [];
}

// 4. Artículos consumidos / asignados de TODAS las remisiones válidas del evento
$sqlRem = "SELECT REMISION_ID FROM AMPAR_HIS_REMISIONES WHERE REMISION_EVENTOID = {$eventoid} AND REMISION_STATUS IN (1,3)";
$resRem = $db->query($sqlRem);
$articulos = [];

if ($resRem && is_array($resRem) && count($resRem) > 0) {
    $remisionIds = [];
    foreach ($resRem as $r) {
        $remisionIds[] = (int)$r['REMISION_ID'];
    }
    $remisionIdsStr = implode(',', $remisionIds);

    $sqlArt = "
        SELECT RA.REMISIONARTICULO_ID AS ID,
               COALESCE(S.STOCK_FOLIO, CA.CLAVE_ARTICULO, EC.FOLIO) AS FOLIO,
               COALESCE(A.NOMBRE, ART_EC.NOMBRE || ' (Equipo Capital)', 'Artículo sin descripción') AS NOMBRE,
               S.STOCK_LOTE AS LOTE,
               S.STOCK_CADUCIDAD AS CADUCIDAD,
               COALESCE(S.STOCK_SERIE, EC.REFERENCIA) AS SERIE,
               'ARTICULO_REMISION' AS TIPO_ITEM,
               CASE WHEN RA.REMISIONARTICULO_EQCAPITALID IS NOT NULL AND RA.REMISIONARTICULO_EQCAPITALID > 0 THEN 1 ELSE 0 END AS ES_EQUIPO_CAPITAL
        FROM AMPAR_HIS_REMISIONESARTICULOS RA
        LEFT JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = RA.REMISIONARTICULO_STOCKID
        LEFT JOIN ARTICULOS A ON A.ARTICULO_ID = S.STOCK_ARTICULOID
        LEFT JOIN claves_articulos CA ON CA.ARTICULO_ID = A.ARTICULO_ID AND CA.ROL_CLAVE_ART_ID = 17
        LEFT JOIN AMPAR_EQUIPOCAPITAL EC ON EC.EQUIPOCAPITAL_ID = RA.REMISIONARTICULO_EQCAPITALID
        LEFT JOIN ARTICULOS ART_EC ON ART_EC.ARTICULO_ID = EC.ARTICULO_ID
        WHERE RA.REMISIONARTICULO_REMISIONID IN ({$remisionIdsStr})
    ";
    $articulos = $db->query($sqlArt);
    if (!$articulos || !is_array($articulos)) {
        $articulos = [];
    }

    $sqlProv = "
        SELECT RPA.REMISIONPROVARTICULO_ID AS ID,
               COALESCE(CA.CLAVE_ARTICULO, '') AS FOLIO,
               COALESCE(A.NOMBRE, 'Artículo sin descripción') || ' (' || COALESCE(P.NOMBRE, 'Proveedor externo') || ')' AS NOMBRE,
               '' AS LOTE,
               '' AS CADUCIDAD,
               '' AS SERIE,
               'ARTICULO_REMISION' AS TIPO_ITEM
        FROM AMPAR_HIS_REMISIONESPARTICULOS RPA
        LEFT JOIN ARTICULOS A ON A.ARTICULO_ID = RPA.REMISIONPROVARTICULO_ARTICULOID
        LEFT JOIN PROVEEDORES P ON P.PROVEEDOR_ID = RPA.REMISIONPROVARTICULO_PROVID
        LEFT JOIN claves_articulos CA ON CA.ARTICULO_ID = A.ARTICULO_ID AND CA.ROL_CLAVE_ART_ID = 17
        WHERE RPA.REMISIONPROVARTICULO_REMISIONID IN ({$remisionIdsStr})
    ";
    $articulosProv = $db->query($sqlProv);
    if ($articulosProv && is_array($articulosProv)) {
        $articulos = array_merge($articulos, $articulosProv);
    }
} else {
    $eventoObj = new eventos();
    $artsEvento = $eventoObj->getarticulosbyevento($eventoid);
    if ($artsEvento && is_array($artsEvento)) {
        foreach ($artsEvento as $a) {
            $articulos[] = [
                'ID' => $a['ID'],
                'FOLIO' => $a['FOLIO'] ?? $a['CLAVE_ARTICULO'] ?? '',
                'NOMBRE' => $a['ARTICULO_NOMBRE'] ?? '',
                'LOTE' => '',
                'CADUCIDAD' => '',
                'SERIE' => '',
                'TIPO_ITEM' => 'ARTICULO_EVENTO'
            ];
        }
    }
}

$db->close();

echo json_encode([
    'cabecera' => $cabecera,
    'remisiones_lista' => $remisionesLista,
    'evidencias' => $evidencias,
    'maletas' => $maletas,
    'articulos' => $articulos
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
