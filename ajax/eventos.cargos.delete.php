<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

try {
  if (empty($_POST['id'])) throw new Exception('Falta id');
  $cargoId = (int)$_POST['id'];
  $db = new FirebirdConnection(false);

  // Traer EVENTO_ID y FILE_ID
  $row = $db->query("SELECT EVENTO_ID, FILE_ID FROM AMPAR_EVENTOS_CARGOS WHERE CARGO_ID = ?", [$cargoId]);
  if (!$row) throw new Exception('Cargo no encontrado');
  $eventoid = (int)$row[0]['EVENTO_ID'];
  $fileId   = $row[0]['FILE_ID'] ? (int)$row[0]['FILE_ID'] : null;

  // Reglas: no permitir eliminar si ya está validado (42)
  $rev = $db->query("SELECT PAGO_STATUS FROM AMPAR_EVENTOS_REVFLOW WHERE EVENTO_ID = ?", [$eventoid]);
  $pstat = $rev ? (int)$rev[0]['PAGO_STATUS'] : 0;
  if ($pstat === 42) throw new Exception('Pagos ya validados; no se puede eliminar');

  $db->execute("DELETE FROM AMPAR_EVENTOS_CARGOS WHERE CARGO_ID = ?", [$cargoId]);

  // Opcional: borrar archivo físico y fila files
  if ($fileId){
    $f = $db->query("SELECT FILE_PATH FROM AMPAR_EVENTOS_FILES WHERE FILE_ID = ?", [$fileId]);
    if ($f && !empty($f[0]['FILE_PATH'])){
      $abs = __DIR__ . '/../' . ltrim(str_replace('\\','/',$f[0]['FILE_PATH']),'/');
      if (is_file($abs)) @unlink($abs);
    }
    $db->execute("DELETE FROM AMPAR_EVENTOS_FILES WHERE FILE_ID = ?", [$fileId]);
  }

  // Recalcular
  $db->execute("EXECUTE PROCEDURE SP_REVFLOW_PAGOS_RECALC(?)", [ $eventoid ]);

  $db->commit();
  echo '';
} catch (Exception $e) {
  if (isset($db)) $db->rollback();
  http_response_code(400);
  echo 'Error: '.$e->getMessage();
}
