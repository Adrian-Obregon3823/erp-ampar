<?php
class oc{

    function getoc($filtroStatus = '', $ignorarUsuario = false){
        $db = new FirebirdConnection();
        $whereClause = "WHERE 1=1";
        
        if (!($GLOBALS['isAdmin'] ?? false) && !$ignorarUsuario) {
            $whereClause .= " AND OC.OC_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
        }
        
        if ($filtroStatus !== '') {
            if ($filtroStatus === '2') { // Parcial
                // Aquí podrías definir la lógica exacta de parcial, por ejemplo si usas el ID 2 para Parcial.
                $whereClause .= " AND OC.OC_STATUS = " . (int)$filtroStatus;
            } else {
                $whereClause .= " AND OC.OC_STATUS = " . (int)$filtroStatus;
            }
        }
        
        $sql = "
            SELECT OC.*, S.*, SU.NOMBRE, ALM.ALMACEN_NOMBRE, COALESCE(OC.OC_TOTAL, (SELECT SUM(OCDET_PRECIO*OCDET_CANTIDAD) FROM AMPAR_OCDET WHERE OCDET_OCID = OC.OC_ID)) TOTAL
            FROM AMPAR_OC OC
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = OC_STATUS
            LEFT JOIN AMPAR_HIS_EVENTOS ON EVENTO_ID = OC_EVENTOID
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = COALESCE(OC.OC_SUCURSALID, EVENTO_SUCURSALID)
            LEFT JOIN AMPAR_HIS_ALMACEN ALM ON ALM.ALMACEN_ID = OC.OC_ALMACENID
            " . $whereClause . "
            ORDER BY OC.OC_ID DESC
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function getocbyid($ocid){
        $db = new FirebirdConnection();
        $sql = "
            SELECT OC.*, S.*, E.*, SU.NOMBRE SUCURSAL_NOMBRE, ALM.ALMACEN_NOMBRE, COALESCE(OC.OC_TOTAL, (SELECT SUM(OCDET_PRECIO*OCDET_CANTIDAD) FROM AMPAR_OCDET WHERE OCDET_OCID = OC.OC_ID)) TOTAL, OCD.*, AR.NOMBRE ARTICULO_NOMBRE, X.CLAVE_ARTICULO, P.NOMBRE AS PROVEEDOR_NOMBRE
            FROM AMPAR_OC OC
            LEFT JOIN AMPAR_CONF_STATUS S ON STATUS_ID = OC_STATUS
            LEFT JOIN AMPAR_HIS_EVENTOS E ON EVENTO_ID = OC_EVENTOID
            LEFT JOIN ampar_cat_sucursales SU ON SU.SUCURSAL_ID = COALESCE(OC.OC_SUCURSALID, E.EVENTO_SUCURSALID)
            LEFT JOIN AMPAR_HIS_ALMACEN ALM ON ALM.ALMACEN_ID = OC.OC_ALMACENID
            LEFT JOIN PROVEEDORES P ON P.PROVEEDOR_ID = OC.OC_PROVEEDORID
            LEFT JOIN AMPAR_OCDET OCD ON OCDET_OCID = OC_ID
            LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = OCDET_ARTICULOID 
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE OC_ID = ".$ocid."
        ";
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function generarFolioOC($db = null) {
        $ownConn = ($db === null);
        if ($ownConn) {
            $db = new FirebirdConnection();
        }
        $sql = "
            SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(OC_FOLIO FROM 1 FOR 5) AS INTEGER)), 0) + 1, 5, '0') 
            || '-' || CAST(EXTRACT(YEAR FROM CURRENT_DATE) - 2000 AS VARCHAR(2)) AS FOLIO
            FROM AMPAR_OC
        ";
        $res = $db->query($sql);
        $folio = ($res && isset($res[0]['FOLIO'])) ? $res[0]['FOLIO'] : '00001-' . date('y');
        if ($ownConn) {
            $db->close();
        }
        return $folio;
    }

    function getDefaultCostForArticle($articuloId) {
        $db = new FirebirdConnection();
        $articuloId = (int)$articuloId;
        $sql = "
            SELECT FIRST 1 PD.PRECIO_UVEN AS COSTO
            FROM PRECIOS_COMPRA PC
            JOIN PRECIOS_COMPRA_DET PD ON PC.PRECIO_COMPRA_ID = PD.PRECIO_COMPRA_ID
                        WHERE PC.ARTICULO_ID = {$articuloId}
                            AND TRIM(CAST(PC.ES_PROV_PREDET AS VARCHAR(10))) IN ('1', 'S', 'SI', 'TRUE')
        ";
        $res = $db->query($sql);
        if ($res && isset($res[0]['COSTO'])) {
            $cost = (float)$res[0]['COSTO'];
        } else {
            // Fallback to AMPAR_CAT_ARTPRECIO
            $sql2 = "
                SELECT FIRST 1 ARTPRECIO_SUBTOTAL AS COSTO
                FROM AMPAR_CAT_ARTPRECIO
                WHERE ARTPRECIO_ARTICULOID = {$articuloId} AND ARTPRECIO_CLIENTEID IS NULL
            ";
            $res2 = $db->query($sql2);
            $cost = ($res2 && isset($res2[0]['COSTO'])) ? (float)$res2[0]['COSTO'] : 0.00;
        }
        $db->close();
        return $cost;
    }

    function getCostForArticleByProvider($articuloId, $proveedorId) {
        $db = new FirebirdConnection();
        $articuloId = (int)$articuloId;
        $proveedorId = (int)$proveedorId;

        if ($proveedorId > 0) {
            $sql = "
                SELECT FIRST 1 ARTCOMPRA_SUBTOTAL AS COSTO
                FROM AMPAR_CAT_ARTCOMPRA
                WHERE ARTCOMPRA_ARTICULOID = {$articuloId}
                  AND ARTCOMPRA_PROVEEDORID = {$proveedorId}
            ";
            $res = $db->query($sql);
            if ($res && isset($res[0]['COSTO'])) {
                $db->close();
                return (float)$res[0]['COSTO'];
            }
        }
        
        $sql2 = "
            SELECT FIRST 1 ARTCOMPRA_SUBTOTAL AS COSTO
            FROM AMPAR_CAT_ARTCOMPRA
            WHERE ARTCOMPRA_ARTICULOID = {$articuloId}
              AND ARTCOMPRA_PROVEEDORID IS NULL
        ";
        $res2 = $db->query($sql2);
        if ($res2 && isset($res2[0]['COSTO'])) {
            $db->close();
            return (float)$res2[0]['COSTO'];
        }
        
        $db->close();
        return $this->getDefaultCostForArticle($articuloId);
    }

    function getCheapestProviderForArticle($articuloId) {
        $db = new FirebirdConnection();
        $articuloId = (int)$articuloId;

        $sql = "
            SELECT FIRST 1 
                P.ARTCOMPRA_PROVEEDORID AS PROVEEDOR_ID,
                PR.NOMBRE AS PROVEEDOR_NOMBRE,
                P.ARTCOMPRA_SUBTOTAL AS COSTO
            FROM AMPAR_CAT_ARTCOMPRA P
            JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
            WHERE P.ARTCOMPRA_ARTICULOID = {$articuloId}
            ORDER BY P.ARTCOMPRA_SUBTOTAL ASC
        ";
        $res = $db->query($sql);
        $db->close();
        if ($res && isset($res[0])) {
            return $res[0];
        }
        return null;
    }

    function guardarManual($almacenId, $proveedorId, $statusId, $articulos, $requerimientoMaterialId = null, $descuentoGlobalPct = 0) {
        $db = new FirebirdConnection(false); // transaction
        try {
            $folio = $this->generarFolioOC($db);
            
            // Get corresponding sucursal ID from the selected warehouse
            $almacenInfo = $db->query("SELECT ALMACEN_SUCURSAL_MS FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = ?", [$almacenId]);
            $sucursalId = ($almacenInfo && isset($almacenInfo[0]['ALMACEN_SUCURSAL_MS'])) ? (int)$almacenInfo[0]['ALMACEN_SUCURSAL_MS'] : null;

            $headerSubtotalNeto = 0;
            $headerImpuestos = 0;
            $headerTotal = 0;
            
            // Handle multiple requerimientos
            $reqIds = [];
            if (is_array($requerimientoMaterialId)) {
                $reqIds = $requerimientoMaterialId;
            } elseif ($requerimientoMaterialId > 0) {
                $reqIds[] = $requerimientoMaterialId;
            }
            
            $legacyReqId = count($reqIds) > 0 ? $reqIds[0] : null;

            // Insert header with zeros first
            $usuarioIdOC = $GLOBALS['usersesion']['USUARIO_ID'] ?? ($_SESSION['USUARIO_ID'] ?? null);
            $sqlOC = "
                INSERT INTO AMPAR_OC 
                (OC_FECHA, OC_FOLIO, OC_EVENTOID, OC_PROVEEDORID, OC_STATUS, OC_SUBTOTAL, OC_DESCUENTO, OC_IMPUESTOS, OC_TOTAL, OC_SUCURSALID, OC_ALMACENID, OC_REQUERIMIENTOMATERIALID, OC_USUARIOID)
                VALUES 
                (CURRENT_TIMESTAMP, ?, NULL, ?, ?, 0, 0, 0, 0, ?, ?, ?, ?)
            ";
            $ocId = $db->executeconreturning($sqlOC, 'OC_ID', [$folio, $proveedorId, $statusId, $sucursalId, $almacenId, $legacyReqId, $usuarioIdOC ?: null]);
            
            // Insert into AMPAR_HIS_OC_REQ
            if (count($reqIds) > 0) {
                foreach ($reqIds as $rId) {
                    $rId = (int)$rId;
                    if ($rId > 0) {
                        $db->execute("INSERT INTO AMPAR_HIS_OC_REQ (OCREQ_OCID, OCREQ_REQID) VALUES (?, ?)", [$ocId, $rId]);
                    }
                }
            }
            
            foreach ($articulos as $ar) {
                $artId = (int)$ar['articulo_id'];
                $qty = (float)$ar['cantidad'];
                $cost = (float)$ar['costo'];
                $descPct = (float)($ar['descuento_pct'] ?? 0);
                $ivaPct = (float)($ar['iva_pct'] ?? 16);
                
                $descTipo = $ar['desc_tipo'] ?? '';
                $descMotivo = $ar['desc_motivo'] ?? '';
                
                // Math calculations
                $itemSubtotalGross = $qty * $cost;
                $itemDescAmount = $itemSubtotalGross * ($descPct / 100);
                $itemSubtotalNeto = $itemSubtotalGross - $itemDescAmount;
                
                $itemGlobalDescAmount = $itemSubtotalNeto * ($descuentoGlobalPct / 100);
                $itemFinalBase = $itemSubtotalNeto - $itemGlobalDescAmount;
                
                $itemIva = $itemFinalBase * ($ivaPct / 100);
                // Keep OCDET_TOTAL without global discount for row-level display context, 
                // but the overall header will use itemIva which is post-global-discount
                $itemTotalDisplay = $itemSubtotalNeto + ($itemSubtotalNeto * ($ivaPct / 100));
                
                $headerSubtotalNeto += $itemSubtotalNeto;
                $headerImpuestos += $itemIva;
                
                $sqlDet = "
                    INSERT INTO AMPAR_OCDET 
                    (OCDET_OCID, OCDET_ARTICULOID, OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT, OCDET_SUBTOTAL, OCDET_TOTAL, OCDET_DESC_TIPO, OCDET_DESC_MOTIVO)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $db->execute($sqlDet, [$ocId, $artId, $qty, $cost, $descPct, $ivaPct, $itemSubtotalNeto, $itemTotalDisplay, $descTipo, $descMotivo]);
            }
            
            // Calculate global discount amount
            $globalDescAmount = $headerSubtotalNeto * ($descuentoGlobalPct / 100);
            $headerTotal = $headerSubtotalNeto - $globalDescAmount + $headerImpuestos;
            
            // Update header totals
            // Note: the user requested that OC_DESCUENTO stores the global discount. 
            $sqlUpdate = "UPDATE AMPAR_OC SET OC_SUBTOTAL = ?, OC_DESCUENTO = ?, OC_IMPUESTOS = ?, OC_TOTAL = ? WHERE OC_ID = ?";
            $db->execute($sqlUpdate, [$headerSubtotalNeto, $globalDescAmount, $headerImpuestos, $headerTotal, $ocId]);
            
            // Actualizar status de los requerimientos de material vinculados
            if (count($reqIds) > 0) {
                require_once __DIR__ . '/requerimientosmaterial.php';
                $reqMat = new requerimientosmaterial();
                foreach ($reqIds as $rId) {
                    $reqMat->actualizarStatusAutomatico($rId);
                }
            }
            
            $db->commit();
            $db->close();
            
            return [
                'oc_id' => $ocId,
                'folio' => $folio
            ];
        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }

    function editarManual($ocId, $almacenId, $proveedorId, $statusId, $articulos, $requerimientoMaterialId, $descuentoGlobalPct) {
        $db = new FirebirdConnection(false);
        try {
            $almacenId = (int)$almacenId;
            $proveedorId = (int)$proveedorId;
            $statusId = (int)$statusId;
            $ocId = (int)$ocId;
            
            $descuentoGlobalPct = (float)$descuentoGlobalPct;
            if ($descuentoGlobalPct < 0) $descuentoGlobalPct = 0;
            if ($descuentoGlobalPct > 100) $descuentoGlobalPct = 100;
            
            $headerSubtotalNeto = 0;
            $headerImpuestos = 0;
            
            // Handle multiple requerimientos
            $reqIds = [];
            if (is_array($requerimientoMaterialId)) {
                $reqIds = $requerimientoMaterialId;
            } elseif ($requerimientoMaterialId > 0) {
                $reqIds[] = $requerimientoMaterialId;
            }
            
            $legacyReqId = count($reqIds) > 0 ? $reqIds[0] : null;

            // Delete old details and requirements
            $db->execute("DELETE FROM AMPAR_OCDET WHERE OCDET_OCID = ?", [$ocId]);
            $db->execute("DELETE FROM AMPAR_HIS_OC_REQ WHERE OCREQ_OCID = ?", [$ocId]);
            
            // Re-insert into AMPAR_HIS_OC_REQ
            if (count($reqIds) > 0) {
                foreach ($reqIds as $rId) {
                    $rId = (int)$rId;
                    if ($rId > 0) {
                        $db->execute("INSERT INTO AMPAR_HIS_OC_REQ (OCREQ_OCID, OCREQ_REQID) VALUES (?, ?)", [$ocId, $rId]);
                    }
                }
            }
            
            foreach ($articulos as $ar) {
                $artId = (int)$ar['articulo_id'];
                $qty = (float)$ar['cantidad'];
                $cost = (float)$ar['costo'];
                $descPct = (float)($ar['descuento_pct'] ?? 0);
                $ivaPct = (float)($ar['iva_pct'] ?? 16);
                
                $descTipo = $ar['desc_tipo'] ?? '';
                $descMotivo = $ar['desc_motivo'] ?? '';
                
                // Math calculations
                $itemSubtotalGross = $qty * $cost;
                $itemDescAmount = $itemSubtotalGross * ($descPct / 100);
                $itemSubtotalNeto = $itemSubtotalGross - $itemDescAmount;
                
                $itemGlobalDescAmount = $itemSubtotalNeto * ($descuentoGlobalPct / 100);
                $itemFinalBase = $itemSubtotalNeto - $itemGlobalDescAmount;
                
                $itemIva = $itemFinalBase * ($ivaPct / 100);
                $itemTotalDisplay = $itemSubtotalNeto + ($itemSubtotalNeto * ($ivaPct / 100));
                
                $headerSubtotalNeto += $itemSubtotalNeto;
                $headerImpuestos += $itemIva;
                
                $sqlDet = "
                    INSERT INTO AMPAR_OCDET 
                    (OCDET_OCID, OCDET_ARTICULOID, OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT, OCDET_SUBTOTAL, OCDET_TOTAL, OCDET_DESC_TIPO, OCDET_DESC_MOTIVO)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $db->execute($sqlDet, [$ocId, $artId, $qty, $cost, $descPct, $ivaPct, $itemSubtotalNeto, $itemTotalDisplay, $descTipo, $descMotivo]);
            }
            
            // Calculate global discount amount
            $globalDescAmount = $headerSubtotalNeto * ($descuentoGlobalPct / 100);
            $headerTotal = $headerSubtotalNeto - $globalDescAmount + $headerImpuestos;
            
            // Update header
            $sqlUpdate = "UPDATE AMPAR_OC SET OC_PROVEEDORID = ?, OC_STATUS = ?, OC_SUBTOTAL = ?, OC_DESCUENTO = ?, OC_IMPUESTOS = ?, OC_TOTAL = ?, OC_ALMACENID = ?, OC_REQUERIMIENTOMATERIALID = ? WHERE OC_ID = ?";
            $db->execute($sqlUpdate, [$proveedorId, $statusId, $headerSubtotalNeto, $globalDescAmount, $headerImpuestos, $headerTotal, $almacenId, $legacyReqId, $ocId]);
            
            // Actualizar status de los requerimientos de material vinculados
            if (count($reqIds) > 0) {
                require_once __DIR__ . '/requerimientosmaterial.php';
                $reqMat = new requerimientosmaterial();
                foreach ($reqIds as $rId) {
                    $reqMat->actualizarStatusAutomatico($rId);
                }
            }
            
            $db->commit();
            $db->close();
            
            return ['oc_id' => $ocId, 'folio' => ''];
            
        } catch (Throwable $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }

    function getSugerenciasCompra($sucursalId) {
        $db = new FirebirdConnection();
        $sucursalId = (int)$sucursalId;
        
        $sql = "
            SELECT 
                AR.ARTICULO_ID AS ID, 
                AR.NOMBRE AS ARTICULO_NOMBRE, 
                X.CLAVE_ARTICULO,
                COALESCE(MIN(PROV.PROVEEDOR_ID), (SELECT FIRST 1 PROVEEDOR_ID FROM PROVEEDORES WHERE ESTATUS = 'N')) AS PROVEEDOR_ID,
                COALESCE(MIN(PROV.NOMBRE), 'Proveedor No Asignado') AS PROVEEDOR_NOMBRE,
                COALESCE(SUM(MINS.INVENTARIO_MINIMO), 0) AS STOCK_MINIMO,
                COALESCE((
                    SELECT COUNT(*) 
                    FROM AMPAR_HIS_STOCK S
                    JOIN AMPAR_HIS_ALMACEN A2 ON S.STOCK_ALMACENIDACTUAL = A2.ALMACEN_ID
                    WHERE S.STOCK_ARTICULOID = AR.ARTICULO_ID 
                      AND S.STOCK_STOCKSTATUSID = 1
                      AND A2.ALMACEN_SUCURSAL_MS = {$sucursalId}
                      AND A2.ALMACEN_TIPOALMACEN IN (1, 2)
                ), 0) AS STOCK_ACTUAL,
                COALESCE((
                    SELECT SUM(OD.OCDET_CANTIDAD) 
                    FROM AMPAR_OCDET OD
                    JOIN AMPAR_OC O ON OD.OCDET_OCID = O.OC_ID
                    WHERE OD.OCDET_ARTICULOID = AR.ARTICULO_ID 
                      AND O.OC_SUCURSALID = {$sucursalId}
                      AND O.OC_STATUS IN (2, 4, 7, 9)
                ), 0) AS STOCK_TRANSITO
            FROM NIVELES_ARTICULOS MINS
            JOIN AMPAR_HIS_ALMACEN A ON MINS.ALMACEN_ID = A.ALMACEN_ID
            JOIN ARTICULOS AR ON MINS.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            LEFT JOIN PRECIOS_COMPRA PC ON PC.ARTICULO_ID = AR.ARTICULO_ID AND TRIM(CAST(PC.ES_PROV_PREDET AS VARCHAR(10))) IN ('1', 'S', 'SI', 'TRUE')
            LEFT JOIN PROVEEDORES PROV ON PC.PROVEEDOR_ID = PROV.PROVEEDOR_ID
            WHERE A.ALMACEN_SUCURSAL_MS = {$sucursalId}
              AND A.ALMACEN_TIPOALMACEN IN (1, 2)
              AND AR.ESTATUS = 'A'
            GROUP BY AR.ARTICULO_ID, AR.NOMBRE, X.CLAVE_ARTICULO
        ";
        
        $result = $db->query($sql);
        $db->close();
        
        $sugerencias = [];
        if (is_array($result)) {
            foreach ($result as $r) {
                $min = (float)$r['STOCK_MINIMO'];
                $act = (float)$r['STOCK_ACTUAL'];
                $trn = (float)$r['STOCK_TRANSITO'];
                if (($act + $trn) < $min) {
                    $sugerir = $min - $act - $trn;
                    $r['CANTIDAD_SUGERIDA'] = $sugerir;
                    $r['COSTO_UNITARIO'] = $this->getDefaultCostForArticle($r['ID']);
                    $sugerencias[] = $r;
                }
            }
        }
        return $sugerencias;
    }

    function crearBorradoresSugeridos($sucursalId) {
        $sugerencias = $this->getSugerenciasCompra($sucursalId);
        if (empty($sugerencias)) {
            return 0;
        }
        
        $porProveedor = [];
        foreach ($sugerencias as $s) {
            $provId = (int)$s['PROVEEDOR_ID'];
            if ($provId <= 0) {
                continue;
            }
            if (!isset($porProveedor[$provId])) {
                $porProveedor[$provId] = [];
            }
            $porProveedor[$provId][] = $s;
        }
        
        $creados = 0;
        $db = new FirebirdConnection(false);
        
        try {
            foreach ($porProveedor as $provId => $items) {
                $folio = $this->generarFolioOC($db);
                
                $subtotal = 0;
                $iva = 0;
                $total = 0;
                
                // For suggestions, we'll choose the first warehouse of the branch as default target warehouse
                $almRes = $db->query("SELECT ALMACEN_ID FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_SUCURSAL_MS = ? AND ALMACEN_TIPOALMACEN IN (1,2) ORDER BY ALMACEN_ID", [$sucursalId]);
                $almacenId = ($almRes && isset($almRes[0]['ALMACEN_ID'])) ? (int)$almRes[0]['ALMACEN_ID'] : null;

                $usuarioIdOC = $GLOBALS['usersesion']['USUARIO_ID'] ?? ($_SESSION['USUARIO_ID'] ?? null);
                $sqlOC = "
                    INSERT INTO AMPAR_OC 
                    (OC_FECHA, OC_FOLIO, OC_EVENTOID, OC_PROVEEDORID, OC_STATUS, OC_SUBTOTAL, OC_DESCUENTO, OC_IMPUESTOS, OC_TOTAL, OC_SUCURSALID, OC_ALMACENID, OC_USUARIOID)
                    VALUES 
                    (CURRENT_TIMESTAMP, '{$folio}', NULL, {$provId}, 1, 0, 0, 0, 0, {$sucursalId}, " . ($almacenId ? $almacenId : "NULL") . ", " . ($usuarioIdOC ? (int)$usuarioIdOC : "NULL") . ")
                ";
                $ocId = $db->executeconreturning($sqlOC, 'OC_ID');
                
                foreach ($items as $item) {
                    $artId = (int)$item['ID'];
                    $qty = (float)$item['CANTIDAD_SUGERIDA'];
                    $cost = (float)$item['COSTO_UNITARIO'];
                    
                    $itemSubtotal = $qty * $cost;
                    $itemIva = $itemSubtotal * 0.16;
                    $itemTotal = $itemSubtotal + $itemIva;
                    
                    $subtotal += $itemSubtotal;
                    $iva += $itemIva;
                    $total += $itemTotal;
                    
                    $sqlDet = "
                        INSERT INTO AMPAR_OCDET 
                        (OCDET_OCID, OCDET_ARTICULOID, OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT, OCDET_SUBTOTAL, OCDET_TOTAL)
                        VALUES 
                        ({$ocId}, {$artId}, {$qty}, {$cost}, 0, 16.00, {$itemSubtotal}, {$itemTotal})
                    ";
                    $db->execute($sqlDet);
                }
                
                $sqlUpdate = "
                    UPDATE AMPAR_OC 
                    SET OC_SUBTOTAL = {$subtotal},
                        OC_IMPUESTOS = {$iva},
                        OC_TOTAL = {$total}
                    WHERE OC_ID = {$ocId}
                ";
                $db->execute($sqlUpdate);
                
                $creados++;
            }
            $db->commit();
            $db->close();
            return $creados;
        } catch (Exception $e) {
            $db->rollback();
            $db->close();
            throw $e;
        }
    }
}
?>