<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  if (!isset($_POST['motivo']) || trim($_POST['motivo'])===''){ echo "Captura un motivo"; exit; }

  $eventoid = (int)$_POST['eventoid'];
  $motivo   = trim($_POST['motivo']);

  $db = new FirebirdConnection(false);

  $db->execute("
    MERGE INTO AMPAR_EVENTOS_REVFLOW t
    USING (SELECT CAST(? AS BIGINT) AS EVENTO_ID FROM RDB\$DATABASE) s
      ON (t.EVENTO_ID = s.EVENTO_ID)
    WHEN MATCHED THEN UPDATE SET
      OC_STATUS      = 10,                            -- Exención solicitada
      OC_NOTAS       = CAST(? AS BLOB SUB_TYPE 1),
      OC_FECHA       = CURRENT_TIMESTAMP,
      ACTUALIZADO_EN = CURRENT_TIMESTAMP
    WHEN NOT MATCHED THEN
      INSERT (EVENTO_ID, OC_STATUS, OC_NOTAS, OC_FECHA, CREADO_EN)
      VALUES (CAST(? AS BIGINT), 10, CAST(? AS BLOB SUB_TYPE 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
  ", [ $eventoid, $motivo, $eventoid, $motivo ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
