<?php include_once("../includes/includes.php");
session_start();

$eventoid    = (int)($_POST['eventoid'] ?? 0);
$proveedorid = (int)($_POST['proveedorid'] ?? 0);
$obs         = trim($_POST['obs'] ?? '');

if (!$eventoid || !$proveedorid){ echo "Parámetros incompletos"; exit; }

try {
  $db = new FirebirdConnection();

  // Insert
  $db->execute("
    INSERT INTO AMPAR_HIS_EVENTOPROVEEDOR
    (EVENTOPROVEEDOR_EVENTOID, EVENTOPROVEEDOR_PROVEEDORID, EVENTOPROVEEDOR_OBSERVACIONES)
    VALUES (?, ?, ?)
  ", [$eventoid, $proveedorid, $obs]);

  // Bitácora
  $usersesion = $_SESSION['ampar']['usuario'];
  $ipuser = bitacora::getip();
  $prov = $db->query("SELECT NOMBRE FROM PROVEEDORES WHERE PROVEEDOR_ID = ?", [$proveedorid]);
  $ev   = $db->query("SELECT EVENTO_FOLIO, EVENTO_SUCURSALID FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = ?", [$eventoid]);
  $nombreProv = $prov && $prov!=0 ? $prov[0]['NOMBRE'] : 'Proveedor';
  $folio      = $ev && $ev!=0 ? $ev[0]['EVENTO_FOLIO'] : '';

  $db->execute("
    INSERT INTO AMPAR_BITACORA
    (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_SUCURSALID_MS, BITACORA_ALMACENID, BITACORA_STOCKID, BITACORA_EVENTOID)
    VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)
  ", [
    'SE AGREGÓ PROVEEDOR <b>'.htmlentities($nombreProv).'</b> AL EVENTO FOLIO:<strong>'.$folio.'</strong>',
    $usersesion['USUARIO_ID'] ?? null,
    $usersesion['USUARIO_CORREO'] ?? null,
    $ipuser,
    $ev[0]['EVENTO_SUCURSALID'] ?? null,
    null, null, $eventoid
  ]);

  $db->close();
  echo ""; // OK
} catch (Throwable $e){
  echo "Error: ".$e->getMessage();
}
?>