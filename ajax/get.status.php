<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener status de proyecto
*********************************************************************************
*/
$cat = new catalogos();
$res = $cat->getstatus($_GET["modulo"]);
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['id'] = '';
  $array[0]['nombre'] = '';
  echo json_encode($array);
}
?>