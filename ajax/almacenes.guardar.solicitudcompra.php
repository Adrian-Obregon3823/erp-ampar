<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para GUARDAR SOLICITUD COMPRA
*********************************************************************************
*/
?>
<?php
    $compras = new compras();
    $compras->guardar($_POST['sucursalid'],'prueba',$_POST['articulos']);
?>