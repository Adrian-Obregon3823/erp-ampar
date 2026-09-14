<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

try {
  if (empty($_POST['eventoid'])) throw new Exception('Falta eventoid');
  $eventoid = (int)$_POST['eventoid'];
  $db = new FirebirdConnection(false);

  // Presupuesto
  $ev = $db->query("SELECT EVENTO_PRESUPUESTOPARTICULAR FROM AMPAR_EVENTOS WHERE EVENTO_ID = ?", [$eventoid]);
  $pres = $ev ? (float)$ev[0]['EVENTO_PRESUPUESTOPARTICULAR'] : 0;

  // Total cargos
  $s = $db->query("SELECT COALESCE(SUM(MONTO),0) AS T FROM AMPAR_EVENTOS_CARGOS WHERE EVENTO_ID=?",[$eventoid]);
  $total = $s ? (float)$s[0]['T'] : 0;

  // PAGO_STATUS
  $pago = 0;
  if ($total>0 && $pres>0){
    $pago = ($total >= $pres) ? 41 : 40;
  } elseif ($total>0 && $pres<=0){
    // si no hay presupuesto, consideramos abonos
    $pago = 40;
  } else {
    $pago = 0;
  }

  // MERGE a REVFLOW (no tocar si ya validado 42)
  $rf = $db->query("SELECT PAGO_STATUS FROM AMPAR_EVENTOS_REVFLOW WHERE EVENTO_ID=?",[$eventoid]);
  if ($rf){
    $curr = (int)$rf[0]['PAGO_STATUS'];
    if ($curr!==42){ // si no está validado, actualiza
      $db->execute("UPDATE AMPAR_EVENTOS_REVFLOW SET PAGO_STATUS=?, PAGO_FECHA=CURRENT_TIMESTAMP WHERE EVENTO_ID=?",
        [$pago, $eventoid]);
    }
  } else {
    $db->execute("
      INSERT INTO AMPAR_EVENTOS_REVFLOW (EVENTO_ID, PAGO_STATUS, PAGO_FECHA)
      VALUES (?,?, CURRENT_TIMESTAMP)
    ", [ $eventoid, $pago ]);
  }

  $db->commit();
  echo '';
} catch (Exception $e) {
  if (isset($db)) $db->rollback();
  http_response_code(400);
  echo 'Error: '.$e->getMessage();
}
