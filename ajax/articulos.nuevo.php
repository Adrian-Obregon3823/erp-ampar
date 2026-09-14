<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    
    // Nuevos campos
    $unidad_venta = isset($_POST['unidad_venta']) ? trim($_POST['unidad_venta']) : '';
    $unidad_compra = isset($_POST['unidad_compra']) ? trim($_POST['unidad_compra']) : '';
    $contenido_unidad_compra = isset($_POST['contenido_unidad_compra']) ? (float)$_POST['contenido_unidad_compra'] : 1;
    $clave_sat = isset($_POST['clave_sat']) ? trim($_POST['clave_sat']) : '';
    
    $es_almacenable = isset($_POST['es_almacenable']) ? 'S' : 'N';
    $es_juego = isset($_POST['es_juego']) ? 'S' : 'N';
    $es_peso_variable = isset($_POST['es_peso_variable']) ? 'S' : 'N';
    $peso_unitario = isset($_POST['peso_unitario']) ? (float)$_POST['peso_unitario'] : 0;
    
    $pedimentos = isset($_POST['pedimentos']) ? trim($_POST['pedimentos']) : 'N';
    $es_siempre_importado = ($pedimentos === 'S') ? 'S' : 'N';
    $es_importado = ($pedimentos === 'S') ? 'S' : 'N';
    
    $pctje_arancel = isset($_POST['pctje_arancel']) ? (float)$_POST['pctje_arancel'] : 0;
    $seguimiento = isset($_POST['seguimiento']) ? trim($_POST['seguimiento']) : 'N';
    $dias_garantia = isset($_POST['dias_garantia']) ? (int)$_POST['dias_garantia'] : 0;
    $estatus = isset($_POST['estatus']) ? trim($_POST['estatus']) : 'A';

    // Precios Base
    $precioBaseVenta = isset($_POST['precio_base_venta']) ? (float)$_POST['precio_base_venta'] : 0;
    $precioBaseCompra = isset($_POST['precio_base_compra']) ? (float)$_POST['precio_base_compra'] : 0;

    // Arreglos de precios específicos
    $clientes = isset($_POST['clientes']) ? $_POST['clientes'] : [];
    $proveedores = isset($_POST['proveedores']) ? $_POST['proveedores'] : [];

    if ($nombre === '' || $categoria_id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios (Nombre o Categoría).']);
        exit;
    }

    $db = new FirebirdConnection(false); // Transacción manual

    // 1. Obtener el siguiente ID de artículo compatible con Microsip
    $articuloId = 0;
    try {
        // Intentar generador singular
        $resId = $db->query("SELECT GEN_ID(GEN_ARTICULO_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
        $articuloId = (int)$resId[0]['NEXT_ID'];
    } catch (Exception $e1) {
        try {
            // Intentar generador plural
            $resId = $db->query("SELECT GEN_ID(GEN_ARTICULOS_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
            $articuloId = (int)$resId[0]['NEXT_ID'];
        } catch (Exception $e2) {
            // Fallback: MAX(ARTICULO_ID) + 1
            $resId = $db->query("SELECT COALESCE(MAX(ARTICULO_ID), 0) + 1 AS NEXT_ID FROM ARTICULOS");
            $articuloId = (int)$resId[0]['NEXT_ID'];
        }
    }

    if ($articuloId <= 0) {
        throw new Exception("Error al generar ID para el artículo.");
    }

    // 2. Insertar el artículo manualmente
    $sqlArt = "INSERT INTO ARTICULOS (
                ARTICULO_ID, NOMBRE, LINEA_ARTICULO_ID, ESTATUS, ES_ALMACENABLE,
                UNIDAD_VENTA, UNIDAD_COMPRA, CONTENIDO_UNIDAD_COMPRA,
                ES_JUEGO, ES_PESO_VARIABLE, PESO_UNITARIO,
                ES_SIEMPRE_IMPORTADO, ES_IMPORTADO, PCTJE_ARANCEL,
                SEGUIMIENTO, DIAS_GARANTIA, PERMITIR_AGREGAR_COMP
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $db->execute($sqlArt, [
        $articuloId, $nombre, $categoria_id, $estatus, $es_almacenable,
        $unidad_venta, $unidad_compra, $contenido_unidad_compra,
        $es_juego, $es_peso_variable, $peso_unitario,
        $es_siempre_importado, $es_importado, $pctje_arancel,
        $seguimiento, $dias_garantia, null
    ]);

    // 3. Insertar clave/referencia (si se proporcionó)
    if ($clave !== '') {
        $claveId = 0;
        try {
            $resIdC = $db->query("SELECT GEN_ID(GEN_CLAVE_ARTICULO_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
            $claveId = (int)$resIdC[0]['NEXT_ID'];
        } catch (Exception $e1) {
            try {
                $resIdC = $db->query("SELECT GEN_ID(GEN_CLAVES_ARTICULOS_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
                $claveId = (int)$resIdC[0]['NEXT_ID'];
            } catch (Exception $e2) {
                $resIdC = $db->query("SELECT COALESCE(MAX(CLAVE_ARTICULO_ID), 0) + 1 AS NEXT_ID FROM CLAVES_ARTICULOS");
                $claveId = (int)$resIdC[0]['NEXT_ID'];
            }
        }

        $sqlClave = "INSERT INTO CLAVES_ARTICULOS (CLAVE_ARTICULO_ID, ARTICULO_ID, CLAVE_ARTICULO, ROL_CLAVE_ART_ID) VALUES (?, ?, ?, 17)";
        $db->execute($sqlClave, [$claveId, $articuloId, $clave]);
    }

    // 3.5 Insertar Clave SAT
    if ($clave_sat !== '') {
        $daId = 0;
        try {
            $resIdDA = $db->query("SELECT GEN_ID(GEN_DATOS_ADICIONALES_ID, 1) AS NEXT_ID FROM RDB\$DATABASE");
            $daId = (int)$resIdDA[0]['NEXT_ID'];
        } catch (Exception $e1) {
            $resIdDA = $db->query("SELECT COALESCE(MAX(DATOS_ADICIONALES_ID), 0) + 1 AS NEXT_ID FROM DATOS_ADICIONALES");
            $daId = (int)$resIdDA[0]['NEXT_ID'];
        }

        $sqlSAT = "INSERT INTO DATOS_ADICIONALES (DATOS_ADICIONALES_ID, ELEM_ID, CLAVE, NOM_TABLA, TIPO_REG) VALUES (?, ?, ?, 'ARTICULOS', 'O')";
        $db->execute($sqlSAT, [$daId, $articuloId, $clave_sat]);
    }

    // 4. Precios de Venta (AMPAR_CAT_ARTPRECIO)
    // Precio Base
    if ($precioBaseVenta >= 0) {
        $ivaVenta = $precioBaseVenta * 0.16;
        $totalVenta = $precioBaseVenta + $ivaVenta;
        $sqlPV = "INSERT INTO AMPAR_CAT_ARTPRECIO (ARTPRECIO_ARTICULOID, ARTPRECIO_CLIENTEID, ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL) VALUES (?, NULL, ?, ?, ?)";
        $db->execute($sqlPV, [$articuloId, $precioBaseVenta, $ivaVenta, $totalVenta]);
    }

    // Precios por Cliente
    if (is_array($clientes)) {
        $sqlPV = "INSERT INTO AMPAR_CAT_ARTPRECIO (ARTPRECIO_ARTICULOID, ARTPRECIO_CLIENTEID, ARTPRECIO_SUBTOTAL, ARTPRECIO_IVA, ARTPRECIO_TOTAL) VALUES (?, ?, ?, ?, ?)";
        foreach ($clientes as $c) {
            $clienteId = (int)($c['id'] ?? 0);
            $precio = (float)($c['precio'] ?? 0);
            if ($clienteId > 0) {
                $iva = $precio * 0.16;
                $total = $precio + $iva;
                $db->execute($sqlPV, [$articuloId, $clienteId, $precio, $iva, $total]);
            }
        }
    }

    // 5. Precios de Compra (AMPAR_CAT_ARTCOMPRA)
    // Precio Base de Compra
    if ($precioBaseCompra >= 0) {
        $ivaCompra = $precioBaseCompra * 0.16;
        $totalCompra = $precioBaseCompra + $ivaCompra;
        $sqlPC = "INSERT INTO AMPAR_CAT_ARTCOMPRA (ARTCOMPRA_ARTICULOID, ARTCOMPRA_PROVEEDORID, ARTCOMPRA_SUBTOTAL, ARTCOMPRA_IVA, ARTCOMPRA_TOTAL) VALUES (?, NULL, ?, ?, ?)";
        $db->execute($sqlPC, [$articuloId, $precioBaseCompra, $ivaCompra, $totalCompra]);
    }

    // Precios por Proveedor
    if (is_array($proveedores)) {
        $sqlPC = "INSERT INTO AMPAR_CAT_ARTCOMPRA (ARTCOMPRA_ARTICULOID, ARTCOMPRA_PROVEEDORID, ARTCOMPRA_SUBTOTAL, ARTCOMPRA_IVA, ARTCOMPRA_TOTAL) VALUES (?, ?, ?, ?, ?)";
        foreach ($proveedores as $p) {
            $proveedorId = (int)($p['id'] ?? 0);
            $precio = (float)($p['precio'] ?? 0);
            if ($proveedorId > 0) {
                $iva = $precio * 0.16;
                $total = $precio + $iva;
                $db->execute($sqlPC, [$articuloId, $proveedorId, $precio, $iva, $total]);
            }
        }
    }

    // Confirmar todo
    $db->commit();
    $db->close();

    echo json_encode(['ok' => true, 'msg' => 'Artículo creado correctamente.', 'id' => $articuloId]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
        $db->close();
    }
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
