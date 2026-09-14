<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

// Traspasos en status 8 (En Revisión, pendientes de autorizar)
$db = new FirebirdConnection();
$sql = "
    SELECT 
        T.TRASPASO_ID, T.TRASPASO_FOLIO, T.TRASPASO_MOTIVO,
        A1.ALMACEN_NOMBRE ALMACEN_DE,
        A2.ALMACEN_NOMBRE ALMACEN_A,
        S1.NOMBRE SUCURSAL_DE,
        S2.NOMBRE SUCURSAL_A,
        (SELECT COUNT(*) FROM AMPAR_HIS_TRASPASODET TD WHERE TD.TRASPASODET_TRASPASOID = T.TRASPASO_ID) CANTIDAD
    FROM AMPAR_HIS_TRASPASO T
    LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = T.TRASPASO_DEALMACENID
    LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = T.TRASPASO_AALMACENID
    LEFT JOIN ampar_cat_sucursales S1 ON S1.SUCURSAL_ID = A1.ALMACEN_SUCURSAL_MS
    LEFT JOIN ampar_cat_sucursales S2 ON S2.SUCURSAL_ID = A2.ALMACEN_SUCURSAL_MS
    WHERE T.TRASPASO_STATUS = 8
    ORDER BY T.TRASPASO_ID DESC
";
$res = $db->query($sql);
$db->close();

echo json_encode($res ?: []);
?>
