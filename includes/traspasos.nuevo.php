<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 320px;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .caducidad-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
    }
</style>
<?php
include_once("../includes/head.php");
$reqId = isset($_GET['reqid']) ? intval(base64_decode($_GET['reqid'])) : 0;
$originId = isset($_GET['origin_id']) ? intval($_GET['origin_id']) : 0;
$destId = isset($_GET['dest_id']) ? intval($_GET['dest_id']) : 0;
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Traspaso de Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-traspasos" enctype="multipart/form-data">
                <div class="row align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="form-group">
                            <label for="deinvalmacen">De Almacén <span class="text-danger">*</span></label>
                            <input type="hidden" id="deinvalmacenaux" name="deinvalmacenaux">
                            <select class="form-control" id="deinvalmacen" name="deinvalmacen"></select>
                        </div>
                        <div class="form-group">
                            <label for="deinsucursal">De Categoría</label>
                            <input type="text" class="form-control" id="deinsucursal" name="deinsucursal" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 text-center py-2 py-md-0">
                        <div class="d-none d-md-block">↔</div>
                        <div class="d-block d-md-none">↕</div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="form-group">
                            <label for="ainvalmacen">A Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="ainvalmacen" name="ainvalmacen"></select>
                        </div>
                        <div class="form-group">
                            <label for="ainsucursal">A Categoría</label>
                            <input type="text" class="form-control" id="ainsucursal" name="ainsucursal" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="traspaso_requerimientomaterial">Requerimiento de material (opcional)</label>
                            <select class="form-control" id="traspaso_requerimientomaterial" name="requerimientomaterialid" disabled>
                                <option value="">Selecciona almacén de destino primero</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="invmotivo">Motivo <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row align-items-end">
                    <div class="col-12 col-md-9 mb-2 mb-md-0">
                        <div class="form-group mb-md-0">
                            <input type="hidden" id="idarticulo" name="idarticulo">
                            <input type="hidden" id="invdetid" name="invdetid">
                            <input type="hidden" id="lote" name="lote">
                            <input type="hidden" id="caducidad" name="caducidad">
                            <input type="hidden" id="serie" name="serie">
                            <input type="hidden" id="folioarticulo" name="folioarticulo">
                            <input type="hidden" id="cvearticulo" name="cvearticulo">
                            <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Buscar artículo...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="form-group mb-md-0 mt-2 mt-md-0">
                            <button type="button" class="btn btn-warning w-100" id="agregarProducto">Agregar Artículo</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="150px">Folio</th>
                                <th width="150px">Clave</th>
                                <th>Artículo</th>
                                <th width="150px">Caducidad</th>
                                <th width="50px">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productosBody"></tbody>
                    </table>
                </div>
                <br>
                <button type="button" class="btn btn-success" onclick="guardar();">Guardar</button>
            </form>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>
