<?php include_once("../includes/includes.php");
$remisionid = (int)($_POST['remisionid'] ?? 0);
$proveedorid= (int)($_POST['proveedorid'] ?? 0);
$articuloid = (int)($_POST['articuloid'] ?? 0);
$cantidad   = (int)($_POST['cantidad'] ?? 0);

if (!$remisionid || !$proveedorid || !$articuloid || $cantidad<=0){
  echo "Parámetros incompletos"; exit;
}

try{
  $rem = new remisiones();
  $rem->agregarArticuloProveedor(null, $remisionid, $proveedorid, $articuloid, $cantidad);
  echo "";
}catch(Throwable $e){
  echo "Error: ".$e->getMessage();
}
?>