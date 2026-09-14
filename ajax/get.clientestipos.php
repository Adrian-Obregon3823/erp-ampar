<?php
header('Content-Type: application/json; charset=utf-8');
require_once("../includes/sesion.php"); 
require_once("../includes/includes.php");

try{
  $db = new FirebirdConnection();
  $rows = $db->query("
    SELECT 
      CLIENTETIPO_ID   AS ID,
      CLIENTETIPO_NOMBRE AS NOMBRE,
      COALESCE(CLIENTETIPO_PRESUPUESTO, 0) AS PRESUPUESTO
    FROM AMPAR_CAT_CLIENTETIPO
    WHERE COALESCE(CLIENTETIPO_ACTIVO, 0) = 1
    ORDER BY CLIENTETIPO_NOMBRE
  ");

  $out = [];
  foreach(($rows ?: []) as $r){
    $out[] = [
      'ID' => (int)$r['ID'],
      'NOMBRE' => (string)$r['NOMBRE'],
      'PRESUPUESTO' => (int)$r['PRESUPUESTO'] // 0 o 1
    ];
  }
  echo json_encode($out);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
