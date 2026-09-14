<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para editar un tipo de maleta
*********************************************************************************
*/
?>
<?php
$maletas = new maletas();
$maletas->edittipomaleta($_POST['tmeditidtipomaleta'], $_POST['tmeditdivision'], $_POST['tmeditnombre'], $_POST['tmeditdescripcion'], (isset($_POST['tmeditidarticuloarray']) ? $_POST['tmeditidarticuloarray'] : ""), (isset($_POST['tmeditcantidadarray']) ? $_POST['tmeditcantidadarray'] : ""), (isset($_POST['tmeditiddetallearrayexistente']) ? $_POST['tmeditiddetallearrayexistente'] : ""), (isset($_POST['tmeditcantidadarrayexistente']) ? $_POST['tmeditcantidadarrayexistente'] : ""));
?>