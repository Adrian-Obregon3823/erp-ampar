<?php include_once("../includes/sesion.php");?>
<?php require '../vendor/autoload.php'; ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar un tipo de maleta
*********************************************************************************
*/
?>
<?php
    $eventos = new eventos();
    $eventos->editarevento($_POST['eventoid'],$_POST['cambios_json']); 
?>