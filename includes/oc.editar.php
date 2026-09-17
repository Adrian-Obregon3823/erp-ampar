<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$ocid = isset($_GET['ocid']) ? (int)base64_decode($_GET['ocid']) : 0;
if ($ocid == 0) {
    echo "ID Inválido";
    exit;
}
$oc = new oc();
$resOC = $oc->getocbyid($ocid);
if (!$resOC || count($resOC) == 0) {
    echo "Orden de Compra no encontrada";
    exit;
}
$ocData = $resOC[0];
$articulosPrecargados = [];
foreach ($resOC as $r) {
    if (!empty($r['OCDET_ARTICULOID'])) {
        $articulosPrecargados[] = [
            'id' => $r['OCDET_ARTICULOID'],
            'clave' => $r['CLAVE_ARTICULO'] ?? '',
            'nombre' => $r['ARTICULO_NOMBRE'] ?? '',
            'cantidad' => $r['OCDET_CANTIDAD'] ?? 1,
            'costo' => $r['OCDET_PRECIO'] ?? 0,
            'descPct' => $r['OCDET_DESCUENTO_PCT'] ?? 0,
            'ivaPct' => $r['OCDET_IVA_PCT'] ?? 16,
            'descTipo' => $r['OCDET_DESC_TIPO'] ?? '',
            'descMotivo' => $r['OCDET_DESC_MOTIVO'] ?? ''
        ];
    }
}
$reqIds = [];
$db = new FirebirdConnection();
$reqRes = $db->query("SELECT OCREQ_REQID FROM AMPAR_HIS_OC_REQ WHERE OCREQ_OCID = " . $ocid);
if ($reqRes) {
    foreach ($reqRes as $req) {
        $reqIds[] = $req['OCREQ_REQID'];
    }
}
$db->close();
?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }

    .stock-badge-container {
        display: none;
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #e9ecef;
    }

    .stock-badge {
        font-size: 0.9em;
        font-weight: bold;
        margin-right: 15px;
        display: inline-block;
        margin-bottom: 4px;
    }

    #ocArticulosBody tr td,
    .table-responsive th {
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .table-oc-items {
            min-width: 650px;
        }
    }

    @media (max-width: 767px) {
        .table-oc-items {
            width: 100% !important;
            min-width: 0 !important;
            margin-bottom: 0 !important;
            border: none !important;
        }
        .table-oc-items thead {
            display: none !important;
        }
        .table-oc-items tbody,
        .table-oc-items tr,
        .table-oc-items td {
            display: block !important;
            width: 100% !important;
        }
        .table-oc-items tr.articulo-row {
            margin-bottom: 1rem !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
            padding: 0.75rem !important;
            background-color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06) !important;
        }
        .table-oc-items tr.articulo-row td {
            text-align: right !important;
            padding: 0.4rem 0.5rem !important;
            border: none !important;
            border-bottom: 1px solid #f1f3f5 !important;
            position: relative !important;
            padding-left: 45% !important;
            white-space: normal !important;
            font-size: 0.85rem !important;
        }
        .table-oc-items tr.articulo-row td:last-child {
            border-bottom: none !important;
            text-align: center !important;
            padding-left: 0.5rem !important;
            padding-top: 0.75rem !important;
        }
        .table-oc-items tr.articulo-row td::before {
            content: attr(data-label);
            position: absolute;
            left: 0.5rem;
            top: 0.4rem;
            width: 40%;
            font-weight: bold;
            color: #495057;
            text-align: left;
        }
        #ocArticulosBody tr td button.btnQuitarArticuloOC {
            width: 100% !important;
            padding: 0.4rem !important;
            font-size: 0.85rem !important;
        }
    }
