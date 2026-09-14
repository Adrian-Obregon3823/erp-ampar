<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try {
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  if (empty($_POST['status']))   { echo "Falta status"; exit; }

  $eventoid = (int)$_POST['eventoid'];
  $status   = strtoupper(trim($_POST['status'])); // EN_REVISION|RECHAZADA|AUTORIZADA
  $notas    = isset($_POST['notas']) ? trim($_POST['notas']) : '';

  $valid = ['SUBIDA','EN_REVISION','RECHAZADA','AUTORIZADA','NO_APLICA'];
  if (!in_array($status, $valid, true)) { echo "Status no válido"; exit; }

  $db = new FirebirdConnection();

  $db->execute("
    MERGE INTO AMPAR_EVENTOS_REVFLOW t
    USING (SELECT ? AS EVENTO_ID FROM RDB\$DATABASE) s
      ON t.EVENTO_ID = s.EVENTO_ID
    WHEN MATCHED THEN UPDATE SET
      OC_STATUS = ?, OC_NOTAS = ?, OC_FECHA = CURRENT_TIMESTAMP
    WHEN NOT MATCHED THEN INSERT (EVENTO_ID, OC_STATUS, OC_NOTAS, OC_FECHA)
      VALUES (?, ?, ?, CURRENT_TIMESTAMP)
  ", [ $eventoid, $status, $notas, $eventoid, $status, $notas ]);

  $db->commit();
  $db->close();
  echo "";
} catch (Throwable $e) {
  if (isset($db)) { $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
