<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';

header('Content-Type: application/json; charset=utf-8');
try {
  if (empty($_GET['eventoid'])) throw new Exception('Falta eventoid');
  $eventoid = (int)$_GET['eventoid'];
  $db = new FirebirdConnection(true);

  $rows = $db->query("
    SELECT c.CARGO_ID, c.CREADO_EN, c.MONTO, c.CONCEPTO,
           f.FILE_ID, f.FILE_NOMBRE, f.FILE_PATH
      FROM AMPAR_EVENTOS_CARGOS c
      LEFT JOIN AMPAR_EVENTOS_FILES f ON f.FILE_ID = c.FILE_ID
     WHERE c.EVENTO_ID = ?
     ORDER BY c.CREADO_EN DESC, c.CARGO_ID DESC
  ", [ $eventoid ]);

  $data = [];
  foreach ($rows ?: [] as $r){
    $url = null;
    if (!empty($r['FILE_PATH'])){
      $url = '../' . ltrim(str_replace('\\','/',$r['FILE_PATH']),'/');
    }
    $data[] = [
      'id'        => (int)$r['CARGO_ID'],
      'fecha'     => $r['CREADO_EN'],
      'monto'     => (float)$r['MONTO'],
      'concepto'  => $r['CONCEPTO'],
      'file_id'   => $r['FILE_ID'] ? (int)$r['FILE_ID'] : null,
      'file_name' => $r['FILE_NOMBRE'] ?? null,
      'file_url'  => $url
    ];
  }

  echo json_encode(['ok'=>true,'rows'=>$data], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
