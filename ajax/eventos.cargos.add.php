<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

try {
  if (empty($_POST['eventoid'])) throw new Exception('Falta eventoid');
  $eventoid = (int)$_POST['eventoid'];
  $monto    = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
  $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
  if ($monto<=0) throw new Exception('Monto inválido');

  $user = $_SESSION['ampar']['usuario'] ?? null;
  $uid  = $user['USUARIO_ID'] ?? null;

  $db = new FirebirdConnection(false);

  // 1) Subir archivo (opcional)
  $fileId = null;
  if (!empty($_FILES['archivo']) && $_FILES['archivo']['error']===UPLOAD_ERR_OK) {
    $up = $_FILES['archivo'];
    $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
    $dir = __DIR__ . '/../uploads/pagos/';
    if (!is_dir($dir)) { @mkdir($dir,0775,true); }
    $final = 'pago_' . $eventoid . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $abs   = $dir . $final;
    if (!move_uploaded_file($up['tmp_name'], $abs)) throw new Exception('No se pudo guardar el archivo');
    $rel   = 'uploads/pagos/' . $final;
    $rel   = str_replace('\\','/',$rel);

    // Guardar en AMPAR_EVENTOS_FILES
    $fileId = $db->executeconreturning("
      INSERT INTO AMPAR_EVENTOS_FILES (EVENTO_ID, FILE_TIPO, FILE_NOMBRE, FILE_PATH, FILE_MIME, FILE_SIZE, CREADO_EN, CREADO_POR)
      VALUES (?,?,?,?,?,?, CURRENT_TIMESTAMP, ?)
    ", 'FILE_ID', [
      $eventoid, 'PAGO_COMPROBANTE', $up['name'], $rel, $up['type'], (int)$up['size'], $uid
    ]);
  }

  // 2) Insertar cargo
  $db->execute("
    INSERT INTO AMPAR_EVENTOS_CARGOS (EVENTO_ID, MONTO, CONCEPTO, FILE_ID, CREADO_EN, CREADO_POR)
    VALUES (?,?,?,?, CURRENT_TIMESTAMP, ?)
  ", [ $eventoid, $monto, $concepto, $fileId, $uid ]);

  // 3) Recalcular estatus de pagos
  $db->execute("
    EXECUTE PROCEDURE SP_REVFLOW_PAGOS_RECALC(?)
  ", [ $eventoid ]);

  $db->commit();
  echo ''; // OK
} catch (Exception $e) {
  if (isset($db)) $db->rollback();
  http_response_code(400);
  echo 'Error: '.$e->getMessage();
}
