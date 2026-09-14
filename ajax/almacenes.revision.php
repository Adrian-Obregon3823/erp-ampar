<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener revisión de almacenes
*********************************************************************************
*/

$sucursal_id = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 0;

$almacen = new almacenes();
$res = $almacen->revisionalmacenesfaltantes($sucursal_id);

if (!empty($res) && is_array($res)) {
    echo json_encode($res);
} else {
    echo json_encode([]); // mejor mandar un array vacío en vez de campos vacíos
}
?>