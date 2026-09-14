<?php
require_once("../includes/sesion.php"); require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$ARTCOMPRA_id = isset($_POST['ARTCOMPRA_id']) ? (int)$_POST['ARTCOMPRA_id'] : 0;
$articulo_id  = isset($_POST['articulo_id'])  ? (int)$_POST['articulo_id']  : 0;

// proveedor_id puede ser NULL (precio base)
$proveedor_raw  = isset($_POST['proveedor_id']) ? trim($_POST['proveedor_id']) : '';
$proveedor_id   = ($proveedor_raw === '') ? null : (int)$proveedor_raw;

$subtotal     = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0.0;
$iva          = isset($_POST['iva'])      ? (float)$_POST['iva']      : 0.0;
$total        = isset($_POST['total'])    ? (float)$_POST['total']    : 0.0;

try{
  if ($articulo_id <= 0) throw new Exception('Artículo inválido');
  if ($subtotal < 0 || $iva < 0 || $total < 0) throw new Exception('Montos inválidos');

  $db = new FirebirdConnection(); // por defecto maneja transacción
  // Validar duplicado (único por ARTICULO + PROVEEDOR, considerando NULL como -1)
  $sqlDup = "
    SELECT COUNT(*) AS N
      FROM AMPAR_CAT_ARTCOMPRA
     WHERE ARTCOMPRA_ARTICULOID = ?
       AND COALESCE(ARTCOMPRA_PROVEEDORID, -1) = COALESCE(?, -1)
  ";
  $paramsDup = [$articulo_id, $proveedor_id];
  if ($ARTCOMPRA_id > 0){
    $sqlDup .= " AND ARTCOMPRA_ID <> ? ";
    $paramsDup[] = $ARTCOMPRA_id;
  }
  $rowDup = $db->query($sqlDup, $paramsDup);
  $nDup   = ($rowDup && isset($rowDup[0]['N'])) ? (int)$rowDup[0]['N'] : 0;
  if ($nDup > 0) {
    throw new Exception('Ya existe un precio para ese proveedor (o base) en este artículo.');
  }

  // INSERT o UPDATE
  if ($ARTCOMPRA_id > 0){
    // UPDATE
    $sql = "
      UPDATE AMPAR_CAT_ARTCOMPRA
         SET ARTCOMPRA_ARTICULOID = ?,
             ARTCOMPRA_PROVEEDORID  = ?,
             ARTCOMPRA_SUBTOTAL   = ?,
             ARTCOMPRA_IVA        = ?,
             ARTCOMPRA_TOTAL      = ?
       WHERE ARTCOMPRA_ID = ?
    ";
    $db->execute($sql, [$articulo_id, $proveedor_id, $subtotal, $iva, $total, $ARTCOMPRA_id]);
    $newId = $ARTCOMPRA_id;
  } else {
    // INSERT (puedes usar RETURNING si tu FB lo soporta)
    // Con tu helper executeconreturning:
    $sql = "
      INSERT INTO AMPAR_CAT_ARTCOMPRA
        (ARTCOMPRA_ARTICULOID, ARTCOMPRA_PROVEEDORID, ARTCOMPRA_SUBTOTAL, ARTCOMPRA_IVA, ARTCOMPRA_TOTAL)
      VALUES ( ?, ?, ?, ?, ? )
    ";
    // Devuelve el ID usando RETURNING
    $newId = $db->executeconreturning($sql, 'ARTCOMPRA_ID', [$articulo_id, $proveedor_id, $subtotal, $iva, $total]);
    if ($newId === false) { throw new Exception('No se pudo insertar el registro.'); }
  }

  $db->commit();
  echo json_encode(['ok'=>true, 'id'=>$newId]);

} catch(Throwable $e){
  if (isset($db)) $db->rollback();
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
