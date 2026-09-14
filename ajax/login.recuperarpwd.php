<?php require '../vendor/autoload.php'; ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar código a correo y recuperar contraseña
*********************************************************************************
*/
?>
<?php
    $login = new login();
    $login->recuperarpwd($_POST['correo']);
?>