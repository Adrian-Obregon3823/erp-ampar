<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$solo_clientes = isset($_GET['solo_clientes']) ? (int)$_GET['solo_clientes'] : 0;
$res = ['ok'=>true, 'items'=>[]];

try{
  $db = new FirebirdConnection(true);

  if ($q === '*') {
      if ($solo_clientes === 1) {
          $sql = "
            SELECT * FROM (
              SELECT (CLIENTE_ID * -1) AS CLIENTE_ID, NOMBRE AS NOMBRE
              FROM CLIENTES
            )
            ORDER BY NOMBRE
            ROWS 50
          ";
      } else {
          $sql = "
            SELECT * FROM (
              SELECT HOSPITAL_ID AS CLIENTE_ID, '(Hospital) ' || HOSPITAL_NOMBRE AS NOMBRE
              FROM AMPAR_CAT_HOSPITALES
              WHERE HOSPITAL_ACTIVO = 1
              UNION
              SELECT (CLIENTE_ID * -1) AS CLIENTE_ID, '(Cliente) ' || NOMBRE AS NOMBRE
              FROM CLIENTES
            )
            ORDER BY NOMBRE
            ROWS 50
          ";
      }
    $rows = $db->query($sql);
  } elseif ($q !== '') {
      if ($solo_clientes === 1) {
          $sql = "
            SELECT * FROM (
              SELECT (CLIENTE_ID * -1) AS CLIENTE_ID, NOMBRE AS NOMBRE
              FROM CLIENTES
              WHERE NOMBRE CONTAINING ?
            )
            ORDER BY NOMBRE
            ROWS 50
          ";
          $rows = $db->query($sql, [$q]);
      } else {
          $sql = "
            SELECT * FROM (
              SELECT HOSPITAL_ID AS CLIENTE_ID, '(Hospital) ' || HOSPITAL_NOMBRE AS NOMBRE
              FROM AMPAR_CAT_HOSPITALES
              WHERE HOSPITAL_ACTIVO = 1 AND HOSPITAL_NOMBRE CONTAINING ?
              UNION
              SELECT (CLIENTE_ID * -1) AS CLIENTE_ID, '(Cliente) ' || NOMBRE AS NOMBRE
              FROM CLIENTES
              WHERE NOMBRE CONTAINING ?
            )
            ORDER BY NOMBRE
            ROWS 50
          ";
          $rows = $db->query($sql, [$q, $q]);
      }
  } else {
    $rows = [];
  }

  foreach (($rows ?: []) as $r) {
    $res['items'][] = [
      'CLIENTE_ID' => (int)$r['CLIENTE_ID'],
      'NOMBRE'     => (string)$r['NOMBRE'],
    ];
  }

} catch (Throwable $e) {
  $res = ['ok'=>false, 'items'=>[], 'error'=>$e->getMessage()];
}

echo json_encode($res);
