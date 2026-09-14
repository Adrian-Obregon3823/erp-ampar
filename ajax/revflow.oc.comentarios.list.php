<?php
require_once '../includes/includes.php';
require_once '../class/bdfirebird.php';
header('Content-Type: application/json; charset=UTF-8');

$out = ['ok'=>false,'rows'=>[]];
try{
  if (empty($_GET['eventoid'])) { echo json_encode($out); exit; }
  $eventoid = (int)$_GET['eventoid'];

  $db = new FirebirdConnection(true);

  $rows = $db->query("
    SELECT c.COMMENT_ID, c.CREADO_EN, c.TEXTO, c.USUARIO_ID,
           u.USUARIO_NOMBRE
      FROM AMPAR_EVENTOS_OC_COMENTARIOS c
      LEFT JOIN USUARIOS u ON u.USUARIO_ID = c.USUARIO_ID
     WHERE c.EVENTO_ID = ?
     ORDER BY c.CREADO_EN DESC
  ", [ $eventoid ]);

  $list = [];
  if ($rows){
    foreach($rows as $r){
      $list[] = [
        'id'      => (int)$r['COMMENT_ID'],
        'fecha'   => $r['CREADO_EN'],
        'texto'   => $r['TEXTO'],
        'usuario' => $r['USUARIO_NOMBRE'] ?? $r['USUARIO_ID']
      ];
    }
  }

  $out['ok']=true; $out['rows']=$list;
  echo json_encode($out);
}catch(Throwable $e){
  $out['ok']=false; $out['error']=$e->getMessage();
  echo json_encode($out);
}
