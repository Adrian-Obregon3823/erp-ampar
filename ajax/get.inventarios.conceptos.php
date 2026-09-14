<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener catálogo de conceptos de inventario
*********************************************************************************
*/
$almacen = new almacenes();
$res = $almacen->getconceptosinventarios((isset($_GET['naturalezaconcepto']))?$_GET['naturalezaconcepto']:'');
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['ID'] = '';
  $array[0]['NOMBRE'] = '';
  echo json_encode($array);
}
?>