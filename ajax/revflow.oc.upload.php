<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: text/plain; charset=UTF-8');

try {
  if (empty($_POST['eventoid'])) { echo "Falta eventoid"; exit; }
  $eventoid = (int)$_POST['eventoid'];
  $notasRaw = isset($_POST['notas']) ? trim($_POST['notas']) : '';
  $notas = ($notasRaw===null) ? '' : $notasRaw;

  if (empty($_FILES['archivo']['name'])) { echo "Selecciona un archivo"; exit; }
  $f = $_FILES['archivo'];
  if ($f['error'] !== UPLOAD_ERR_OK) { echo "Error al subir el archivo"; exit; }

  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['pdf','jpg','jpeg','png'], true)) { echo "Formato no permitido"; exit; }

  $destDir = realpath(__DIR__ . '/../uploads');
  if (!$destDir) { @mkdir(__DIR__ . '/../uploads', 0775, true); $destDir = realpath(__DIR__ . '/../uploads'); }
  if (!$destDir) { echo "No se pudo crear carpeta de uploads"; exit; }

  $safeName = preg_replace('/[^A-Za-z0-9_\.-]/','_', $f['name']);
  $final    = $eventoid.'_OC_'.date('YmdHis').'_'.$safeName;
  $finalAbs = $destDir . DIRECTORY_SEPARATOR . $final;
  if (!move_uploaded_file($f['tmp_name'], $finalAbs)) { echo "No se pudo mover el archivo"; exit; }

  $relPath = 'uploads/'.$final;
  $mime = $f['type'] ?: ('application/'.$ext);
  $size = (int)$f['size'];

  $db = new FirebirdConnection(false);

  // Guarda archivo y devuelve FILE_ID
  $fileId = $db->executeconreturning("
    INSERT INTO AMPAR_EVENTOS_FILES
      (FILE_ID, EVENTO_ID, FILE_TIPO, FILE_NOMBRE, FILE_PATH, FILE_MIME, FILE_SIZE, FILE_NOTAS, CREADO_EN)
    VALUES (GEN_ID(GEN_AMPAR_EVENTOS_FILES_ID,1), ?, 'OC_CLIENTE', ?, ?, ?, ?, CAST(? AS BLOB SUB_TYPE 1), CURRENT_TIMESTAMP)
  ", 'FILE_ID', [ $eventoid, $f['name'], $relPath, $mime, $size, $notas ]);

  if (!$fileId) { throw new Exception("No se pudo guardar archivo"); }

  // Marca OC como CARGADA (20)
  $db->execute("
    MERGE INTO AMPAR_EVENTOS_REVFLOW t
    USING (SELECT CAST(? AS BIGINT) AS EVENTO_ID FROM RDB\$DATABASE) s
      ON (t.EVENTO_ID = s.EVENTO_ID)
    WHEN MATCHED THEN UPDATE SET
      OC_STATUS      = 20,
      OC_FILE_ID     = CAST(? AS BIGINT),
      OC_NOTAS       = CAST(? AS BLOB SUB_TYPE 1),
      OC_FECHA       = CURRENT_TIMESTAMP,
      ACTUALIZADO_EN = CURRENT_TIMESTAMP
    WHEN NOT MATCHED THEN
      INSERT (EVENTO_ID, OC_STATUS, OC_FILE_ID, OC_NOTAS, OC_FECHA, CREADO_EN)
      VALUES (CAST(? AS BIGINT), 20, CAST(? AS BIGINT), CAST(? AS BLOB SUB_TYPE 1), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
  ", [ $eventoid, $fileId, $notas, $eventoid, $fileId, $notas ]);

  $db->commit(); $db->close();
  echo "";
} catch (Throwable $e) {
  if (isset($db)) { $db->rollback(); $db->close(); }
  echo "Error: ".$e->getMessage();
}
