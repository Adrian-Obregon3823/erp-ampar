<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para mandar el detalle de una solicitud de traspaso
*********************************************************************************
*/
?>
<?php   
$traspasoid = $_POST['traspasoid'];
$traspasos = new traspasos();
$res = $traspasos->getinfotraspasobyid($traspasoid);
echo json_encode($res);
?>