<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para traer la informacion de un usuario
*********************************************************************************
*/
?>
<?php
    $login = new login();
    $usuarioget = $login->getusuariobyid($_GET['id']);
    echo json_encode($usuarioget);
?>