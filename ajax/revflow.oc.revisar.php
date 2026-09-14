<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try{
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  if (!isset($_POST['aprobar'])) { echo "Falta aprobar"; exit; }

  $eventoid = (int)$_POST['eventoid'];
  $aprobar  = ((int)$_POST['aprobar']===1) ? 1 : 0;
  $motivo   = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
  $userid   = isset($_SESSION['ampar']['usuario']['USUARIO_ID'])
                ? (int)$_SESSION['ampar']['usuario']['USUARIO_ID'] : null;

  $db = new FirebirdConnection(false);

  // Cambia estado: 22 aprobada, 21 rechazada
  $nuevo = $aprobar ? 22 : 21;
  $db->execute("
    UPDATE AMPAR_EVENTOS_REVFLOW
       SET OC_STATUS      = $nuevo,
           OC_NOTAS = COALESCE(CAST(? AS BLOB SUB_TYPE 1), OC_NOTAS),
           OC_FECHA       = CURRENT_TIMESTAMP,
           ACTUALIZADO_EN = CURRENT_TIMESTAMP
     WHERE EVENTO_ID = CAST(? AS BIGINT)
  ", [ $motivo, $eventoid ]);

  // Registra comentario de retroalimentación
  $db->executeconreturning("
    INSERT INTO AMPAR_EVENTOS_OC_COMENTARIOS
      (COMMENT_ID, EVENTO_ID, USUARIO_ID, TEXTO, CREADO_EN)
    VALUES (GEN_ID(GEN_AE_OC_COMENT_ID,1),
            CAST(? AS BIGINT),
            CAST(? AS BIGINT),
            CAST(? AS BLOB SUB_TYPE 1),
            CURRENT_TIMESTAMP)
  ", 'COMMENT_ID', [ $eventoid, $userid, $motivo ]);

  $db->commit(); $db->close();
  echo "";
}catch(Throwable $e){
  if(isset($db)){ $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
