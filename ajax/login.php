<?php
session_start();
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para autenticar login
*********************************************************************************
*/
?>
<?php include_once("../includes/includes.php"); ?>
<?php
$nom = new login();
$nom->autenticar($_POST["ulusuario"], $_POST["ulpwd"]);
?>