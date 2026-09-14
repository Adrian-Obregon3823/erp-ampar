<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  $eventoid = (int)$_POST['eventoid'];

  $db = new FirebirdConnection(false);

  // Solo validamos si está en 41 (cubierto)
  $row = $db->query("
    SELECT COALESCE(PAGO_STATUS,0) AS PS
      FROM AMPAR_EVENTOS_REVFLOW
     WHERE EVENTO_ID = ?
  ", [ $eventoid ]);
  $ps = $row ? (int)$row[0]['PS'] : 0;

  if ($ps !== 41) { echo "Debe estar en 'Cubierto (41)' para validar"; exit; }

  $db->execute("
    UPDATE AMPAR_EVENTOS_REVFLOW
       SET PAGO_STATUS      = 42,              -- Validado
           PAGO_VALIDADO_EN = CURRENT_TIMESTAMP,
           ACTUALIZADO_EN   = CURRENT_TIMESTAMP
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $eventoid ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
