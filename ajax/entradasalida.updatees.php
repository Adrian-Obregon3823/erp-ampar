<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para editar entrada de inventarios
*********************************************************************************
*/
?>
<?php
    $entradasalida = new entradasalida();
    $entradasalida->actualizarEntradasalida($_POST['invsucursalid'],$_POST['invalmacenid'],$_POST['esid'],$_POST['invtipo'],$_POST['invconcepto'],$_POST['invmotivo'],$_POST['idproveedor'],$_POST['correoproveedor'],$_POST['cambios']);
?>