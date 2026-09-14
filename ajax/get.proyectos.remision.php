<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Ajax para obtener los proyectos filtrados por el almacén para Nueva Remisión
*********************************************************************************
*/
$almacenid = isset($_GET['almacenid']) ? intval($_GET['almacenid']) : 0;
$db = new FirebirdConnection();

// Traemos los proyectos de la sucursal del almacén con los alias que JS espera
$sql = "
    SELECT 
        P.PROYECTO_ID AS ID, 
        P.PROYECTO_FOLIO AS FOLIO, 
        CAST(P.PROYECTO_CONCEPTO AS VARCHAR(1000)) AS NOMBRE, 
        S.STATUS_NOMBRE, 
        P.PROYECTO_CLIENTEID AS CLIENTE_ID,
        C.NOMBRE AS CLIENTE
    FROM AMPAR_HIS_PROYECTOS P
    LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = P.PROYECTO_STATUSGENERAL
    LEFT JOIN CLIENTES C ON C.CLIENTE_ID = P.PROYECTO_CLIENTEID
    WHERE P.PROYECTO_SUCURSALID = (SELECT ALMACEN_SUCURSAL_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = {$almacenid})
      AND P.PROYECTO_STATUSGENERAL IN (14, 15)
    ORDER BY P.PROYECTO_ID DESC
";

$res = $db->query($sql);

if ($res <> 0 && is_array($res)){
    echo json_encode($res);
} else {
    echo json_encode(array());
}
$db->close();
?>
