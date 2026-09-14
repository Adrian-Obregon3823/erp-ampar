<?php 
include_once ("../includes/sesion.php"); 
include_once("../includes/includes.php");
?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar remision
*********************************************************************************
*/
?>
<?php
$tipo = $_POST['tipo'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
if (!in_array($tipo, ['AMPAR','PROVEEDOR']) || !$id){ echo "Parámetros inválidos"; exit; }

try {
  // Traer info para bitácora antes de borrar
  $db = new FirebirdConnection();
  if ($tipo === 'AMPAR'){
    $info = $db->query("
      SELECT RA.REMISIONARTICULO_ID, RA.REMISIONARTICULO_REMISIONID, R.REMISION_EVENTOID,
             S.STOCK_FOLIO, A.NOMBRE AS ARTICULO_NOMBRE
      FROM AMPAR_HIS_REMISIONESARTICULOS RA
      JOIN AMPAR_HIS_REMISIONES R ON R.REMISION_ID = RA.REMISIONARTICULO_REMISIONID
      JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = RA.REMISIONARTICULO_STOCKID
      JOIN ARTICULOS A ON A.ARTICULO_ID = S.STOCK_ARTICULOID
      WHERE RA.REMISIONARTICULO_ID = ?
    ", [$id]);
    $db->execute("DELETE FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_ID = ?", [$id]);
  } else {
    $info = $db->query("
      SELECT RP.REMISIONPROVARTICULO_ID, RP.REMISIONPROVARTICULO_REMISIONID, R.REMISION_EVENTOID,
             P.NOMBRE AS PROVEEDOR, A.NOMBRE AS ARTICULO
      FROM AMPAR_HIS_REMISIONESPARTICULOS RP
      JOIN AMPAR_HIS_REMISIONES R ON R.REMISION_ID = RP.REMISIONPROVARTICULO_REMISIONID
      JOIN PROVEEDORES P ON P.PROVEEDOR_ID = RP.REMISIONPROVARTICULO_PROVID
      JOIN ARTICULOS A ON A.ARTICULO_ID = RP.REMISIONPROVARTICULO_ARTICULOID
      WHERE RP.REMISIONPROVARTICULO_ID = ?
    ", [$id]);
    $db->execute("DELETE FROM AMPAR_HIS_REMISIONESPARTICULOS WHERE REMISIONPROVARTICULO_ID = ?", [$id]);
  }

  // Bitácora
  if ($info && $info!=0){
    $evId = $info[0]['REMISION_EVENTOID'] ?? null;
    $usersesion = $_SESSION['ampar']['usuario'];
    $ipuser = bitacora::getip();
    $txt = ($tipo==='AMPAR')
      ? ('SE ELIMINÓ ARTÍCULO <b>'.htmlentities($info[0]['STOCK_FOLIO'].' - '.$info[0]['ARTICULO_NOMBRE']).'</b> DE LA REMISIÓN')
      : ('SE ELIMINÓ PROVEEDOR/ARTÍCULO <b>'.htmlentities(($info[0]['PROVEEDOR']??'').' - '.($info[0]['ARTICULO']??''))).'</b> DE LA REMISIÓN';

    $db->execute("
      INSERT INTO AMPAR_BITACORA
      (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
      VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [$txt, $usersesion['USUARIO_ID'] ?? null, $usersesion['USUARIO_CORREO'] ?? null, $ipuser, null, null, null, $evId]);
  }

  $db->close();
  echo "";
}catch(Throwable $e){
  echo "Error: ".$e->getMessage();
}
?>