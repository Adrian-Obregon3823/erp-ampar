<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

try {
  if (empty($_POST['eventoid'])) throw new Exception('Falta eventoid');
  $eventoid = (int)$_POST['eventoid'];

  $db = new FirebirdConnection(false);

  // Debe haber al menos 1 cargo
  $s = $db->query("SELECT COUNT(*) AS N FROM AMPAR_EVENTOS_CARGOS WHERE EVENTO_ID=?",[$eventoid]);
  if (!$s || (int)$s[0]['N']===0) throw new Exception('No hay pagos capturados');

  // Colocar REV_STATUS=70 (Revisión) para el bloque de pagos
  $rf = $db->query("SELECT REV_STATUS FROM AMPAR_EVENTOS_REVFLOW WHERE EVENTO_ID=?",[$eventoid]);
  if ($rf){
    $db->execute("UPDATE AMPAR_EVENTOS_REVFLOW SET REV_STATUS=70 WHERE EVENTO_ID=?",[$eventoid]);
  } else {
    $db->execute("
      INSERT INTO AMPAR_EVENTOS_REVFLOW (EVENTO_ID, REV_STATUS)
      VALUES (?, 70)
    ",[$eventoid]);
  }

  $db->commit();
  echo '';
} catch (Exception $e) {
  if (isset($db)) $db->rollback();
  http_response_code(400);
  echo 'Error: '.$e->getMessage();
}
