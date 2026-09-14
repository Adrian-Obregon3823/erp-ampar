<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para modificar en bd el pwd
*********************************************************************************
*/
?>
<?php
    $login = new login();
    echo $login->updatepwd($_POST['correo'],$_POST['pwdcp1']);
?>