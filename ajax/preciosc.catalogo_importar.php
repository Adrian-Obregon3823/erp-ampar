<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
require_once("../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

try {
  // Validar archivo
  if (empty($_FILES['file']['tmp_name'])) {
    throw new Exception("Archivo no recibido.");
  }

  // Recomendado para archivos grandes
  @ini_set('max_execution_time', '300');
  @ini_set('memory_limit', '512M');

  $db = new FirebirdConnection();

  // Cargar Excel
  $spread = IOFactory::load($_FILES['file']['tmp_name']);
  $sh = $spread->getActiveSheet();
  $lastRow = $sh->getHighestRow();

  $resultado = ['creados'=>0, 'actualizados'=>0, 'errores'=>[]];

  // Iniciar transacción
  $didBegin = false;
  if (method_exists($db,'beginTransaction')) { 
    try { $db->beginTransaction(); $didBegin = true; } catch (Throwable $e) {}
  }

  // Iterar filas (desde 2)
  for ($r = 2; $r <= $lastRow; $r++) {
    try {
      $ARTCOMPRA_ID = trim((string)$sh->getCell("A$r")->getValue());       // puede venir vacío
      $ARTICULO_ID  = (int)trim((string)$sh->getCell("B$r")->getValue());  // obligatorio
      $TIPO         = strtoupper(trim((string)$sh->getCell("E$r")->getValue())); // BASE o PROVEEDOR
      $PRO_RAW      = trim((string)$sh->getCell("F$r")->getValue());       // PROVEEDOR_ID para PROVEEDOR
      $SUBTOTAL     = $sh->getCell("H$r")->getCalculatedValue();
      $IVA          = $sh->getCell("I$r")->getCalculatedValue();
      $TOTAL        = $sh->getCell("J$r")->getCalculatedValue();
    } catch (Throwable $e) {
      $resultado['errores'][] = "Fila $r: Error al leer datos (posible fórmula inválida). Detalles: " . $e->getMessage();
      continue;
    }

    // Fila vacía => saltar
    if ($ARTCOMPRA_ID==='' && $ARTICULO_ID===0 && $TIPO==='' && $PRO_RAW==='' && $SUBTOTAL==='' && $IVA==='' && $TOTAL==='') {
      continue;
    }

    // Validaciones
    if ($ARTICULO_ID <= 0) { $resultado['errores'][] = "Fila $r: ARTICULO_ID inválido."; continue; }
    if ($TIPO !== 'BASE' && $TIPO !== 'PROVEEDOR') { $resultado['errores'][] = "Fila $r: TIPO debe ser BASE o PROVEEDOR."; continue; }

    $PROVEEDOR_ID = null;
    if ($TIPO === 'PROVEEDOR') {
      if ($PRO_RAW === '') { $resultado['errores'][] = "Fila $r: Falta PROVEEDOR_ID para TIPO=PROVEEDOR."; continue; }
      if (!ctype_digit($PRO_RAW)) { $resultado['errores'][] = "Fila $r: PROVEEDOR_ID debe ser numérico."; continue; }
      $PROVEEDOR_ID = (int)$PRO_RAW;
    }

    $SUBTOTAL = ($SUBTOTAL === '' || $SUBTOTAL === null) ? 0 : (float)$SUBTOTAL;
    $IVA      = ($IVA === '' || $IVA === null) ? 0 : (float)$IVA;
    $TOTAL    = ($TOTAL === '' || $TOTAL === null) ? 0 : (float)$TOTAL;

    if ($SUBTOTAL < 0 || $IVA < 0 || $TOTAL < 0) {
      $resultado['errores'][] = "Fila $r: Montos negativos no permitidos.";
      continue;
    }

    try {
      if ($ARTCOMPRA_ID !== '') {
        // UPDATE por ID (preserva IDs)
        $db->execute("
          UPDATE AMPAR_CAT_ARTCOMPRA
             SET ARTCOMPRA_ARTICULOID = ?,
                 ARTCOMPRA_PROVEEDORID  = ?,
                 ARTCOMPRA_SUBTOTAL   = ?,
                 ARTCOMPRA_IVA        = ?,
                 ARTCOMPRA_TOTAL      = ?
           WHERE ARTCOMPRA_ID = ?
        ", [ $ARTICULO_ID, $PROVEEDOR_ID, $SUBTOTAL, $IVA, $TOTAL, (int)$ARTCOMPRA_ID ]);
        $resultado['actualizados']++;
      } else {
        // UPSERT por (ARTICULO_ID, PROVEEDOR_ID) considerando NULL como base
        if ($PROVEEDOR_ID === null) {
          $ex = $db->query("
            SELECT ARTCOMPRA_ID FROM AMPAR_CAT_ARTCOMPRA
             WHERE ARTCOMPRA_ARTICULOID = ?
               AND ARTCOMPRA_PROVEEDORID IS NULL
          ", [ $ARTICULO_ID ]);
        } else {
          $ex = $db->query("
            SELECT ARTCOMPRA_ID FROM AMPAR_CAT_ARTCOMPRA
             WHERE ARTCOMPRA_ARTICULOID = ?
               AND ARTCOMPRA_PROVEEDORID = ?
          ", [ $ARTICULO_ID, $PROVEEDOR_ID ]);
        }

        if ($ex && isset($ex[0]['ARTCOMPRA_ID'])) {
          $db->execute("
            UPDATE AMPAR_CAT_ARTCOMPRA
               SET ARTCOMPRA_SUBTOTAL = ?, ARTCOMPRA_IVA = ?, ARTCOMPRA_TOTAL = ?
             WHERE ARTCOMPRA_ID = ?
          ", [ $SUBTOTAL, $IVA, $TOTAL, $ex[0]['ARTCOMPRA_ID'] ]);
          $resultado['actualizados']++;
        } else {
          $db->execute("
            INSERT INTO AMPAR_CAT_ARTCOMPRA
              (ARTCOMPRA_ARTICULOID, ARTCOMPRA_PROVEEDORID, ARTCOMPRA_SUBTOTAL, ARTCOMPRA_IVA, ARTCOMPRA_TOTAL)
            VALUES (?, ?, ?, ?, ?)
          ", [ $ARTICULO_ID, $PROVEEDOR_ID, $SUBTOTAL, $IVA, $TOTAL ]);
          $resultado['creados']++;
        }
      }
    } catch (Throwable $e) {
      $resultado['errores'][] = "Fila $r: ".$e->getMessage();
      continue;
    }
  }

  if ($didBegin && method_exists($db,'commit')) { $db->commit(); }
  $db->close();

  echo json_encode(['ok'=>true, 'resultado'=>$resultado], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($db) && method_exists($db,'rollback')) { $db->rollback(); }
  if (isset($db)) $db->close();
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
