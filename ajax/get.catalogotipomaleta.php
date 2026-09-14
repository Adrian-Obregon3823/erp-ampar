<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN 
?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Tipo Maleta (filtrado por división si se pasa divisionid)
*********************************************************************************
*/
header('Content-Type: application/json; charset=utf-8');
$alm = new maletas();
$divisionid = isset($_GET['divisionid']) && $_GET['divisionid'] != '' && $_GET['divisionid'] != '0'
  ? intval($_GET['divisionid'])
  : '';
$res = $alm->getcatalogotipomaletas($divisionid);
if ($res <> 0) {
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
} else {
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array, JSON_UNESCAPED_UNICODE);
}
?>