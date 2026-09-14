<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

try {
  if (empty($_POST['eventoid'])) throw new Exception('Falta eventoid');
  $eventoid = (int)$_POST['eventoid'];
  $aprobar  = isset($_POST['aprobar']) ? (int)$_POST['aprobar'] : 0; // 1 VoBo, 0 Observado
  $motivo   = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

  $user = $_SESSION['ampar']['usuario'] ?? null;
  $uid  = $user['USUARIO_ID'] ?? null;

  $db = new FirebirdConnection(false);

  if ($aprobar === 1){
    // VoBo: PAGO_STATUS=42 (Validado), REV_STATUS=72 (VoBo)
    $db->execute("
      UPDATE AMPAR_EVENTOS_REVFLOW
         SET PAGO_STATUS=42, REV_STATUS=72, PAGO_FECHA=CURRENT_TIMESTAMP
       WHERE EVENTO_ID=?
    ", [$eventoid]);

    if ($motivo!==''){
      $db->execute("
        INSERT INTO AMPAR_EVENTOS_PAGO_COMENTARIOS (EVENTO_ID, TEXTO, USUARIO_ID, CREADO_EN)
        VALUES (?,?,?, CURRENT_TIMESTAMP)
      ", [ $eventoid, $motivo, $uid ]);
    }
  } else {
    // Observado: REV_STATUS=71; mantener PAGO_STATUS (40/41); agregar comentario obligatorio sugerido
    $db->execute("
      UPDATE AMPAR_EVENTOS_REVFLOW
         SET REV_STATUS=71
       WHERE EVENTO_ID=?
    ", [$eventoid]);

    $db->execute("
      INSERT INTO AMPAR_EVENTOS_PAGO_COMENTARIOS (EVENTO_ID, TEXTO, USUARIO_ID, CREADO_EN)
      VALUES (?,?,?, CURRENT_TIMESTAMP)
    ", [ $eventoid, ($motivo!==''?$motivo:'Pagos observados'), $uid ]);
  }

  $db->commit();
  echo '';
} catch (Exception $e) {
  if (isset($db)) $db->rollback();
  http_response_code(400);
  echo 'Error: '.$e->getMessage();
}
