<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$res = ['items'=>[]];

try{
  // autocommit para lecturas (evita transacciones colgando)
  $db = new FirebirdConnection(true);

  if ($q === '*') {
    $sql = "
      SELECT
        AR.ARTICULO_ID,
        AR.NOMBRE AS ARTICULO_NOMBRE,
        X.CLAVE_ARTICULO AS ARTICULO_CLAVE
      FROM ARTICULOS AR
      LEFT JOIN CLAVES_ARTICULOS X
             ON X.ARTICULO_ID = AR.ARTICULO_ID
            AND X.ROL_CLAVE_ART_ID = 17
      WHERE AR.ESTATUS = 'A'
      ORDER BY
        CASE WHEN X.CLAVE_ARTICULO IS NULL THEN 1 ELSE 0 END,
        X.CLAVE_ARTICULO,
        AR.NOMBRE
      ROWS 30
    ";
    $rows = $db->query($sql); // SIN params
  } elseif ($q !== '') {
    $like = '%'.$q.'%';
    // ⚠️ Usa placeholders POSICIONALES y pásalos DOS veces
    $sql = "
      SELECT
        AR.ARTICULO_ID,
        AR.NOMBRE AS ARTICULO_NOMBRE,
        X.CLAVE_ARTICULO AS ARTICULO_CLAVE
      FROM ARTICULOS AR
      LEFT JOIN CLAVES_ARTICULOS X
             ON X.ARTICULO_ID = AR.ARTICULO_ID
            AND X.ROL_CLAVE_ART_ID = 17
      WHERE AR.ESTATUS = 'A'
        AND (
            UPPER(AR.NOMBRE) LIKE UPPER(?)
         OR UPPER(X.CLAVE_ARTICULO) LIKE UPPER(?)
        )
      ORDER BY
        CASE WHEN X.CLAVE_ARTICULO IS NULL THEN 1 ELSE 0 END,
        X.CLAVE_ARTICULO,
        AR.NOMBRE
      ROWS 30
    ";
    $rows = $db->query($sql, [$like, $like]); // <-- pásalo dos veces
  } else {
    $rows = [];
  }

  foreach (($rows ?: []) as $r) {
    $res['items'][] = [
      'ARTICULO_ID'     => (int)$r['ARTICULO_ID'],
      'ARTICULO_NOMBRE' => (string)$r['ARTICULO_NOMBRE'],
      'ARTICULO_CLAVE'  => isset($r['ARTICULO_CLAVE']) ? (string)$r['ARTICULO_CLAVE'] : ''
    ];
  }

  $res['ok'] = true;

} catch (Throwable $e){
  $res = ['items'=>[], 'ok'=>false, 'error'=>$e->getMessage()];
}

echo json_encode($res);
