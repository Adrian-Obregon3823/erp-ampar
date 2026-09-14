<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

// Entradas en status 8 (En Revisión, pendientes de autorizar)
$db = new FirebirdConnection();
$sql = "
    SELECT 
        ES.ES_ID, ES.ES_FOLIO, ES.ES_ALMACENID, ES.ES_TIPO, ES.ES_FECHA,
        A.ALMACEN_NOMBRE,
        SU.NOMBRE SUCURSAL_NOMBRE,
        (SELECT COUNT(*) FROM AMPAR_HIS_ESDET ED WHERE ED.ESDET_ESID = ES.ES_ID) CANTIDAD
    FROM AMPAR_HIS_ES ES
    LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = ES.ES_ALMACENID
    LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = A.ALMACEN_SUCURSAL_MS
    WHERE ES.ES_STATUS = 8
    AND ES.ES_TIPO = 'E'
    ORDER BY ES.ES_ID DESC
";
$res = $db->query($sql);
$db->close();

echo json_encode($res ?: []);
?>
