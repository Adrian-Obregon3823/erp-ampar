<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  $eventoid = (int)$_POST['eventoid'];

  $db = new FirebirdConnection(false);

  // Verifica condiciones mínimas: OC ok (12/22) o Pagos ok (41/42)
  $row = $db->query("
    SELECT
      COALESCE(OC_STATUS,0)   AS OC_STATUS,
      COALESCE(PAGO_STATUS,0) AS PAGO_STATUS,
      COALESCE(REV_STATUS,0)  AS REV_STATUS
    FROM AMPAR_EVENTOS_REVFLOW
   WHERE EVENTO_ID = ?
  ", [ $eventoid ]);

  $oc  = $row ? (int)$row[0]['OC_STATUS'] : 0;
  $pg  = $row ? (int)$row[0]['PAGO_STATUS'] : 0;
  $rev = $row ? (int)$row[0]['REV_STATUS'] : 0;

  if ($rev !== 0) { echo "Ya fue enviado o resuelto"; exit; }
  $ocOk = in_array($oc, [12,22], true);
  $pgOk = in_array($pg, [41,42], true);

  if (!$ocOk && !$pgOk) { echo "Falta OC aprobada/exenta o pagos cubiertos"; exit; }

  $db->execute("
    UPDATE AMPAR_EVENTOS_REVFLOW
       SET REV_STATUS     = 70,            -- En revisión admin
           REV_FECHA      = CURRENT_TIMESTAMP,
           ACTUALIZADO_EN = CURRENT_TIMESTAMP
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $eventoid ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
