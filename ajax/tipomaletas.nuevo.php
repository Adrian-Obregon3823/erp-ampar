<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar un tipo de maleta
*********************************************************************************
*/
?>
<?php
//if (is_array($_POST['tmidarticulo'])){
$maletas = new maletas();
$maletas->nuevotipomaleta($_POST['tmdivision'], $_POST['tmnombre'], $_POST['tmdescripcion'], (isset($_POST['tmidarticuloarray']) ? $_POST['tmidarticuloarray'] : array()), (isset($_POST['tmcantidadarray']) ? $_POST['tmcantidadarray'] : array()));
//}else{
//echo "Debes seleccionar al menos un artículo";
//}
?>