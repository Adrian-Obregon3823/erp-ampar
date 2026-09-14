<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para consultar si existe correo de usuario
*********************************************************************************
*/
?>
<?php
    $login = new login();
    $login->validaexistecorreousuario($_GET['correo']);
?>