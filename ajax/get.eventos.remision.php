<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Ajax para obtener los eventos filtrados por Almacén para Nueva Remisión
*********************************************************************************
*/
$almacenid = isset($_GET['almacenid']) ? intval($_GET['almacenid']) : 0;
$db = new FirebirdConnection();

// Traemos los eventos del almacén y les ponemos los nombres (ALIAS) que el Javascript espera
$sql = "
    SELECT 
        E.EVENTO_ID AS ID, 
        E.EVENTO_FOLIO AS FOLIO, 
        CAST(E.EVENTO_CONCEPTO AS VARCHAR(1000)) AS NOMBRE, 
        S.STATUS_NOMBRE, 
        E.EVENTO_CLIENTEID AS CLIENTE_ID,
        C.NOMBRE AS CLIENTE
    FROM AMPAR_HIS_EVENTOS E
    LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = E.EVENTO_STATUSGENERAL
    LEFT JOIN CLIENTES C ON C.CLIENTE_ID = E.EVENTO_CLIENTEID
    WHERE E.EVENTO_ALMACENID = {$almacenid}
      AND E.EVENTO_STATUSGENERAL IN (15, 16) 
    ORDER BY E.EVENTO_ID DESC
";

$res = $db->query($sql);

if ($res <> 0 && is_array($res)){
    echo json_encode($res);
} else {
    // Si no hay eventos, devolvemos un array vacío limpio
    echo json_encode(array());
}
$db->close();
?>