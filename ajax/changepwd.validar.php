<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para validar codigo para cambiar pwd
*********************************************************************************
*/
?>
<?php
    $login = new login();
    echo $login->validarchangepwd($_POST["correo"],$_POST["codigo"]);
?>