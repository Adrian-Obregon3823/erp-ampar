<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener catalogo de articulos
*********************************************************************************
*/
header('Content-Type: application/json; charset=utf-8');
$almacen = new articulos();
$res = $almacen->getcatalogoarticulos();
if ($res<>0){
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  $array[0]['SEGUIMIENTO'] = '';
  $array[0]['CLAVE_ARTICULO'] = '';
  echo json_encode($array, JSON_UNESCAPED_UNICODE);
}
?>