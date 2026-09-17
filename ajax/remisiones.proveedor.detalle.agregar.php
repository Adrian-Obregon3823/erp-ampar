<?php include_once("../includes/includes.php");
$remisionid = (int)($_POST['remisionid'] ?? 0);
$proveedorid= (int)($_POST['proveedorid'] ?? 0);
$articuloid = (int)($_POST['articuloid'] ?? 0);
$cantidad   = (int)($_POST['cantidad'] ?? 0);

if (!$remisionid || !$articuloid || $cantidad<=0){
  echo "Parámetros incompletos"; exit;
}
$proveedorid = $proveedorid > 0 ? $proveedorid : 'NULL';

try{
  $rem = new remisiones();
  $rem->agregarArticuloProveedor(null, $remisionid, $proveedorid, $articuloid, $cantidad);
  echo "";
}catch(Throwable $e){
  echo "Error: ".$e->getMessage();
}
?>