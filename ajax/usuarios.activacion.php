<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para activar o desactivar un usuario
*********************************************************************************
*/
?>
<?php
    $login = new login();
    $login->updateactivo($_POST['id'],$_POST['status']);
?>