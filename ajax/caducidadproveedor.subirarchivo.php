<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para subir documentos de soporte en garantias de proveedor
*********************************************************************************
*/
?>
<?php
$es = new entradasalida();
$es->uploaddsgarantiaprov($_POST['cpid'],$_FILES);
?>