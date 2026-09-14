<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para eliminar documento de soporte en garantias de proveedor
*********************************************************************************
*/
?>
<?php
$es = new entradasalida();
$es->eliminardsgarantiaprov($_POST['id']);
?>