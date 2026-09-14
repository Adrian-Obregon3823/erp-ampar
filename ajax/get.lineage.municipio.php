<?php
// ajax/get.lineage.municipio.php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try{
  $ciudadId = (int)($_GET['ciudadid'] ?? 0);
  if ($ciudadId<=0) throw new Exception('ciudadid inválido');

  $db = new FirebirdConnection();

  // Ajusta nombres de campos/tabla si difieren en tu esquema
  // CIUDADES(CIUDAD_ID, NOMBRE, ESTADO_ID)
  // ESTADOS(ESTADO_ID, NOMBRE, PAIS_ID)
  // PAISES(PAIS_ID, NOMBRE)
  $sql = "
    SELECT c.CIUDAD_ID, c.NOMBRE AS CIUDAD_NOMBRE,
           e.ESTADO_ID, e.NOMBRE AS ESTADO_NOMBRE,
           p.PAIS_ID, p.NOMBRE AS PAIS_NOMBRE
      FROM CIUDADES c
      JOIN ESTADOS  e ON e.ESTADO_ID = c.ESTADO_ID
      JOIN PAISES   p ON p.PAIS_ID   = e.PAIS_ID
     WHERE c.CIUDAD_ID = ?
  ";
  $row = $db->query($sql, [$ciudadId]);
  if(!$row){ echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }
  $r = $row[0];

  echo json_encode([
    'ok'=>true,
    'ciudad_id'     => (int)$r['CIUDAD_ID'],
    'ciudad_nombre' => (string)$r['CIUDAD_NOMBRE'],
    'estado_id'     => (int)$r['ESTADO_ID'],
    'estado_nombre' => (string)$r['ESTADO_NOMBRE'],
    'pais_id'       => (int)$r['PAIS_ID'],
    'pais_nombre'   => (string)$r['PAIS_NOMBRE'],
  ], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
