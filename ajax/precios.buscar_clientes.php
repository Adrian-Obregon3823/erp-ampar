<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$res = ['ok'=>true, 'items'=>[]];

try{
  // Autocommit para lecturas: evita dejar transacciones abiertas
  $db = new FirebirdConnection(true);

  if ($q === '*') {
    // Lista rápida sin filtro (máximo 30)
    $sql = "
      SELECT c.CLIENTE_ID, c.NOMBRE
      FROM CLIENTES c
      ORDER BY c.NOMBRE
      ROWS 30
    ";
    $rows = $db->query($sql);
  } elseif ($q !== '') {
    // Búsqueda por nombre. Opción A: CONTAINING (no requiere UPPER ni %)
    $sql = "
      SELECT c.CLIENTE_ID, c.NOMBRE
      FROM CLIENTES c
      WHERE c.NOMBRE CONTAINING ?
      ORDER BY c.NOMBRE
      ROWS 30
    ";
    $rows = $db->query($sql, [$q]);

    // --- Opción B (si prefieres LIKE): ---
    // $like = '%'.$q.'%';
    // $sql = "
    //   SELECT c.CLIENTE_ID, c.NOMBRE
    //   FROM CLIENTES c
    //   WHERE UPPER(c.NOMBRE) LIKE UPPER(?)
    //   ORDER BY c.NOMBRE
    //   ROWS 30
    // ";
    // $rows = $db->query($sql, [$like]);
  } else {
    // q vacío => no regreses nada (evita traer todo)
    $rows = [];
  }

  foreach (($rows ?: []) as $r) {
    $res['items'][] = [
      'CLIENTE_ID' => (int)$r['CLIENTE_ID'],
      'NOMBRE'     => (string)$r['NOMBRE'],
    ];
  }

} catch (Throwable $e) {
  // Devuélvelo para verlo en Network->Response si falla algo
  $res = ['ok'=>false, 'items'=>[], 'error'=>$e->getMessage()];
}

echo json_encode($res);
