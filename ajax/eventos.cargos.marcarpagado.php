<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  $eventoid = (int)$_POST['eventoid'];

  $db = new FirebirdConnection(false);

  $db->execute("
    UPDATE AMPAR_HIS_EVENTOS
       SET EVENTO_STATUSGENERAL = 23
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $eventoid ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