<script>
    $(document).ready(function() {

        function obtenerEstadoCaducidad(fechaCaducidad) {
            if (!fechaCaducidad) {
                return {
                    colorFondo: '#dff4e4',
                    colorTexto: '#2f6d3f',
                    etiqueta: 'Sin caducidad'
                };
            }
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fecha = new Date(fechaCaducidad + 'T00:00:00');
            if (isNaN(fecha.getTime())) {
                return {
                    colorFondo: '#dff4e4',
                    colorTexto: '#2f6d3f',
                    etiqueta: 'Sin caducidad'
                };
            }
            const unMesDespues = new Date(hoy);
            unMesDespues.setMonth(unMesDespues.getMonth() + 1);
            if (fecha < hoy) {
                return {
                    colorFondo: '#f8d7da',
                    colorTexto: '#8a1f2d',
                    etiqueta: 'Caducado'
                };
            }
            if (fecha <= unMesDespues) {
                return {
                    colorFondo: '#fff3cd',
                    colorTexto: '#8a6d1f',
                    etiqueta: 'Menos de 1 mes'
                };
            }
            return {
                colorFondo: '#dff4e4',
                colorTexto: '#2f6d3f',
                etiqueta: 'Vigente'
            };
        }

        function inicializarAutocompleteArticulos(resultarticulos) {
            const normalizarTexto = function(texto) {
                return (texto || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
            };

            const ajustarMenuAutocomplete = function(inputElement) {
                const $input = $(inputElement);
                const $menu = $input.autocomplete('widget');
                const anchoInput = $input.outerWidth();
                const anchoDeseado = Math.max(anchoInput, 920);
                const maxAncho = Math.max(520, Math.floor($(window).width() * 0.85));
                $menu.css({
                    width: Math.min(anchoDeseado, maxAncho) + 'px',
                    maxWidth: maxAncho + 'px'
                });
            };

            const obtenerIdsEnTabla = function() {
                const ids = new Set();
                $('#productosBody tr').each(function() {
                    const invdetid = $(this).find('input[name="invdetidarray[]"]').val();
                    if (invdetid) ids.add(String(invdetid));
                });
                return ids;
            };

            if ($("#articulo").data("ui-autocomplete")) {
                $("#articulo").autocomplete("destroy");
            }

            $("#articulo").autocomplete({
                appendTo: "#modalglobal .modal-body",
                position: {
                    my: "left top+6",
                    at: "left bottom",
                    collision: "flipfit"
                },
                source: function(request, response) {
                    const termino = normalizarTexto(request.term);
                    const idsAgregados = obtenerIdsEnTabla();

                    const nombreAlmacenDestino = $("#ainvalmacen option:selected").text().toUpperCase();
                    const esDestinoCaducados = nombreAlmacenDestino.includes("CADUCADO");

                    function getPrioridadCaducidad(fechaCaducidad) {
                        if (!fechaCaducidad) return 3;
                        const hoy = new Date();
                        hoy.setHours(0, 0, 0, 0);
                        const fecha = new Date(fechaCaducidad + 'T00:00:00');
                        if (isNaN(fecha.getTime())) return 3;
                        const unMesDespues = new Date(hoy);
                        unMesDespues.setMonth(unMesDespues.getMonth() + 1);
                        if (fecha < hoy) return 1;
                        if (fecha <= unMesDespues) return 2;
                        return 3;
                    }

                    var resultados = $.map(resultarticulos, function(obj) {
                        const hoy = new Date();
                        hoy.setHours(0, 0, 0, 0);
                        let isCaducado = false;
                        if (obj.CADUCIDAD) {
                            const fecha = new Date(obj.CADUCIDAD + 'T00:00:00');
                            if (!isNaN(fecha.getTime()) && fecha < hoy) {
                                isCaducado = true;
                            }
                        }

                        if (esDestinoCaducados) {
                            // Solo mostrar caducados
                            if (!isCaducado) return null;
                        } else {
                            // Solo mostrar NO caducados
                            if (isCaducado) return null;
                        }

                        var label = obj.STOCK_FOLIO + ' - ' + '(' + obj.CLAVE_ARTICULO + ') ' + obj.NOMBRE.toUpperCase();
                        const searchable = normalizarTexto((obj.STOCK_FOLIO || '') + ' ' + (obj.CLAVE_ARTICULO || '') + ' ' + (obj.NOMBRE || ''));
                        
                        // Si hay artículos permitidos (porque es un almacén de proyecto), validamos
                        if (window.isProjectDest) {
                            let allowed = window.allowedDestArticles || [];
                            if (!allowed.includes(String(obj.ID)) && !allowed.includes(String(-obj.ID))) {
                                return null;
                            }
                        }

                        if (searchable.includes(termino) && !idsAgregados.has(String(obj.INVDETID))) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                invdetid: obj.INVDETID,
                                lote: obj.LOTE,
                                caducidad: obj.CADUCIDAD,
                                serie: obj.SERIE,
                                folioarticulo: obj.STOCK_FOLIO,
                                cvearticulo: obj.CLAVE_ARTICULO
                            };
                        }
                        return null;
                    }).filter(Boolean);

                    resultados.sort(function(a, b) {
                        var prioA = getPrioridadCaducidad(a.caducidad);
                        var prioB = getPrioridadCaducidad(b.caducidad);
                        if (prioA !== prioB) return prioA - prioB;
                        var fechaA = a.caducidad ? new Date(a.caducidad + 'T00:00:00').getTime() : Infinity;
                        var fechaB = b.caducidad ? new Date(b.caducidad + 'T00:00:00').getTime() : Infinity;
                        return fechaA - fechaB;
                    });

                    response(resultados);
                },
                minLength: 1,
                open: function() {
                    ajustarMenuAutocomplete(this);
                },
                select: function(event, ui) {
                    $("#articulo").val(ui.item.nombre);
                    $("#folioarticulo").val(ui.item.folioarticulo);
                    $("#cvearticulo").val(ui.item.cvearticulo);
                    $("#idarticulo").val(ui.item.id);
                    $("#invdetid").val(ui.item.invdetid);
                    $("#lote").val(ui.item.lote);
                    $("#caducidad").val(ui.item.caducidad);
                    $("#serie").val(ui.item.serie);
                    return false;
                }
            });

            $(window).off('resize.autocompleteTraspaso').on('resize.autocompleteTraspaso', function() {
                if ($("#articulo").data("ui-autocomplete")) {
                    ajustarMenuAutocomplete($("#articulo"));
                }
            });

            $("#articulo").autocomplete("instance")._renderItem = function(ul, item) {
                const estado = obtenerEstadoCaducidad(item.caducidad);
                const caducidadTexto = item.caducidad ? item.caducidad : 'Sin caducidad';
                const loteHTML = item.lote ? '<span><b style="color:#94a3b8;">Lote:</b> ' + item.lote + '</span>' : '';

                return $('<li>')
                    .append(
                        '<div style="display:flex; justify-content:space-between; align-items:flex-start; padding:10px 12px; border-bottom:1px solid #f1f5f9; cursor:pointer; background:#fff;" ' +
                        'onmouseover="this.style.background=\'#f8fafc\'" ' +
                        'onmouseout="this.style.background=\'#fff\'">' +
                        '<div style="flex:1;">' +
                        '<div style="font-weight:600; color:#1e293b; font-size:14px; margin-bottom:4px;">' +
                        (item.nombre ? item.nombre.toUpperCase() : '') +
                        '</div>' +
                        '<div style="font-size:12px; color:#64748b; display:flex; gap:10px;">' +
                        '<span><b style="color:#94a3b8;">Folio:</b> ' + (item.folioarticulo || '') + '</span>' +
                        '<span><b style="color:#94a3b8;">Clave:</b> ' + (item.cvearticulo || '') + '</span>' +
                        loteHTML +
                        '</div>' +
                        '</div>' +
                        '<div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">' +
                        '<span style="background:' + estado.colorFondo + '; color:' + estado.colorTexto + '; font-size:11px; padding:3px 8px; border-radius:12px; font-weight:600; white-space:nowrap; border:1px solid rgba(0,0,0,0.05);">' +
                        estado.etiqueta +
                        '</span>' +
                        '<span style="font-size:12px; color:#475569; font-weight:500;">' +
                        '<i class="mdi mdi-calendar"></i> ' + caducidadTexto +
                        '</span>' +
                        '</div>' +
                        '</div>'
                    )
                    .appendTo(ul);
            };
        }

        const urlReqId = <?= $reqId ?>;
        const urlOriginId = <?= $originId ?>;
        const urlDestId = <?= $destId ?>;

        let allWarehousesData = [];

        // Get Almacenes para ambos combos al cargar la página
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2,3&todos=17", function(data) {
            allWarehousesData = data;
            itemsal += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#deinvalmacen").html(itemsal);
            $("#ainvalmacen").html(itemsal);

            if (urlDestId > 0) {
                $("#ainvalmacen").val(urlDestId).trigger("change");
            }
            if (urlOriginId > 0) {
                $("#deinvalmacen").val(urlOriginId).trigger("change");
            }
        });

        let articulosDisponiblesOrigen = [];

        let stockMinimosOrigen = {};

        // Evento cambio De Almacén
        $("#deinvalmacen").change(function() {
            const almacenId = $("#deinvalmacen").val();
            $("#deinsucursal").val("");
            articulosDisponiblesOrigen = [];
            stockMinimosOrigen = {};
            
            // Filter "A Almacén" options
            let optionsDest = "<option value=''></option>";
            let originWarehouse = allWarehousesData.find(w => String(w.ID) === String(almacenId));
            let originSucursalMs = originWarehouse ? String(originWarehouse.SUCURSAL_MS) : "";

            $.each(allWarehousesData, function(index, item) {
                // No permitir traspaso al mismo almacén
                if (String(item.ID) === String(almacenId)) {
                    return;
                }

                if (['4', '5', '6'].includes(String(item.TIPOALMACEN))) {
                    if (String(item.SUCURSAL_MS) === String(almacenId)) {
                        optionsDest += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                    }
                } else if (String(item.TIPOALMACEN) === '3') {
                    if (String(item.SUCURSAL_MS) === originSucursalMs) {
                        optionsDest += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                    }
                } else if (item.NOMBRE.toUpperCase().includes('CADUCADO')) {
                    // Permitir caducado si es de la misma sucursal, o si el origen es Saltillo CEDIS (almacenId == 1)
                    if (String(item.SUCURSAL_MS) === originSucursalMs || String(almacenId) === '1') {
                        optionsDest += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                    }
                } else {
                    optionsDest += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                }
            });
            
            let currentDest = $("#ainvalmacen").val();
            $("#ainvalmacen").html(optionsDest);
            if (currentDest) {
                $("#ainvalmacen").val(currentDest);
            }

            if (!almacenId) return;

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data.SUCURSAL_NOMBRE) $("#deinsucursal").val(data.SUCURSAL_NOMBRE);
            });

            // Cargar stock mínimos y artículos disponibles
            $("#deinvalmacenaux").val(almacenId);

            $.getJSON("../ajax/get.minimosalmacen.php?almacenid=" + almacenId, function(minimos) {
                if (Array.isArray(minimos)) {
                    minimos.forEach(function(m) {
                        stockMinimosOrigen[m.ARTICULO_ID] = parseFloat(m.CANTIDAD || 0);
                    });
                }

                $.getJSON("../ajax/get.articulosfiltrados.catalogo.php?almacenid=" + almacenId + "&incluirMaletas=1&filtros=vigentes,proximos,caducados", function(resultarticulos) {
                    articulosDisponiblesOrigen = resultarticulos || [];
                    inicializarAutocompleteArticulos(resultarticulos);

                    // Si hay un requerimiento seleccionado, intentar rellenar automáticamente los artículos
                    const reqId = $("#traspaso_requerimientomaterial").val();
                    if (reqId) {
                        autocompletarArticulosRequerimiento(reqId);
                    }
                });
            });
        });

        // Evento cambio A Almacén
        $("#ainvalmacen").change(function() {
            const almacenId = $("#ainvalmacen").val();
            $("#ainsucursal").val("");
            window.isProjectDest = false;
            window.allowedDestArticles = [];
            
            if (!almacenId) {
                $("#traspaso_requerimientomaterial").prop("disabled", true).html("<option value=''>Selecciona almacén de destino primero</option>");
                return;
            }
            
            $.getJSON("../ajax/get.articulos_permitidos_proyecto.php?almacenid=" + almacenId, function(data) {
                if (data && data.is_project) {
                    window.isProjectDest = true;
                    window.allowedDestArticles = data.allowed ? data.allowed.map(String) : [];
                }
            });

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data.SUCURSAL_NOMBRE) $("#ainsucursal").val(data.SUCURSAL_NOMBRE);
            });

            if (urlDestId > 0 && String(almacenId) === String(urlDestId) && urlReqId > 0) {
                cargarRequerimientosTraspaso(almacenId, urlReqId);
            } else {
                cargarRequerimientosTraspaso(almacenId);
            }
        });

        // Evento cambio Requerimiento
        $("#traspaso_requerimientomaterial").change(function() {
            const reqId = $(this).val();
            if (reqId) {
                autocompletarArticulosRequerimiento(reqId);
            } else {
                $("#productosBody").empty();
            }
        });

        function cargarRequerimientosTraspaso(almacenId, preselectedReqId = '') {
            const $sel = $("#traspaso_requerimientomaterial");
            $sel.prop("disabled", true).html("<option value=''>Cargando requerimientos...</option>");

            if (!almacenId) {
                $sel.prop("disabled", true).html("<option value=''>Selecciona almacén de destino primero</option>");
                return;
            }

            $.getJSON("../ajax/requerimientosmaterial.catalogo.php", {
                almacenid: almacenId,
                status: "1,2,19"
            }, function(resp) {
                let options = "<option value=''>Sin requerimiento</option>";
                const items = (resp && resp.ok && Array.isArray(resp.items)) ? resp.items : [];

                items.forEach(function(item) {
                    const folio = item.FOLIO || '';
                    const almacen = item.ALMACEN_NOMBRE || '';
                    const status = item.STATUS_NOMBRE || '';
                    const articulos = item.ARTICULOS || 0;
                    const selected = (preselectedReqId && String(item.ID) === String(preselectedReqId)) ? 'selected' : '';
                    options += `<option value="${item.ID}" ${selected}>${folio} - ${almacen} - ${status} (${articulos} art.)</option>`;
                });

                $sel.html(options).prop("disabled", false);

                if (preselectedReqId) {
                    autocompletarArticulosRequerimiento(preselectedReqId);
                }
            }).fail(function() {
                $sel.prop("disabled", true).html("<option value=''>No se pudieron cargar los requerimientos</option>");
            });
        }

        function autocompletarArticulosRequerimiento(reqId) {
            if (!reqId) return;
            const originAlmacenId = $("#deinvalmacenaux").val();
            if (!originAlmacenId) {
                return;
            }

            $.getJSON("../ajax/requerimientosmaterial.detalle.php", {
                reqid: reqId
            }, function(resp) {
                if (!resp || !resp.ok || !Array.isArray(resp.items)) {
                    Swal.fire({
                        icon: 'warning',
                        text: 'No se pudo cargar el detalle del requerimiento.'
                    });
                    return;
                }

                $("#productosBody").empty();

                let articulosNoEncontrados = [];
                let articulosLimitadosPorMinimo = [];

                resp.items.forEach(function(reqItem) {
                    const reqArtId = reqItem.articulo_id;
                    const reqCant = parseFloat(reqItem.cantidad || 1);
                    const reqNombre = reqItem.nombre;

                    const disponibles = articulosDisponiblesOrigen.filter(x => String(x.ID) === String(reqArtId));

                    disponibles.sort(function(a, b) {
                        const dateA = a.CADUCIDAD ? new Date(a.CADUCIDAD).getTime() : Infinity;
                        const dateB = b.CADUCIDAD ? new Date(b.CADUCIDAD).getTime() : Infinity;
                        return dateA - dateB;
                    });

                    // Regla de stock mínimo
                    const minStock = stockMinimosOrigen[reqArtId] || 0.0;
                    const disponiblesTotales = disponibles.length;
                    const transferible = Math.max(0, disponiblesTotales - minStock);

                    // Tomar la cantidad transferible autorizada
                    const cantATransferir = Math.min(reqCant, transferible);
                    const aAgregar = disponibles.slice(0, Math.ceil(cantATransferir));

                    if (disponiblesTotales === 0) {
                        articulosNoEncontrados.push(reqNombre);
                    } else if (cantATransferir < reqCant) {
                        const faltante = reqCant - cantATransferir;
                        articulosLimitadosPorMinimo.push(`• <b>${reqNombre}</b>: Se requieren ${reqCant}, pero solo ${cantATransferir} son transferibles sin bajar del stock mínimo de ${minStock} en el origen (faltante: ${faltante} requiere OC).`);
                    }

                    aAgregar.forEach(function(obj) {
                        var nuevaFila = `
                            <tr>
                                <td>
                                    <input type="hidden" name="idarticulofiltro[]" value="${obj.INVDETID}">
                                    <input type="hidden" name="invdetidarray[]" value="${obj.INVDETID}">
                                    <input type="text" class="form-control" value="${obj.STOCK_FOLIO}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="${obj.CLAVE_ARTICULO}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="${obj.NOMBRE.toUpperCase()}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="${obj.CADUCIDAD || 'Sin caducidad'}" readonly>
                                </td>
                                <td>
                                    <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila"
                                        style="cursor: pointer; width:20px !important; height: 20px !important;"
                                        title="Eliminar">
                                </td>
                            </tr>
                        `;
                        $("#productosBody").append(nuevaFila);
                    });
                });

                let msgHtml = "Artículos del requerimiento cargados con éxito.";
                let hasAlert = false;
                let alertIcon = "success";

                if (articulosNoEncontrados.length > 0 || articulosLimitadosPorMinimo.length > 0) {
                    hasAlert = true;
                    alertIcon = "warning";
                    msgHtml = "";
                    if (articulosNoEncontrados.length > 0) {
                        msgHtml += `<b>No se encontraron existencias en el origen para:</b><br>${articulosNoEncontrados.join('<br>')}<br><br>`;
                    }
                    if (articulosLimitadosPorMinimo.length > 0) {
                        msgHtml += `<b>Artículos limitados por regla de Stock Mínimo:</b><br>${articulosLimitadosPorMinimo.join('<br>')}`;
                    }
                }

                if (hasAlert) {
                    Swal.fire({
                        html: msgHtml,
                        icon: alertIcon,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                } else {
                    Swal.fire({
                        html: msgHtml,
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }

        // Agregar producto a la tabla
        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var folioarticulo = $("#folioarticulo").val();
            var cvearticulo = $("#cvearticulo").val();
            var invdetid = $("#invdetid").val();
            var caducidad = $("#caducidad").val();

            if (idArticulo === "" || articulo === "" || invdetid === "") {
                Swal.fire({
                    html: "Seleccione un artículo válido",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            var existe = false;
            $("input[name='invdetidarray[]']").each(function() {
                if ($(this).val() === invdetid) {
                    existe = true;
                    return false;
                }
            });
            if (existe) {
                Swal.fire({
                    html: "Este artículo ya fue agregado.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
                return;
            }

            var nuevaFila = `
                <tr>
                    <td>
                        <input type="hidden" name="idarticulofiltro[]" value="${invdetid}">
                        <input type="hidden" name="invdetidarray[]" value="${invdetid}">
                        <input type="text" class="form-control" value="${folioarticulo}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${cvearticulo}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${articulo}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control" value="${caducidad || 'Sin caducidad'}" readonly>
                    </td>
                    <td>
                        <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila"
                            style="cursor: pointer; width:20px !important; height: 20px !important;"
                            title="Eliminar">
                    </td>
                </tr>
            `;
            $("#productosBody").append(nuevaFila);

            $("#idarticulo").val("");
            $("#articulo").val("");
            $("#invdetid").val("");
            $("#folioarticulo").val("");
            $("#cvearticulo").val("");
            $("#lote").val("");
            $("#caducidad").val("");
            $("#serie").val("");
        });

        // Eliminar fila
        $(document).on("click", ".eliminarFila", function() {
            $(this).closest("tr").remove();
        });

    });

    function guardar() {
        Swal.fire({
            text: '¿Seguro que deseas guardar el traspaso de almacén?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#deinvalmacenaux").val() == "") {
                    Swal.fire({
                        html: "Almacén de origen es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#deinvalmacen').focus();
                        }
                    });
                } else if ($("#ainvalmacen").val() == "") {
                    Swal.fire({
                        html: "Almacén de destino es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#ainvalmacen').focus();
                        }
                    });
                } else if ($("#deinvalmacenaux").val() == $("#ainvalmacen").val()) {
                    Swal.fire({
                        html: "Almacén de destino debe ser diferente a almacén de origen",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#ainvalmacen').focus();
                        }
                    });
                } else if ($("#invmotivo").val() == "") {
                    Swal.fire({
                        html: "Motivo es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invmotivo').focus();
                        }
                    });
                } else if ($("#productosBody tr").length === 0) {
                    Swal.fire({
                        html: "Debes agregar al menos un artículo antes de guardar.",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-traspasos"));
                    $.ajax({
                        url: '../ajax/traspasos.registro.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'html',
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $("#loading").show();
                        },
                        success: function(response) {
                            if (response.startsWith("OK_SPECIAL|")) {
                                Swal.fire({
                                    html: "Traspaso enviado a revisión con éxito (Maleta)",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            } else if (response.startsWith("OK|")) {
                                var partes = response.split("|");
                                var nuevoId = partes[1];
                                Swal.fire({
                                    html: "Traspaso guardado con éxito.<br><br><b>Siguiente paso:</b> El almacén destino deberá realizar la recepción de esta mercancía en el sistema para que ingrese a su inventario.",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                }).then(() => {
                                    window.open("../gdocs/traspaso.etiqueta.php?traspasoid=" + btoa(nuevoId), "_blank");
                                    location.reload();
                                });
                            } else if (response.trim() === "") {
                                Swal.fire({
                                    html: "Traspaso guardado con éxito.<br><br><b>Siguiente paso:</b> El almacén destino deberá realizar la recepción de esta mercancía en el sistema para que ingrese a su inventario.",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: {
                                        confirmButton: 'btn btn-success'
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud:', error);
                        },
                        complete: function(data) {
                            $("#loading").hide();
                        }
                    });
                }
            } else {
                return false;
            }
        });
    }
</script>