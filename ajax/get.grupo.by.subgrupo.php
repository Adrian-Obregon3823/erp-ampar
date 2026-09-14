<?php
// ajax/get.grupo.by.subgrupo.php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try{
  $subId = (int)($_GET['subgrupoid'] ?? 0);
  if ($subId<=0) throw new Exception('subgrupoid inválido');

  $db = new FirebirdConnection();

  // AMPAR_CAT_TIPOEVENTOSUBGRUPO(TIPOEVENTOSUBGRUPO_ID, TIPOEVENTOSUBGRUPO_NOMBRE, TIPOEVENTOSUBGRUPO_GRUPOID)
  // AMPAR_CAT_TIPOEVENTOGRUPO(TIPOEVENTOGRUPO_ID, TIPOEVENTOGRUPO_NOMBRE)
  $sql = "
    SELECT s.TIPOEVENTOSUBGRUPO_ID, s.TIPOEVENTOSUBGRUPO_NOMBRE,
           g.TIPOEVENTOGRUPO_ID AS GRUPO_ID, g.TIPOEVENTOGRUPO_NOMBRE AS GRUPO_NOMBRE
      FROM AMPAR_CAT_TIPOEVENTOSUBGRUPO s
      JOIN AMPAR_CAT_TIPOEVENTOGRUPO g
        ON g.TIPOEVENTOGRUPO_ID = s.TIPOEVENTOSUBGRUPO_GRUPOID
     WHERE s.TIPOEVENTOSUBGRUPO_ID = ?
  ";
  $row = $db->query($sql, [$subId]);
  if(!$row){ echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }
  $r = $row[0];

  echo json_encode([
    'ok'=>true,
    'subgrupo_id'     => (int)$r['TIPOEVENTOSUBGRUPO_ID'],
    'subgrupo_nombre' => (string)$r['TIPOEVENTOSUBGRUPO_NOMBRE'],
    'grupo_id'        => (int)$r['GRUPO_ID'],
    'grupo_nombre'    => (string)$r['GRUPO_NOMBRE'],
  ], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
