<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para eliminar una división
*********************************************************************************
*/
$maletas = new maletas();
$maletas->eliminardivision($_POST['divisionid']);
?>
