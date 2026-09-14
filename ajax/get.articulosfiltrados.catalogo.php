<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener catalogo de articulos en un almacen
*********************************************************************************
*/
$almacen = new articulos();
$incluirMaletas = isset($_GET['incluirMaletas']) && $_GET['incluirMaletas'] === '1';

if (!isset($_GET['filtros'])) {
    $incluirCaducados = isset($_GET['incluirCaducados']) && $_GET['incluirCaducados'] === '1';
    $soloCaducados = isset($_GET['soloCaducados']) && $_GET['soloCaducados'] === '1';
    $soloProximosCaducar = isset($_GET['soloProximosCaducar']) && $_GET['soloProximosCaducar'] === '1';
    
    $filtrosArr = [];
    if ($soloCaducados && $soloProximosCaducar) {
        $filtrosArr = ['caducados', 'proximos'];
    } elseif ($soloCaducados) {
        $filtrosArr = ['caducados'];
    } elseif ($soloProximosCaducar) {
        $filtrosArr = ['proximos'];
    } elseif ($incluirCaducados) {
        $filtrosArr = ['caducados', 'proximos', 'vigentes'];
    } else {
        $filtrosArr = ['vigentes'];
    }
    $filtros_str = implode(',', $filtrosArr);
} else {
    $filtros_str = $_GET['filtros'];
}

$almacenid = isset($_GET['almacenid']) ? $_GET['almacenid'] : (isset($_GET['maletaid']) ? $_GET['maletaid'] : 0);

$res = $almacen->getcatalogoarticulosbyalmacenid($almacenid, $filtros_str, $incluirMaletas);
if ($res<>0){
  echo json_encode($res);
}else{
  $array[0]['INVDETID'] = '';
  $array[0]['STOCK_FOLIO'] = '';
  $array[0]['ID'] = '';
  $array[0]['CLAVE_ARTICULO'] = '';
  $array[0]['NOMBRE'] = '';
  $array[0]['LOTE'] = '';
  $array[0]['CADUCIDAD'] = '';
  $array[0]['SERIE'] = '';
  echo json_encode($array);
}
?>