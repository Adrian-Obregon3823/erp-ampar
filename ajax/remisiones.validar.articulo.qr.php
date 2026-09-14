<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar remision
*********************************************************************************
*/
?>
<?php
$remisiones = new remisiones();
$stockid = isset($_GET['stockid']) ? intval($_GET['stockid']) : 0;

header('Content-Type: application/json; charset=utf-8');
echo $remisiones->validarqrcarrito($stockid);
?>