</style>
<div class="col-12">
    <div class="card border-0">
        <div class="card-body p-3 p-md-4">
            <form class="forms-sample" id="form-oc-nueva">
                <input type="hidden" id="ocid" name="ocid" value="<?=$ocid?>">
                <input type="hidden" id="statusid" name="statusid" value="<?=$ocData['OC_STATUS']?>">

                <div class="row">
                    <!-- Almacen Selection -->
                    <div class="col-md-6 mb-3">
                        <label for="ocalmacen">Almacén <span class="text-danger">*</span></label>
                        <select class="form-control" id="ocalmacen" name="almacenid" required>
                            <option value="">Selecciona Almacén</option>
                        </select>
                    </div>

                    <!-- Proveedor Selection -->
                    <div class="col-md-6 mb-3">
                        <label for="ocproveedor">Proveedor <span class="text-danger">*</span></label>
                        <select class="form-control" id="ocproveedor" name="proveedorid" required>
                            <option value="">Selecciona Proveedor</option>
                        </select>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="chkSoloOptimos" checked>
                            <label class="custom-control-label text-muted" for="chkSoloOptimos" style="font-size: 0.85rem;">Solo cargar artículos donde este proveedor sea el más barato</label>
                        </div>
                    </div>
                </div>

                <div class="row" id="sectionRequerimientos" style="display:none;">
                    <div class="col-md-12 mb-3">
                        <label>Seleccionar Requerimientos de Material (opcional)</label>
                        <ul class="nav nav-tabs" id="reqTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="req-sucursal-tab" data-toggle="tab" href="#req-sucursal" role="tab">Por Sucursal</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="req-general-tab" data-toggle="tab" href="#req-general" role="tab">General (Todos)</a>
                            </li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3" id="reqTabsContent">
                            <div class="tab-pane fade show active" id="req-sucursal" role="tabpanel">
                                <div id="listaReqSucursal" style="max-height: 200px; overflow-y: auto;">Selecciona un almacén primero</div>
                            </div>
                            <div class="tab-pane fade" id="req-general" role="tabpanel">
                                <div id="listaReqGeneral" style="max-height: 200px; overflow-y: auto;">Cargando...</div>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2">Al seleccionar uno o varios requerimientos se sumarán sus artículos pendientes de comprar y se cargarán en la tabla inferior.</small>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Article Search & Row Form -->
                <div class="row align-items-end mb-3">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <label for="ocarticulo">Buscar Artículo <span class="text-danger">*</span></label>
                        <input type="hidden" id="oc_idarticulo">
                        <input type="hidden" id="oc_cvearticulo">
                        <input type="hidden" id="oc_mejor_proveedor_id">
                        <input type="hidden" id="oc_mejor_proveedor_nombre">
                        <input type="hidden" id="oc_mejor_proveedor_costo">
                        <input type="text" class="form-control" id="ocarticulo" placeholder="Escribe clave o nombre del artículo...">
                    </div>
                    <div class="col-6 col-md mb-2 mb-md-0">
                        <label for="oc_cantidad">Cantidad</label>
                        <input type="number" step="any" class="form-control" id="oc_cantidad" value="1">
                    </div>
                    <div class="col-6 col-md mb-2 mb-md-0">
                        <label for="oc_costo">Costo Unitario</label>
                        <input type="number" step="0.01" class="form-control" id="oc_costo" value="0.00">
                        <small id="lblMejorPrecio" class="text-success" style="display:none; font-size:0.75rem; line-height: 1.2; padding-top: 2px;"></small>
                    </div>
                    <div class="col-6 col-md mb-2 mb-md-0">
                        <label for="oc_descuento">Desc. %</label>
                        <input type="number" step="any" class="form-control" id="oc_descuento" value="0.00">
                    </div>
                    <div class="col-6 col-md mb-2 mb-md-0">
                        <label for="oc_iva">IVA %</label>
                        <input type="number" step="any" class="form-control" id="oc_iva" value="16.00">
                    </div>
                    <div class="col-12 col-md-2 mt-1 mt-md-0">
                        <button type="button" class="btn btn-warning w-100 font-weight-bold shadow-sm" id="btnAgregarArticuloOC" style="padding: 10px 0;">
                            <i class="mdi mdi-plus mr-1"></i> <span class="d-inline d-md-none">Agregar Artículo</span><span class="d-none d-md-inline">Agregar</span>
                        </button>
                    </div>
                </div>

                <!-- Stock Badges Container -->
                <div class="stock-badge-container mb-3" id="ocStockInfo">
                    <span class="text-muted mr-3">Existencias en Almacén:</span>
                    <span class="stock-badge text-primary" id="lblStockActual">Actual: -</span>
                    <span class="stock-badge text-warning" id="lblStockMinimo">Mínimo: -</span>
                    <span class="stock-badge text-info" id="lblStockTransito">En Tránsito: -</span>
                </div>

                <!-- Table of Added Items -->
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-hover align-middle table-oc-items">
                        <thead>
                            <tr>
                                <th class="px-1">#</th>
                                <th class="px-1">Clave</th>
                                <th>Artículo</th>
                                <th class="text-center px-1">Cant.</th>
                                <th class="px-1">Costo</th>
                                <th class="px-1">Desc</th>
                                <th class="px-1">IVA</th>
                                <th class="px-1">Subt.</th>
                                <th class="text-right px-1">Total</th>
                                <th class="px-1"></th>
                            </tr>
                        </thead>
                        <tbody id="ocArticulosBody">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3" id="rowEmptyPlaceholder">No hay artículos agregados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- Totals Section -->
                <div class="row mt-4">
                    <!-- Global Discount Block -->
                    <div class="col-12 col-md-6 col-lg-4 offset-lg-4 mb-3 mb-md-0 d-flex align-items-end justify-content-md-end">
                        <div class="card bg-light border-0 w-100" style="max-width: 300px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div class="card-body py-2 px-3">
                                <label for="oc_descuento_global_pct" class="font-weight-bold mb-1 text-muted" style="font-size: 0.85rem;">Descuento Global a la OC</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="any" min="0" max="100" class="form-control text-right font-weight-bold" id="oc_descuento_global_pct" name="descuentoglobalpct" value="<?=number_format((($ocData['OC_DESCUENTO'] ?? 0) > 0 && ($ocData['OC_SUBTOTAL'] ?? 0) > 0) ? (($ocData['OC_DESCUENTO'] ?? 0) / ($ocData['OC_SUBTOTAL'] ?? 0) * 100) : 0, 2)?>" onchange="recalcularTotalesOC()" onkeyup="recalcularTotalesOC()" style="color: #dc3545;">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-white text-danger font-weight-bold">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th class="py-1 text-end" style="width: 50%;">Subtotal:</th>
                                    <td class="py-1 text-end font-weight-bold" id="lblSubtotal">$0.00</td>
                                </tr>
                                <tr>
                                    <th class="py-1 text-end text-danger">Desc. Global:</th>
                                    <td class="py-1 text-end font-weight-bold text-danger" id="lblDescuentoGlobalAmount">-$0.00</td>
                                </tr>
                                <tr>
                                    <th class="py-1 text-end">IVA (Impuestos):</th>
                                    <td class="py-1 text-end font-weight-bold" id="lblIva">$0.00</td>
                                </tr>
                                <tr class="border-top">
                                    <th class="py-2 text-end text-primary" style="font-size: 1.2em;">Total Global:</th>
                                    <td class="py-2 text-end font-weight-bold text-primary" style="font-size: 1.2em;" id="lblTotal">$0.00</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Action Buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center mt-3 pt-2">
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center order-1 order-sm-2">
                        <button type="button" class="btn btn-outline-primary mb-2 mb-sm-0 mr-0 mr-sm-2" id="btnGuardarBorrador">
                            <i class="mdi mdi-file-document-outline mr-1"></i> Guardar Borrador
                        </button>
                        <button type="button" class="btn btn-primary shadow-sm mb-2 mb-sm-0" id="btnGuardarSolicitar">
                            <i class="mdi mdi-check mr-1"></i> Guardar y Solicitar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let articulosCatalogo = [];
    
    function formatNumberDisplay(num) {
        let formatted = parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (formatted.endsWith('.00')) {
            formatted = formatted.substring(0, formatted.length - 3);
        }
        return formatted;
    }

    function cargarAlmacenes(inicialAlmacenId) {
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            let options = "<option value=''>Selecciona Almacén</option>";
            if (data && data !== 0) {
                $.each(data, function(_, item) {
                    if (item.ID) {
                        const selected = (item.ID == inicialAlmacenId) ? 'selected' : '';
                        options += "<option value='" + item.ID + "' " + selected + ">" + item.NOMBRE + "</option>";
                    }
                });
            }
            $("#ocalmacen").html(options);
            if (inicialAlmacenId) {
                cargarRequerimientosMateriales(inicialAlmacenId);
                // No llamamos a cargarDetalleRequerimiento aquí para evitar que se borren 
                // los artículos que ya cargamos desde la base de datos (OCDET).
            }
        });
    }

    function cargarProveedores(inicialProveedorId) {
        $.getJSON("../ajax/get.proveedores.php", function(data) {
            let options = "<option value=''>Selecciona Proveedor</option>";
            if (data && data !== 0) {
                $.each(data, function(_, item) {
                    if (item.ID) {
                        const selected = (item.ID == inicialProveedorId) ? 'selected' : '';
                        options += "<option value='" + item.ID + "' " + selected + ">" + item.NOMBRE + "</option>";
                    }
                });
            }
            $("#ocproveedor").html(options);
        });
    }

    function cargarRequerimientosMateriales(almacenId) {
        $("#sectionRequerimientos").fadeIn();
        
        const provId = $("#ocproveedor").val() || 0;
        const soloOptimos = $("#chkSoloOptimos").is(":checked") ? 1 : 0;
        
        // Cargar General (todos)
        $.getJSON("../ajax/requerimientosmaterial.catalogo.php", {
            almacenid: '',
            status: "2",
            proveedor_id: provId,
            solo_optimos: soloOptimos
        }, function(resp) {
            let html = "";
            const items = (resp && resp.ok && Array.isArray(resp.items)) ? resp.items : [];
            items.forEach(function(item) {
                const pendingText = item.ARTICULOS_PENDIENTES < item.ARTICULOS 
                    ? `(${item.ARTICULOS_PENDIENTES} de ${item.ARTICULOS} art. pendientes)` 
                    : `(${item.ARTICULOS} art.)`;
                html += `<div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input cb-req" id="cb-req-gen-${item.ID}" value="${item.ID}">
                            <label class="custom-control-label" for="cb-req-gen-${item.ID}">${item.FOLIO} - ${item.ALMACEN_NOMBRE} ${pendingText}</label>
                         </div>`;
            });
            if(html === "") html = "<div class='text-muted'>No hay requerimientos con artículos óptimos para este proveedor.</div>";
            $("#listaReqGeneral").html(html);
            syncCheckboxes();
        });

        // Cargar por Sucursal (Almacén seleccionado)
        if (almacenId) {
            $.getJSON("../ajax/requerimientosmaterial.catalogo.php", {
                almacenid: almacenId,
                status: "2",
                proveedor_id: provId,
                solo_optimos: soloOptimos
            }, function(resp) {
                let html = "";
                const items = (resp && resp.ok && Array.isArray(resp.items)) ? resp.items : [];
                items.forEach(function(item) {
                    const pendingText = item.ARTICULOS_PENDIENTES < item.ARTICULOS 
                        ? `(${item.ARTICULOS_PENDIENTES} de ${item.ARTICULOS} art. pendientes)` 
                        : `(${item.ARTICULOS} art.)`;
                    html += `<div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input cb-req" id="cb-req-suc-${item.ID}" value="${item.ID}">
                                <label class="custom-control-label" for="cb-req-suc-${item.ID}">${item.FOLIO} ${pendingText}</label>
                             </div>`;
                });
                if(html === "") html = "<div class='text-muted'>No hay requerimientos pendientes para este almacén.</div>";
                $("#listaReqSucursal").html(html);
                syncCheckboxes();
            });
        } else {
            $("#listaReqSucursal").html("<div class='text-muted'>Selecciona un almacén primero</div>");
        }
    }
    
    let selectedReqIds = [];
    
    function syncCheckboxes() {
        $(".cb-req").each(function() {
            $(this).prop('checked', selectedReqIds.includes($(this).val()));
        });
    }

    $(document).on('change', '.cb-req', function() {
        const val = $(this).val();
        const isChecked = $(this).is(':checked');
        const $chk = $(`.cb-req[value='${val}']`);
        
        if (isChecked && !selectedReqIds.includes(val)) {
            const tempReqs = [...selectedReqIds, val];
            const reqidParam = tempReqs.join(',');
            
            $chk.prop('disabled', true);
            $.getJSON("../ajax/requerimientosmaterial.detalle.php", {
                reqid: reqidParam,
                proveedor_id: 0
            }, function(resp) {
                $chk.prop('disabled', false);
                if (resp && resp.ok && resp.items && resp.items.length > 0) {
                    const validItems = resp.items.filter(i => i.mejor_proveedor_id !== null);
                    if (validItems.length === 0) {
                        $chk.prop('checked', false);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin Proveedor',
                            text: 'El requerimiento seleccionado no contiene ningún artículo que esté registrado con un proveedor. No se puede agregar.'
                        });
                        return;
                    }
                    
                    selectedReqIds.push(val);
                    $chk.prop('checked', true);
                    
                    let counts = {};
                    let maxCount = 0;
                    let bestProvId = null;
                    let bestProvName = null;
                    resp.items.forEach(function(item) {
                        if (item.mejor_proveedor_id) {
                            const pid = item.mejor_proveedor_id;
                            counts[pid] = (counts[pid] || 0) + 1;
                            if (counts[pid] > maxCount) {
                                maxCount = counts[pid];
                                bestProvId = pid;
                                bestProvName = item.mejor_proveedor_nombre;
                            }
                        }
                    });
                    
                    if (bestProvId && $("#ocproveedor").val() != bestProvId) {
                        if ($("#ocproveedor").find("option[value='" + bestProvId + "']").length) {
                            $("#ocproveedor").val(bestProvId).trigger('change.select2');
                        } else {
                            var newOption = new Option(bestProvName, bestProvId, true, true);
                            $("#ocproveedor").append(newOption).trigger('change.select2');
                        }
                    }
                    
                    cargarDetalleRequerimiento(selectedReqIds);
                } else {
                    $chk.prop('checked', false);
                    Swal.fire({ icon: 'warning', text: 'No se encontraron artículos pendientes.' });
                }
            }).fail(function() {
                $chk.prop('disabled', false).prop('checked', false);
                Swal.fire({ icon: 'error', text: 'Error al consultar el requerimiento.' });
            });
            
        } else if (!isChecked && selectedReqIds.includes(val)) {
            selectedReqIds = selectedReqIds.filter(id => id !== val);
            $chk.prop('checked', false);
            cargarDetalleRequerimiento(selectedReqIds);
        }
    });

    function cargarArticulos() {
        $.getJSON("../ajax/get.articulos.catalogo.php", function(data) {
            articulosCatalogo = data || [];
            inicializarAutocompleteOC();
        });
    }

    function limpiarTablaArticulosOC() {
        $("#ocArticulosBody").html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-3" id="rowEmptyPlaceholder">No hay artículos agregados</td>
            </tr>
        `);
        recalcularTotalesOC();
    }

    function agregarFilaOC(data) {
        const idArticulo = String(data.articulo_id || data.ID || '');
        const clave = data.clave || data.CLAVE_ARTICULO || '';
        const nombre = data.nombre || data.NOMBRE || '';
        const qty = parseFloat(data.cantidad ?? 1);
        const cost = parseFloat(data.costo ?? 0);
        const desc = parseFloat(data.descuento_pct ?? 0);
        const iva = parseFloat(data.iva_pct ?? 16);

        if (!idArticulo || !nombre || qty <= 0) {
            return false;
        }

        let duplicado = false;
        $("#ocArticulosBody input[name='idarticuloarray[]']").each(function() {
            if ($(this).val() === idArticulo) {
                duplicado = true;
                return false;
            }
        });
        if (duplicado) {
            return false;
        }

        $("#rowEmptyPlaceholder").closest("tr").remove();

        const descTipo = data.desc_tipo || '';
        const descMotivo = data.desc_motivo || '';

        const subtotalItem = qty * cost * (1 - (desc / 100));
        const totalItem = subtotalItem * (1 + (iva / 100));
        const rowCount = $("#ocArticulosBody tr.articulo-row").length + 1;

        const newRow = `
            <tr class="articulo-row">
                <td class="px-1" data-label="#">${rowCount}</td>
                <td class="px-1" data-label="Clave">${clave || 'S/K'}</td>
                <td class="td-articulo-nombre" data-label="Artículo">
                    <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                    <input type="hidden" name="cantidadarray[]" value="${qty}">
                    <input type="hidden" name="costoarray[]" value="${cost}">
                    <input type="hidden" name="descuentoarray[]" class="input-descuento-pct" value="${desc}">
                    <input type="hidden" name="descuentotipoarray[]" class="input-descuento-tipo" value="${descTipo}">
                    <input type="hidden" name="descuentomotivoarray[]" class="input-descuento-motivo" value="${descMotivo}">
                    <input type="hidden" name="ivaarray[]" class="input-iva-pct" value="${iva}">
                    <span class="font-weight-bold">${nombre}</span>
                    <div class="row-recomendacion"></div>
                </td>
                <td class="text-center px-1" data-label="Cantidad">
                    <div class="d-flex align-items-center justify-content-center">
                        <span class="cell-cant-lbl mr-1">${Number.isInteger(qty) ? qty : parseFloat(qty.toFixed(2))}</span>
                        <button type="button" class="btn btn-sm btn-light btnEditarCantOC" style="padding: 0.1rem 0.3rem;" title="Editar Cantidad"><i class="mdi mdi-pencil text-primary"></i></button>
                    </div>
                </td>
                <td class="px-1" data-label="Costo Unit.">
                    <div class="d-flex align-items-center">
                        $<span class="cell-costo-lbl mr-1">${formatNumberDisplay(cost)}</span>
                        <button type="button" class="btn btn-sm btn-light btnEditarCostoOC" style="padding: 0.1rem 0.3rem;" title="Editar Costo"><i class="mdi mdi-pencil text-primary"></i></button>
                    </div>
                </td>
                <td class="px-1" data-label="Desc %">
                    <div class="d-flex align-items-center">
                        <span class="cell-desc-lbl mr-1">${formatNumberDisplay(desc)}%</span>
                        <button type="button" class="btn btn-sm btn-light btnEditarDescOC" style="padding: 0.1rem 0.3rem;" title="Editar Descuento"><i class="mdi mdi-pencil text-primary"></i></button>
                    </div>
                </td>
                <td class="px-1" data-label="IVA %">${formatNumberDisplay(iva)}%</td>
                <td class="px-1" data-label="Subtotal">$<span class="cell-subtotal">${formatNumberDisplay(subtotalItem)}</span></td>
                <td class="font-weight-bold text-primary text-right px-1" data-label="Total">$<span class="cell-total">${formatNumberDisplay(totalItem)}</span></td>
                <td class="px-1"><button type="button" class="btn btn-sm btn-danger btnQuitarArticuloOC" style="padding: 0.15rem 0.35rem;" title="Quitar"><i class="mdi mdi-delete"></i> <span class="d-inline d-md-none">Quitar</span></button></td>
            </tr>
        `;


        $("#ocArticulosBody").append(newRow);
        const $lastRow = $("#ocArticulosBody tr.articulo-row").last();
        if (data.mejor_proveedor_id) {
            $lastRow.data("mejor-prov-id", data.mejor_proveedor_id);
            $lastRow.data("mejor-prov-nombre", data.mejor_proveedor_nombre);
            $lastRow.data("mejor-prov-costo", data.mejor_proveedor_costo);
        }
        if (typeof actualizarRecomendacionesTabla === "function") actualizarRecomendacionesTabla();
        recalcularTotalesOC();
        return true;
    }

    function cargarDetalleRequerimiento(reqIds) {
        if (!reqIds || reqIds.length === 0) {
            limpiarTablaArticulosOC();
            return;
        }

        const reqidParam = Array.isArray(reqIds) ? reqIds.join(',') : reqIds;
        const provId = $("#ocproveedor").val() || 0;

        $.getJSON("../ajax/requerimientosmaterial.detalle.php", {
            reqid: reqidParam,
            proveedor_id: provId
        }, function(resp) {
            if (!resp || !resp.ok || !Array.isArray(resp.items)) {
                Swal.fire({
                    icon: 'warning',
                    text: (resp && resp.msg) ? resp.msg : 'No se pudo cargar los requerimientos.'
                });
                return;
            }

            limpiarTablaArticulosOC();
            
            let filteredCount = 0;
            const filterOptimos = $("#chkSoloOptimos").is(":checked");
            const currentProvId = $("#ocproveedor").val();
            
            resp.items.forEach(function(item) {
                let shouldAdd = true;
                if (filterOptimos && currentProvId && item.mejor_proveedor_id) {
                    if (item.mejor_proveedor_id != currentProvId) {
                        shouldAdd = false;
                    }
                }
                
                if (shouldAdd) {
                    agregarFilaOC({
                        articulo_id: item.articulo_id,
                        clave: item.clave,
                        nombre: item.nombre,
                        cantidad: item.cantidad,
                        costo: item.costo,
                        descuento_pct: 0,
                        iva_pct: 16,
                        mejor_proveedor_id: item.mejor_proveedor_id,
                        mejor_proveedor_nombre: item.mejor_proveedor_nombre,
                        mejor_proveedor_costo: item.mejor_proveedor_costo
                    });
                } else {
                    filteredCount++;
                }
            });
            
            $("#filtroProveedorAlert").remove();
            if (filteredCount > 0) {
                $("#ocArticulosBody").closest(".table-responsive").before(`
                    <div id="filtroProveedorAlert" class="alert py-2 px-3 mb-3 d-flex align-items-center" style="background-color: #e0f2fe; color: #0369a1; border-left: 4px solid #0ea5e9; border-radius: 6px; font-size: 0.85rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border-top: none; border-right: none; border-bottom: none;">
                        <i class="mdi mdi-information-outline mr-2" style="font-size: 1.2rem; color: #0284c7;"></i>
                        <span>Se ocultaron <b>${filteredCount}</b> artículo(s) del requerimiento porque existe una oferta más barata con otro proveedor. Si deseas agregarlos de todos modos, apaga el interruptor arriba.</span>
                    </div>
                `);
            }
        }).fail(function() {
            Swal.fire({
                icon: 'error',
                text: 'Error al cargar los requerimientos de material.'
            });
        });
    }

    function inicializarAutocompleteOC() {
        if ($("#ocarticulo").data("ui-autocomplete")) {
            $("#ocarticulo").autocomplete("destroy");
        }

        $("#ocarticulo").autocomplete({
            appendTo: "#modalglobal .modal-body",
            minLength: 1,
            source: function(request, response) {
                const term = (request.term || "").toUpperCase();
                const resultados = $.map(articulosCatalogo, function(item) {
                    const searchable = ((item.CLAVE_ARTICULO || "") + " " + (item.NOMBRE || "")).toUpperCase();
                    if (searchable.indexOf(term) !== -1) {
                        return {
                            label: "(" + (item.CLAVE_ARTICULO || "S/K") + ") " + (item.NOMBRE || ""),
                            value: item.NOMBRE || "",
                            data: item
                        };
                    }
                    return null;
                });
                response(resultados);
            },
            select: function(_, ui) {
                const item = ui.item.data;
                $("#ocarticulo").val(item.NOMBRE || "");
                $("#oc_idarticulo").val(item.ID || "");
                $("#oc_cvearticulo").val(item.CLAVE_ARTICULO || "");

                // Get stock details
                consultarStock(item.ID);
                // Get default cost
                consultarCosto(item.ID);
                return false;
            }
        });
    }

    function consultarStock(articuloId) {
        const almacenId = $("#ocalmacen").val();
        if (!articuloId) return;

        $.getJSON("../ajax/get.articulo.stock_info.php", {
            articulo_id: articuloId,
            almacen_id: almacenId
        }, function(res) {
            if (res && res.status === 'success') {
                $("#lblStockActual").text("Actual: " + res.stock_actual);
                $("#lblStockMinimo").text("Mínimo: " + res.stock_minimo);
                $("#lblStockTransito").text("En Tránsito: " + res.stock_transito);
                $("#ocStockInfo").fadeIn();
            }
        });
    }

    function consultarCosto(articuloId) {
        if (!articuloId) return;

        $.getJSON("../ajax/get.articulo.cost_info.php", {
            articulo_id: articuloId
        }, function(res) {
            if (res && res.status === 'success') {
                $("#oc_costo").val(parseFloat(res.cost).toFixed(2));
            }
        });

        // Buscar el mejor precio sugerido
        $.getJSON("../ajax/get.articulo.cheapest_provider.php", {
            articulo_id: articuloId
        }, function(resCheapest) {
            if (resCheapest && resCheapest.status === "success") {
                $("#oc_mejor_proveedor_id").val(resCheapest.proveedor_id);
                $("#oc_mejor_proveedor_nombre").val(resCheapest.proveedor_nombre);
                $("#oc_mejor_proveedor_costo").val(resCheapest.costo);
                
                if (proveedorId != resCheapest.proveedor_id) {
                    $("#lblMejorPrecio").html(`<div class="mt-2"><span style="display: inline-flex; align-items: center; background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid #fde68a; box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: normal; text-align: left;"><i class="mdi mdi-alert-circle mr-1" style="font-size: 0.9rem;"></i> <span>Mejor precio con <b class="ml-1">${resCheapest.proveedor_nombre}</b> a $${parseFloat(resCheapest.costo).toFixed(2)}</span></span></div>`).show();
                } else {
                    $("#lblMejorPrecio").html(`<div class="mt-2"><span style="display: inline-flex; align-items: center; background-color: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid #bbf7d0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><i class="mdi mdi-check-circle mr-1" style="font-size: 0.9rem;"></i> ¡Excelente! Mejor precio</span></div>`).show();
                }
            } else {
                $("#lblMejorPrecio").hide();
                $("#oc_mejor_proveedor_id").val("");
                $("#oc_mejor_proveedor_nombre").val("");
                $("#oc_mejor_proveedor_costo").val("");
            }
        });
    }

    function actualizarRecomendacionesTabla() {
        const provActual = $("#ocproveedor").val();
        $("#ocArticulosBody tr.articulo-row").each(function() {
            const mId = $(this).data("mejor-prov-id");
            const mNombre = $(this).data("mejor-prov-nombre");
            const mCosto = parseFloat($(this).data("mejor-prov-costo") || 0);
            const $recCont = $(this).find(".row-recomendacion");
            
            if (mId) {
                if (mId != provActual) {
                    $recCont.html(`<div class="mt-1 mb-1">
                        <span style="display: inline-flex; align-items: center; background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid #fde68a; box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: normal; text-align: left;">
                            <i class="mdi mdi-alert-circle mr-1" style="font-size: 0.9rem;"></i> 
                            <span>Mejor precio con <b class="ml-1">${mNombre}</b> a $${mCosto.toFixed(2)}</span>
                        </span>
                    </div>`);
                } else {
                    $recCont.html(`<div class="mt-1 mb-1">
                        <span style="display: inline-flex; align-items: center; background-color: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid #bbf7d0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <i class="mdi mdi-check-circle mr-1" style="font-size: 0.9rem;"></i> ¡Excelente! Mejor precio
                        </span>
                    </div>`);
                }
            }
        });
    }

    function limpiarFormArticulo() {
        $("#ocarticulo").val("");
        $("#oc_idarticulo").val("");
        $("#oc_cvearticulo").val("");
        $("#oc_cantidad").val("1");
        $("#oc_costo").val("0.00");
        $("#oc_descuento").val("0.00");
        $("#oc_iva").val("16.00");
        $("#ocStockInfo").fadeOut();
        $("#lblMejorPrecio").hide();
        $("#oc_mejor_proveedor_id").val("");
        $("#oc_mejor_proveedor_nombre").val("");
        $("#oc_mejor_proveedor_costo").val("");
    }

    function recalcularTotalesOC() {
        let subtotal = 0;
        let descuentoArticulos = 0;
        let subtotalNetoArticulos = 0;
        let iva = 0;
        let total = 0;
        
        let globalDescPct = parseFloat($("#oc_descuento_global_pct").val()) || 0;
        if (globalDescPct < 0) globalDescPct = 0;
        if (globalDescPct > 100) globalDescPct = 100;

        $("#ocArticulosBody tr.articulo-row").each(function() {
            const qty = parseFloat($(this).find("input[name='cantidadarray[]']").val() || 0);
            const cost = parseFloat($(this).find("input[name='costoarray[]']").val() || 0);
            const descPct = parseFloat($(this).find("input[name='descuentoarray[]']").val() || 0);
            const ivaPct = parseFloat($(this).find("input[name='ivaarray[]']").val() || 0);

            const rowSubtotal = qty * cost;
            const rowDesc = rowSubtotal * (descPct / 100);
            const rowSubtotalNeto = rowSubtotal - rowDesc;
            
            // Aplicar descuento global al subtotal neto de la fila
            const rowDescGlobal = rowSubtotalNeto * (globalDescPct / 100);
            const rowFinalBaseIva = rowSubtotalNeto - rowDescGlobal;
            
            const rowIva = rowFinalBaseIva * (ivaPct / 100);
            
            // We DO NOT update the row's total cell in the table to reflect global discount,
            // or do we? The global discount is GLOBAL, so row's "Subtotal" and "Total" columns 
            // usually just show the item-level total.
            // But for the global SUM, we must use the discounted base:
            
            // We keep row display unaffected by global discount
            const rowTotalDisplay = rowSubtotalNeto + (rowSubtotalNeto * (ivaPct / 100));
            $(this).find(".cell-subtotal").text(formatNumberDisplay(rowSubtotalNeto));
            $(this).find(".cell-total").text(formatNumberDisplay(rowTotalDisplay));

            subtotal += rowSubtotal;
            descuentoArticulos += rowDesc;
            subtotalNetoArticulos += rowSubtotalNeto;
            iva += rowIva;
        });
        
        let descuentoGlobalAmount = subtotalNetoArticulos * (globalDescPct / 100);
        total = subtotalNetoArticulos - descuentoGlobalAmount + iva;

        $("#lblSubtotal").text("$" + formatNumberDisplay(subtotalNetoArticulos));
        $("#lblDescuentoGlobalAmount").text("-$" + formatNumberDisplay(descuentoGlobalAmount));
        $("#lblIva").text("$" + formatNumberDisplay(iva));
        $("#lblTotal").text("$" + formatNumberDisplay(total));
    }

    function guardarOrdenCompra(statusId) {
        const almacen = $("#ocalmacen").val();
        const proveedor = $("#ocproveedor").val();
        const totalRows = $("#ocArticulosBody tr.articulo-row").length;

        if (!almacen) {
            Swal.fire({
                icon: 'warning',
                text: 'Debes seleccionar un almacén.'
            });
            return;
        }
        if (!proveedor) {
            Swal.fire({
                icon: 'warning',
                text: 'Debes seleccionar un proveedor.'
            });
            return;
        }
        if (totalRows === 0) {
            Swal.fire({
                icon: 'warning',
                text: 'Debes agregar al menos un artículo.'
            });
            return;
        }

        $("#statusid").val(statusId);
        const formData = new FormData(document.getElementById("form-oc-nueva"));

        if (selectedReqIds.length > 0) {
            formData.append("requerimientomaterialid", selectedReqIds.join(','));
        }

        $.ajax({
            url: "../ajax/oc.guardar.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(resp) {
                if (resp && resp.status === "success") {
                    Swal.fire({
                        icon: "success",
                        html: (resp.message || "Orden de compra guardada.") + "<br><b>Folio: " + (resp.folio || "") + "</b>"
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: "warning",
                        text: (resp && resp.message) ? resp.message : "Error al guardar la orden de compra."
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: "error",
                    text: "Error de servidor al procesar la solicitud."
                });
            },
            complete: function() {
                $("#loading").hide();
            }
        });
    }

    $(document).ready(function() {
        // Pre-load data from PHP
        const inicialAlmacenId = <?=json_encode($ocData['OC_ALMACENID'] ?? '')?>;
        const inicialProveedorId = <?=json_encode($ocData['OC_PROVEEDORID'] ?? '')?>;
        const inicialArticulos = <?=json_encode($articulosPrecargados)?>;
        window.selectedReqIds = <?=json_encode($reqIds)?>;

        cargarAlmacenes(inicialAlmacenId);
        cargarProveedores(inicialProveedorId);

        cargarArticulos();
        
        // Inject articles
        if (inicialArticulos.length > 0) {
            $("#rowEmptyPlaceholder").closest("tr").remove();
            inicialArticulos.forEach(art => {
                agregarFilaOC({
                    articulo_id: art.id,
                    clave: art.clave,
                    nombre: art.nombre,
                    cantidad: art.cantidad,
                    costo: art.costo,
                    descuento_pct: art.descPct,
                    iva_pct: art.ivaPct,
                    descTipo: art.descTipo,
                    descMotivo: art.descMotivo
                });
            });
        }

        $("#ocalmacen").on("change", function() {
            cargarRequerimientosMateriales($(this).val());
            // Re-check stock of current selected product if any
            const articuloId = $("#oc_idarticulo").val();
            if (articuloId) {
                consultarStock(articuloId);
            }
        });

        $("#chkSoloOptimos").on("change", function() {
            cargarRequerimientosMateriales($("#ocalmacen").val());
            if (typeof selectedReqIds !== "undefined" && selectedReqIds.length > 0) {
                cargarDetalleRequerimiento(selectedReqIds);
            }
        });

        $("#ocproveedor").on("change", function() {
            const provId = $(this).val();
            
            cargarRequerimientosMateriales($("#ocalmacen").val());
            
            if (typeof actualizarRecomendacionesTabla === "function") actualizarRecomendacionesTabla();

            const articuloId = $("#oc_idarticulo").val();
            if (articuloId) {
                consultarCosto(articuloId);
            }

            if (window.selectedReqIds && window.selectedReqIds.length > 0) {
                cargarDetalleRequerimiento(window.selectedReqIds);
            } else if (typeof selectedReqIds !== "undefined" && selectedReqIds.length > 0) {
                cargarDetalleRequerimiento(selectedReqIds);
            } else {
                $("#ocArticulosBody tr.articulo-row").each(function() {
                    const tr = $(this);
                    const artId = tr.find("input[name='idarticuloarray[]']").val();
                    
                    if (artId) {
                        $.getJSON("../ajax/get.articulo.cost_info.php", {
                            articulo_id: artId,
                            proveedor_id: provId
                        }, function(res) {
                            if (res && res.status === "success") {
                                const newCost = parseFloat(res.cost);
                                const qty = parseFloat(tr.find("input[name='cantidadarray[]']").val() || 0);
                                const descPct = parseFloat(tr.find("input[name='descuentoarray[]']").val() || 0);
                                const ivaPct = parseFloat(tr.find("input[name='ivaarray[]']").val() || 0);
                                
                                const rowSubtotal = qty * newCost;
                                const rowDesc = rowSubtotal * (descPct / 100);
                                const rowSubtotalNeto = rowSubtotal - rowDesc;
                                const rowIva = rowSubtotalNeto * (ivaPct / 100);
                                const rowTotal = rowSubtotalNeto + rowIva;
                                
                                tr.find("input[name='costoarray[]']").val(newCost);
                                tr.find("td[data-label='Costo Unit.']").text('$' + newCost.toFixed(2));
                                tr.find("td[data-label='Subtotal']").text('$' + rowSubtotalNeto.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                tr.find("td[data-label='Total']").text('$' + rowTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                
                                if (res.mejor_proveedor_id) {
                                    tr.data("mejor-prov-id", res.mejor_proveedor_id);
                                    tr.data("mejor-prov-nombre", res.mejor_proveedor_nombre);
                                    tr.data("mejor-prov-costo", res.mejor_proveedor_costo);
                                }
                                if (typeof actualizarRecomendacionesTabla === "function") actualizarRecomendacionesTabla();
                                
                                recalcularTotalesOC();
                            }
                        });
                    }
                });
            }
        });

        $("#ocrequerimientomaterial").on("change", function() {
            const reqId = $(this).val();
            if (reqId) {
                cargarDetalleRequerimiento(reqId);
            }
        });

        $("#btnAgregarArticuloOC").on("click", function() {
            const agregado = agregarFilaOC({
                articulo_id: $("#oc_idarticulo").val(),
                clave: $("#oc_cvearticulo").val(),
                nombre: $("#ocarticulo").val(),
                cantidad: $("#oc_cantidad").val(),
                costo: $("#oc_costo").val(),
                descuento_pct: $("#oc_descuento").val(),
                iva_pct: $("#oc_iva").val(),
                mejor_proveedor_id: $("#oc_mejor_proveedor_id").val(),
                mejor_proveedor_nombre: $("#oc_mejor_proveedor_nombre").val(),
                mejor_proveedor_costo: $("#oc_mejor_proveedor_costo").val()
            });

            if (!agregado) {
                Swal.fire({
                    icon: 'warning',
                    text: 'Selecciona un artículo válido de la lista.'
                });
                return;
            }
            limpiarFormArticulo();
        });

        $(document).on("click", ".btnQuitarArticuloOC", function() {
            $(this).closest("tr").remove();

            // Reindex
            $("#ocArticulosBody tr.articulo-row").each(function(index) {
                $(this).children("td").first().text(index + 1);
            });

            if ($("#ocArticulosBody tr.articulo-row").length === 0) {
                $("#ocArticulosBody").append(`
                    <tr>
                        <td colspan="10" class="text-center text-muted py-3" id="rowEmptyPlaceholder">No hay artículos agregados</td>
                    </tr>
                `);
            }

            recalcularTotalesOC();
        });

        $(document).on("click", ".btnEditarDescOC", function() {
            const tr = $(this).closest("tr");
            const actualPct = tr.find(".input-descuento-pct").val() || 0;
            const actualTipo = tr.find(".input-descuento-tipo").val() || '';
            const actualMotivo = tr.find(".input-descuento-motivo").val() || '';

            const tipoOptions = [
                "Precio mayoreo",
                "Pedido inicial de mes",
                "Promoción única",
                "Negociación total"
            ];
            
            let optionsHtml = '<option value="">Selecciona un tipo...</option>';
            tipoOptions.forEach(opt => {
                optionsHtml += `<option value="${opt}" ${actualTipo === opt ? 'selected' : ''}>${opt}</option>`;
            });

            Swal.fire({
                target: document.getElementById('modalglobal') || document.body,
                title: '<i class="mdi mdi-ticket-percent text-primary mr-2"></i> Detalles del Descuento',
                html: `
                    <div style="text-align: left; padding: 10px 5px;">
                        <div class="form-group mb-4">
                            <label style="color: #475569; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Porcentaje de Descuento (%)</label>
                            <input type="number" step="any" min="0" max="100" id="swal-desc-pct" class="form-control form-control-lg" value="${actualPct}" style="border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1.1rem; color: #1e293b; text-align: right; padding-right: 15px;" placeholder="0.00">
                        </div>
                        <div class="form-group mb-4">
                            <label style="color: #475569; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Tipo de Descuento</label>
                            <select id="swal-desc-tipo" class="form-control" style="border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #334155; padding: 8px 12px; height: auto;">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label style="color: #475569; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Motivo / Nota Justificativa</label>
                            <textarea id="swal-desc-motivo" class="form-control" rows="3" placeholder="Describe brevemente la razón de este descuento..." style="border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #334155; padding: 10px; resize: vertical; min-height: 80px;">${actualMotivo}</textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-check mr-1"></i> Aplicar Descuento',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#94a3b8',
                customClass: {
                    confirmButton: 'btn btn-primary px-4 py-2 rounded-lg font-weight-bold shadow-sm',
                    cancelButton: 'btn btn-light px-4 py-2 rounded-lg font-weight-bold text-muted border',
                    popup: 'rounded-lg shadow-lg border-0'
                },
                buttonsStyling: false,
                focusConfirm: false,
                didOpen: () => {
                    const container = document.querySelector('.swal2-container');
                    if (container) container.style.zIndex = '999999';
                    
                    // Auto focus percentage if it's 0, else focus note
                    setTimeout(() => {
                        const pctInput = document.getElementById('swal-desc-pct');
                        if (pctInput && parseFloat(pctInput.value) === 0) {
                            pctInput.select();
                        } else {
                            document.getElementById('swal-desc-motivo').focus();
                        }
                    }, 100);
                },
                preConfirm: () => {
                    const valPct = parseFloat(document.getElementById('swal-desc-pct').value) || 0;
                    const valTipo = document.getElementById('swal-desc-tipo').value.trim();
                    const valMotivo = document.getElementById('swal-desc-motivo').value.trim();

                    if (valPct > 0 && valTipo === '') {
                        Swal.showValidationMessage('Debes seleccionar un tipo de descuento si el porcentaje es mayor a 0.');
                        return false;
                    }
                    if (valPct > 0 && valMotivo === '') {
                        Swal.showValidationMessage('Debes ingresar un motivo justificativo.');
                        return false;
                    }

                    return { pct: valPct, tipo: valTipo, motivo: valMotivo };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;
                    
                    tr.find(".input-descuento-pct").val(data.pct);
                    tr.find(".input-descuento-tipo").val(data.tipo);
                    tr.find(".input-descuento-motivo").val(data.motivo);
                    
                    tr.find(".cell-desc-lbl").text(data.pct.toFixed(2) + '%');
                    
                    recalcularTotalesOC();
                }
            });
        });

        $(document).on("click", ".btnEditarCantOC", function() {
            const tr = $(this).closest("tr");
            const inputCant = tr.find("input[name='cantidadarray[]']");
            const cantVal = parseFloat(inputCant.val()) || 0;
            
            Swal.fire({
                title: 'Editar Cantidad',
                input: 'number',
                inputAttributes: {
                    min: 0,
                    step: 'any'
                },
                inputValue: cantVal,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || parseFloat(value) <= 0) {
                        return 'Ingresa una cantidad mayor a 0';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newCant = parseFloat(result.value);
                    inputCant.val(newCant);
                    tr.find(".cell-cant-lbl").text(Number.isInteger(newCant) ? newCant : newCant.toFixed(2));
                    recalcularTotalesOC();
                }
            });
        });

        $(document).on("click", ".btnEditarCostoOC", function() {
            const tr = $(this).closest("tr");
            const inputCosto = tr.find("input[name='costoarray[]']");
            const costoVal = parseFloat(inputCosto.val()) || 0;
            
            Swal.fire({
                title: 'Editar Costo Unitario',
                input: 'number',
                inputAttributes: {
                    min: 0,
                    step: 0.01
                },
                inputValue: costoVal,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || parseFloat(value) < 0) {
                        return 'Ingresa un costo válido';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newCosto = parseFloat(result.value);
                    inputCosto.val(newCosto);
                    tr.find(".cell-costo-lbl").text(formatNumberDisplay(newCosto));
                    recalcularTotalesOC();
                }
            });
        });

        $("#btnGuardarBorrador").on("click", function() {
            guardarOrdenCompra(1); // 1 = Guardado / Borrador
        });

        $("#btnGuardarSolicitar").on("click", function() {
            guardarOrdenCompra(1); // 1 = Solicitado / Pendiente
        });
    });
</script>