<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
require_once("../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

try {
  if (empty($_FILES['file']['tmp_name'])) {
    throw new Exception("Archivo no recibido.");
  }

  @ini_set('max_execution_time', '300');
  @ini_set('memory_limit', '512M');

  $db = new FirebirdConnection();

  $spread = IOFactory::load($_FILES['file']['tmp_name']);
  $sh = $spread->getActiveSheet();
  $lastRow = $sh->getHighestRow();
  $lastCol = $sh->getHighestColumn();
  
  $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);

  $resultado = ['creados'=>0, 'actualizados'=>0, 'errores'=>[]];

  // 1. Mapear columnas de proveedores (Fila 1)
  // Col 1 = A (ID), Col 2 = B (Ref), Col 3 = C (Nombre), Col 4 = D (PRECIO_BASE)
  $mapaProveedores = []; // [ columnIndex => proveedor_id (o null para base) ]
  
  for ($c = 4; $c <= $lastColIndex; $c++) {
      $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
      $headerVal = trim((string)$sh->getCell("{$colLetter}1")->getValue());
      
      if ($headerVal === '') continue; // Columna sin encabezado
      
      if ($c === 4 || strtoupper($headerVal) === 'PRECIO_BASE') {
          $mapaProveedores[$c] = null; // null significa Precio Base
      } else {
          // Buscar proveedor por nombre exacto
          $qProv = $db->query("SELECT PROVEEDOR_ID FROM PROVEEDORES WHERE TRIM(NOMBRE) = ?", [$headerVal]);
          if ($qProv && isset($qProv[0]['PROVEEDOR_ID'])) {
              $mapaProveedores[$c] = (int)$qProv[0]['PROVEEDOR_ID'];
          } else {
              $resultado['errores'][] = "Advertencia: El proveedor '{$headerVal}' (Columna {$colLetter}) no existe en la base de datos. Se ignorarán sus precios.";
          }
      }
  }

  if (empty($mapaProveedores)) {
      throw new Exception("No se detectaron columnas válidas de proveedores o precio base.");
  }

  // 2. Precargar mapeo de Claves a Articulo_ID para hacerlo más rápido
  // Si el catálogo es inmenso esto puede ocupar mucha RAM, pero Firebird lo maneja bien.
  // Alternativa: consultar 1x1. Haremos 1x1 con un caché en PHP.
  $cacheClaves = [];

  $didBegin = false;
  if (method_exists($db,'beginTransaction')) { 
    try { $db->beginTransaction(); $didBegin = true; } catch (Throwable $e) {}
  }

  // 3. Iterar filas
  for ($r = 2; $r <= $lastRow; $r++) {
      $idString = trim((string)$sh->getCell("A$r")->getValue());
      $clave = trim((string)$sh->getCell("B$r")->getValue());
      
      if ($idString === '' && $clave === '') continue; // Fila vacía

      // Buscar Artículo: primero por ID oculto/columna A, si no, por referencia
      if (!isset($cacheClaves[$idString . '_' . $clave])) {
          $articulo_id_found = false;
          
          if ($idString !== '') {
              // Buscar por ID exacto
              $qArt = $db->query("SELECT ARTICULO_ID FROM ARTICULOS WHERE ARTICULO_ID = ?", [(int)$idString]);
              if ($qArt && isset($qArt[0]['ARTICULO_ID'])) {
                  $articulo_id_found = (int)$qArt[0]['ARTICULO_ID'];
              }
          }
          
          if ($articulo_id_found === false && $clave !== '') {
              // Buscar por clave si el ID no funcionó o está vacío
              $qArt = $db->query("
                  SELECT AR.ARTICULO_ID 
                  FROM ARTICULOS AR
                  JOIN CLAVES_ARTICULOS X ON X.ARTICULO_ID = AR.ARTICULO_ID AND X.ROL_CLAVE_ART_ID = 17
                  WHERE UPPER(TRIM(X.CLAVE_ARTICULO)) = UPPER(?)
              ", [$clave]);
              
              if ($qArt && isset($qArt[0]['ARTICULO_ID'])) {
                  $articulo_id_found = (int)$qArt[0]['ARTICULO_ID'];
              }
          }
          
          $cacheClaves[$idString . '_' . $clave] = $articulo_id_found;
      }

      $articulo_id = $cacheClaves[$idString . '_' . $clave];
      if ($articulo_id === false) {
          $resultado['errores'][] = "Fila $r: No se encontró el artículo (ID: '{$idString}', Ref: '{$clave}').";
          continue;
      }

      // Para cada columna mapeada, extraer el precio e insertar/actualizar
      foreach ($mapaProveedores as $colIndex => $proveedor_id) {
          $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
          
          try {
              $val = $sh->getCell("{$colLetter}{$r}")->getCalculatedValue();
          } catch (Throwable $e) {
              $resultado['errores'][] = "Fila $r, Col {$colLetter}: Error al leer celda (posible fórmula).";
              continue;
          }

          if ($val === '' || $val === null) {
              continue; // Celda vacía, no se hace nada
          }

          $subtotal = (float)$val;
          if ($subtotal < 0) {
              $resultado['errores'][] = "Fila $r, Col {$colLetter}: Precio negativo inválido.";
              continue;
          }

          $iva = round($subtotal * 0.16, 2);
          $total = round($subtotal + $iva, 2);

          try {
              // UPSERT
              if ($proveedor_id === null) {
                  $ex = $db->query("
                      SELECT ARTCOMPRA_ID FROM AMPAR_CAT_ARTCOMPRA 
                      WHERE ARTCOMPRA_ARTICULOID = ? AND ARTCOMPRA_PROVEEDORID IS NULL
                  ", [$articulo_id]);
              } else {
                  $ex = $db->query("
                      SELECT ARTCOMPRA_ID FROM AMPAR_CAT_ARTCOMPRA 
                      WHERE ARTCOMPRA_ARTICULOID = ? AND ARTCOMPRA_PROVEEDORID = ?
                  ", [$articulo_id, $proveedor_id]);
              }

              if ($ex && isset($ex[0]['ARTCOMPRA_ID'])) {
                  $db->execute("
                      UPDATE AMPAR_CAT_ARTCOMPRA
                         SET ARTCOMPRA_SUBTOTAL = ?, ARTCOMPRA_IVA = ?, ARTCOMPRA_TOTAL = ?
                       WHERE ARTCOMPRA_ID = ?
                  ", [$subtotal, $iva, $total, $ex[0]['ARTCOMPRA_ID']]);
                  $resultado['actualizados']++;
              } else {
                  $db->execute("
                      INSERT INTO AMPAR_CAT_ARTCOMPRA
                        (ARTCOMPRA_ARTICULOID, ARTCOMPRA_PROVEEDORID, ARTCOMPRA_SUBTOTAL, ARTCOMPRA_IVA, ARTCOMPRA_TOTAL)
                      VALUES (?, ?, ?, ?, ?)
                  ", [$articulo_id, $proveedor_id, $subtotal, $iva, $total]);
                  $resultado['creados']++;
              }
          } catch (Throwable $e) {
              $resultado['errores'][] = "Fila $r, Col {$colLetter}: " . $e->getMessage();
          }
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
