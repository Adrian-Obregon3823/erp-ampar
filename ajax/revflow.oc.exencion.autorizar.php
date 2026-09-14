<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  $eventoid = (int)$_POST['eventoid'];
  $aprobar  = (isset($_POST['aprobar']) && (int)$_POST['aprobar']===1) ? 1 : 0;
  $motivo   = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

  $db = new FirebirdConnection(false);

  $nuevo = $aprobar ? 12 : 21; /* 12 = Exención aprobada, 21 = Rechazada */
  $db->execute("
    UPDATE AMPAR_EVENTOS_REVFLOW
       SET OC_STATUS      = $nuevo,
           OC_NOTAS       = COALESCE(CAST(? AS BLOB SUB_TYPE 1), OC_NOTAS),
           OC_FECHA       = CURRENT_TIMESTAMP,
           ACTUALIZADO_EN = CURRENT_TIMESTAMP
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $motivo, $eventoid ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
