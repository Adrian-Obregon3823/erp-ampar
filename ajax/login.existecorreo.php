<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para validar si existe correo registrado para el alta de un consultor
*********************************************************************************
*/
$nom = new login();
$res = $nom->validaexistecorreoconsultor($_GET['correo']);
echo $res;
?>