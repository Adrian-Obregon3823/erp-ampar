<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$articulo_id  = isset($_GET['articulo_id'])  ? (int)$_GET['articulo_id']  : 0;
$artcompra_id = isset($_GET['artcompra_id']) ? (int)$_GET['artcompra_id'] : 0;

$out = ['ok'=>false];

try{
  // Autocommit TRUE para lecturas (evita dejar transacciones abiertas)
  $db = new FirebirdConnection(true);

  // ----- Obtener UN precio por ID (para editar) -----
  if ($artcompra_id > 0){
    $sql = "
      SELECT
        P.ARTCOMPRA_ID,
        P.ARTCOMPRA_ARTICULOID AS ARTICULO_ID,
        P.ARTCOMPRA_PROVEEDORID  AS PROVEEDOR_ID,
        P.ARTCOMPRA_SUBTOTAL   AS SUBTOTAL,
        P.ARTCOMPRA_IVA        AS IVA,
        P.ARTCOMPRA_TOTAL      AS TOTAL,
        PR.NOMBRE               AS PROVEEDOR_NOMBRE
      FROM AMPAR_CAT_ARTCOMPRA P
      LEFT JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
      WHERE P.ARTCOMPRA_ID = ?
    ";
    $row = $db->query($sql, [$artcompra_id]);
    if (!$row || !is_array($row) || count($row)===0) {
      throw new Exception('No existe el registro solicitado.');
    }
    $out['ok']  = true;
    $out['item']= $row[0];
    echo json_encode($out); exit;
  }

  // ----- Obtener lista por ARTÍCULO -----
  if ($articulo_id <= 0) {
    throw new Exception('articulo_id requerido');
  }

  $sql = "
    SELECT
      P.ARTCOMPRA_ID,
      P.ARTCOMPRA_ARTICULOID AS ARTICULO_ID,
      P.ARTCOMPRA_PROVEEDORID  AS PROVEEDOR_ID,
      P.ARTCOMPRA_SUBTOTAL   AS SUBTOTAL,
      P.ARTCOMPRA_IVA        AS IVA,
      P.ARTCOMPRA_TOTAL      AS TOTAL,
      PR.NOMBRE               AS PROVEEDOR_NOMBRE
    FROM AMPAR_CAT_ARTCOMPRA P
    LEFT JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
    WHERE P.ARTCOMPRA_ARTICULOID = ?
    ORDER BY
      CASE WHEN P.ARTCOMPRA_PROVEEDORID IS NULL THEN 0 ELSE 1 END,
      PR.NOMBRE
  ";
  $rows = $db->query($sql, [$articulo_id]);

  $out['ok']   = true;
  $out['items']= $rows ?: [];
  echo json_encode($out);

} catch(Throwable $e){
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
