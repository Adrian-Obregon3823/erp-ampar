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
$stockid = isset($_POST['stockid']) ? $_POST['stockid'] : 0;
$eventoid = isset($_POST['eventoid']) ? $_POST['eventoid'] : 0;
$remisiones = new remisiones();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($remisiones->iniciarevento(
    $eventoid,
    $stockid
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>