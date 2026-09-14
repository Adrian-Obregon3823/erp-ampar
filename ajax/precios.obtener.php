<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$articulo_id  = isset($_GET['articulo_id'])  ? (int)$_GET['articulo_id']  : 0;
$artprecio_id = isset($_GET['artprecio_id']) ? (int)$_GET['artprecio_id'] : 0;

$out = ['ok'=>false];

try{
  // Autocommit TRUE para lecturas (evita dejar transacciones abiertas)
  $db = new FirebirdConnection(true);

  // ----- Obtener UN precio por ID (para editar) -----
  if ($artprecio_id > 0){
    $sql = "
      SELECT
        P.ARTPRECIO_ID,
        P.ARTPRECIO_ARTICULOID AS ARTICULO_ID,
        P.ARTPRECIO_CLIENTEID  AS CLIENTE_ID,
        P.ARTPRECIO_SUBTOTAL   AS SUBTOTAL,
        P.ARTPRECIO_IVA        AS IVA,
        P.ARTPRECIO_TOTAL      AS TOTAL,
        CASE 
          WHEN P.ARTPRECIO_CLIENTEID > 0 THEN '(Hospital) ' || H.HOSPITAL_NOMBRE
          WHEN P.ARTPRECIO_CLIENTEID < 0 THEN '(Cliente) ' || C.NOMBRE
          ELSE NULL 
        END AS CLIENTE_NOMBRE
      FROM AMPAR_CAT_ARTPRECIO P
      LEFT JOIN AMPAR_CAT_HOSPITALES H ON H.HOSPITAL_ID = P.ARTPRECIO_CLIENTEID AND P.ARTPRECIO_CLIENTEID > 0
      LEFT JOIN CLIENTES C ON C.CLIENTE_ID = (P.ARTPRECIO_CLIENTEID * -1) AND P.ARTPRECIO_CLIENTEID < 0
      WHERE P.ARTPRECIO_ID = ?
    ";
    $row = $db->query($sql, [$artprecio_id]);
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
      P.ARTPRECIO_ID,
      P.ARTPRECIO_ARTICULOID AS ARTICULO_ID,
      P.ARTPRECIO_CLIENTEID  AS CLIENTE_ID,
      P.ARTPRECIO_SUBTOTAL   AS SUBTOTAL,
      P.ARTPRECIO_IVA        AS IVA,
      P.ARTPRECIO_TOTAL      AS TOTAL,
      CASE 
        WHEN P.ARTPRECIO_CLIENTEID > 0 THEN '(Hospital) ' || H.HOSPITAL_NOMBRE
        WHEN P.ARTPRECIO_CLIENTEID < 0 THEN '(Cliente) ' || C.NOMBRE
        ELSE NULL 
      END AS CLIENTE_NOMBRE
    FROM AMPAR_CAT_ARTPRECIO P
    LEFT JOIN AMPAR_CAT_HOSPITALES H ON H.HOSPITAL_ID = P.ARTPRECIO_CLIENTEID AND P.ARTPRECIO_CLIENTEID > 0
    LEFT JOIN CLIENTES C ON C.CLIENTE_ID = (P.ARTPRECIO_CLIENTEID * -1) AND P.ARTPRECIO_CLIENTEID < 0
    WHERE P.ARTPRECIO_ARTICULOID = ?
    ORDER BY
      CASE WHEN P.ARTPRECIO_CLIENTEID IS NULL THEN 0 ELSE 1 END,
      2
  ";
  $rows = $db->query($sql, [$articulo_id]);

  $out['ok']   = true;
  $out['items']= $rows ?: [];
  echo json_encode($out);

} catch(Throwable $e){
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
