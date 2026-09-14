<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$artprecio_id = isset($_POST['artprecio_id']) ? (int)$_POST['artprecio_id'] : 0;
$articulo_id  = isset($_POST['articulo_id'])  ? (int)$_POST['articulo_id']  : 0;

// cliente_id puede ser NULL (precio base)
$cliente_raw  = isset($_POST['cliente_id']) ? trim($_POST['cliente_id']) : '';
$cliente_id   = ($cliente_raw === '') ? null : (int)$cliente_raw;

$subtotal     = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0.0;
$iva          = isset($_POST['iva'])      ? (float)$_POST['iva']      : 0.0;
$total        = isset($_POST['total'])    ? (float)$_POST['total']    : 0.0;

try{
  if ($articulo_id <= 0) throw new Exception('Artículo inválido');
  if ($subtotal < 0 || $iva < 0 || $total < 0) throw new Exception('Montos inválidos');

  $db = new FirebirdConnection(); // por defecto maneja transacción
  // Validar duplicado (único por ARTICULO + CLIENTE, considerando NULL como -1)
  $sqlDup = "
    SELECT COUNT(*) AS N
      FROM AMPAR_CAT_ARTPRECIO
     WHERE ARTPRECIO_ARTICULOID = ?
       AND COALESCE(ARTPRECIO_CLIENTEID, -1) = COALESCE(?, -1)
  ";
  $paramsDup = [$articulo_id, $cliente_id];
  if ($artprecio_id > 0){
    $sqlDup .= " AND ARTPRECIO_ID <> ? ";
    $paramsDup[] = $artprecio_id;
  }
  $rowDup = $db->query($sqlDup, $paramsDup);
  $nDup   = ($rowDup && isset($rowDup[0]['N'])) ? (int)$rowDup[0]['N'] : 0;
  if ($nDup > 0) {
    throw new Exception('Ya existe un precio para ese cliente (o base) en este artículo.');
  }

  // INSERT o UPDATE
  if ($artprecio_id > 0){
    // UPDATE
    $sql = "
      UPDATE AMPAR_CAT_ARTPRECIO
         SET ARTPRECIO_ARTICULOID = ?,
             ARTPRECIO_CLIENTEID  = ?,
             ARTPRECIO_SUBTOTAL   = ?,
             ARTPRECIO_IVA        = ?,
             ARTPRECIO_TOTAL      = ?
       WHERE ARTPRECIO_ID = ?
    ";
    $db->execute($sql, [$articulo_id, $cliente_id, $subtotal, $iva, $total, $artprecio_id]);
    $newId = $artprecio_id;
  } else {
    // INSERT (puedes usar RETURNING si tu FB lo soporta)
    // Con tu helper executeconreturning:
    $sql = "
      INSERT INTO AMPAR_CAT_ARTPRECIO
        (ARTPRECIO_ARTICULOID, ARTPRECIO_CLIENTEID, ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL)
      VALUES ( ?, ?, ?, ?, ? )
    ";
    // Devuelve el ID usando RETURNING
    $newId = $db->executeconreturning($sql, 'ARTPRECIO_ID', [$articulo_id, $cliente_id, $subtotal, $iva, $total]);
    if ($newId === false) { throw new Exception('No se pudo insertar el registro.'); }
  }

  $db->commit();
  echo json_encode(['ok'=>true, 'id'=>$newId]);

} catch(Throwable $e){
  if (isset($db)) $db->rollback();
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
