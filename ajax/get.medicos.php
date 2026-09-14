<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN 
?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Médicos por Hospital + Subgrupo (+ Grupo opcional)
*********************************************************************************
*/
$almacenid = isset($_GET['almacenid']) ? (int)$_GET['almacenid'] : 0;
$subgrupoid = isset($_GET['subgrupoid']) ? (int)$_GET['subgrupoid'] : 0;
$grupoid    = isset($_GET['grupoid']) ? (int)$_GET['grupoid'] : 0;

$eventos = new eventos();
$res = $eventos->getcatalogomedicos($almacenid, $subgrupoid, $grupoid);

if ($res <> 0) {
  echo json_encode($res);
} else {
  $array = [];
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>