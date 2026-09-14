<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener linea de artículos
*********************************************************************************
*/
$articulos = new articulos();
$grupo_linea_id = isset($_GET['grupo_linea_id']) ? intval($_GET['grupo_linea_id']) : null;
$division_id = isset($_GET['division_id']) ? intval($_GET['division_id']) : null;
$search = isset($_GET['q']) ? $_GET['q'] : null;
$res = $articulos->getcatalogolineaarticulos($grupo_linea_id, $search, $division_id);
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['id'] = '';
  $array[0]['nombre'] = '';
  echo json_encode($array);
}
?>