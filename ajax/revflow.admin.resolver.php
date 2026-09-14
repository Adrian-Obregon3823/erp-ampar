<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  if (!isset($_POST['aprobar'])) { echo "Falta aprobar"; exit; }

  $eventoid = (int)$_POST['eventoid'];
  $aprobar  = ((int)$_POST['aprobar']===1) ? 1 : 0;
  $notas    = isset($_POST['notas']) ? trim($_POST['notas']) : '';

  $db = new FirebirdConnection(false);

  $nuevo = $aprobar ? 72 : 71; /* 72 VoBo, 71 Observado */
  $db->execute("
    UPDATE AMPAR_EVENTOS_REVFLOW
       SET REV_STATUS     = $nuevo,
           REV_NOTAS      = CAST(? AS BLOB SUB TYPE 1),
           REV_FECHA      = CURRENT_TIMESTAMP,
           ACTUALIZADO_EN = CURRENT_TIMESTAMP
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $notas, $eventoid ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
