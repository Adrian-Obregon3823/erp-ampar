<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<style>
    #rmSubtotal,
    #rmIva,
    #rmTotal {
        font-weight: bold;
        font-size: 1.1em;
    }

    #rmTotal {
        background-color: #f8f9fa;
        border: 2px solid #007bff;
    }

    /* Select2 UI Improvements */
    .select2-container--default .select2-results__option--highlighted[aria-selected] .item-title,
    .select2-container--default .select2-results__option--highlighted[aria-selected] .item-subtitle,
    .select2-container--default .select2-results__option--highlighted[aria-selected] .item-label,
    .select2-container--default .select2-results__option--highlighted[aria-selected] .item-icon {
        color: white !important;
    }
    
    .item-title { font-weight:600; color:#1e293b; font-size:14px; margin-bottom:4px; }
    .item-subtitle { font-size:12px; color:#64748b; display:flex; gap:10px; flex-wrap:wrap; }
    .item-label { color:#94a3b8; font-weight:bold; }
    .item-icon { color:#475569; }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        min-height: calc(2.25rem + 2px) !important;
        height: auto !important;
        padding-bottom: 5px !important;
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        height: calc(2.25rem + 2px) !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 0.75rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
    }
    
    /* Asegurar que el texto que se escribe en el buscador sea visible (color oscuro y tamaño adecuado) */
    .select2-container--default .select2-search--inline .select2-search__field {
        color: #495057 !important;
        font-family: inherit;
        line-height: 1.5;
        margin-top: 5px;
        min-width: 200px !important;
    }

    /* Mejorar el diseño de las etiquetas seleccionadas (quitar los colores variados del tema) */
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        padding: 0 5px 5px 5px !important;
        box-sizing: border-box;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .select2-container--default .select2-selection--multiple .select2-selection__choice:nth-child(n) {
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        color: #334155 !important;
        border-radius: 6px !important;
        padding: 3px 8px 3px 26px !important; /* Espacio a la izquierda para la X */
        position: relative !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        margin: 5px 5px 0 0 !important;
        display: inline-block !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        padding: 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
    .select2-container--default .select2-selection--multiple .select2-selection__choice:nth-child(n) .select2-selection__choice__remove {
        color: #94a3b8 !important;
        border: none !important;
        border-right: 1px solid #e2e8f0 !important;
        background: transparent !important;
        font-weight: bold;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        height: 100% !important;
        width: 22px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ef4444 !important;
        background-color: #fee2e2 !important;
    }
</style>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nueva Remisión</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-remision-mostrador">
                <input type="hidden" id="invtipo" name="invtipo" value="S">
                <input type="hidden" id="tiposalida" name="tiposalida" value="VENTA_DIRECTA">

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="cliente_mostrador">Cliente <span class="text-danger">*</span></label>
                            <select class="form-control" id="cliente_mostrador" name="cliente_mostrador" style="width:100%"></select>
                        </div>
                        <div class="form-group mt-3">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <div class="form-group row">
                                <div class="col-md-9">
                                    <select class="form-control" id="invalmacen" name="invalmacen"></select>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-warning w-100 text-nowrap" id="btnLimpiarRemisionMostrador">Cambiar Almacén</button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="maletaid" name="maletaid" value="">

                        <div class="row align-items-end">
                            <div class="col-12 col-md-9 mb-2 mb-md-0">
                                <div class="form-group mb-md-0">
                                    <select id="articulo" name="articulo[]" multiple="multiple" style="width:100%" data-placeholder="Buscar artículos..."></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="form-group mb-md-0 mt-2 mt-md-0">
                                    <button type="button" class="btn btn-warning w-100" id="btnAgregarArticuloMostrador">Agregar Artículos</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Folio</th>
                                        <th>Cve</th>
                                        <th>Artículo</th>
                                        <th>Subtotal</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="articulosMostradorBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-2 text-end">
                    <div class="row justify-content-end mb-2">
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="actualizarTotales();" title="Recalcular totales desde los artículos">
                                <i class="mdi mdi-calculator"></i> Recalcular Totales
                            </button>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="rmSubtotal"><strong>Subtotal:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end" id="rmSubtotal" name="rmSubtotal" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="rmIva"><strong>IVA:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end" id="rmIva" name="rmIva" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="rmTotal"><strong>Total:</strong></label>
                                <input type="number" step="0.01" class="form-control text-end font-weight-bold" id="rmTotal" name="rmTotal" value="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="btnGuardarRemisionMostrador">Guardar manual</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    let articulosDisponibles = [];

    function actualizarTotales() {
        let subtotal = 0;
        let iva = 0;
        let total = 0;

        $("#articulosMostradorBody tr").each(function() {
            subtotal += parseFloat($(this).find("input[name='subtotalarray[]']").val() || 0);
            iva += parseFloat($(this).find("input[name='ivaarray[]']").val() || 0);
            total += parseFloat($(this).find("input[name='totalarray[]']").val() || 0);
        });

        $("#rmSubtotal").val(subtotal.toFixed(2));
        $("#rmIva").val(iva.toFixed(2));
        $("#rmTotal").val(total.toFixed(2));
    }

    window.recalcularFila = function(el) {
        const tr = $(el).closest('tr');
        let sub = parseFloat($(el).val()) || 0;
        let iva = sub * 0.16;
        let total = sub + iva;
        
        tr.find('.input-iva-row').val(iva.toFixed(2));
        tr.find('.input-total-row').val(total.toFixed(2));
        
        actualizarTotales();
    };

    function limpiarArticuloTemporal() {
        if ($("#articulo").hasClass("select2-hidden-accessible")) {
            $("#articulo").val(null).trigger("change");
        }
    }

    function cargarAlmacenes() {
        let options = "<option value=''></option>";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            $.each(data, function(_, item) {
                options += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(options);
        });
    }

    function cargarCatalogoArticulos() {
        const almacenId = $("#invalmacen").val();
        const tipoSalida = $("#tiposalida").val();
        const maletaId = $("#maletaid").val();
        const clienteId = $("#cliente_mostrador").val();

        if (!almacenId || !tipoSalida) {
            articulosDisponibles = [];
            if ($("#articulo").data("ui-autocomplete")) {
                $("#articulo").autocomplete("destroy");
            }
            return;
        }

        $.getJSON("../ajax/get.articulos.remision.mostrador.php", {
            almacenid: almacenId,
            tiposalida: tipoSalida,
            clienteid: clienteId || "",
            maletaid: maletaId || ""
        }, function(data) {
            articulosDisponibles = data || [];
            inicializarAutocomplete();
        });
    }



    function inicializarAutocomplete() {
        if ($("#articulo").hasClass("select2-hidden-accessible")) {
            $("#articulo").select2("destroy");
            $("#articulo").empty();
        }

        const idsEnTabla = new Set();
        $("#articulosMostradorBody tr").each(function() {
            const invdet = $(this).find("input[name='invdetidarray[]']").val();
            if (invdet) idsEnTabla.add(String(invdet));
        });

        const selectData = articulosDisponibles
            .filter(item => !idsEnTabla.has(String(item.INVDETID)))
            .map(item => {
                return {
                    id: item.INVDETID,
                    text: (item.STOCK_FOLIO || "") + " - (" + (item.CLAVE_ARTICULO || "") + ") " + (item.NOMBRE || ""),
                    data: item
                };
            });

        function matchCustom(params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }
            if (typeof data.text === 'undefined') {
                return null;
            }
            
            const term = params.term.toLowerCase();
            const textToSearch = data.text.toLowerCase() + " " + (data.data.LOTE || "").toLowerCase();
            
            // Check if all words in the search term exist in the text
            const words = term.split(" ");
            let allWordsMatch = true;
            for (let i = 0; i < words.length; i++) {
                if (textToSearch.indexOf(words[i]) === -1) {
                    allWordsMatch = false;
                    break;
                }
            }
            
            if (allWordsMatch) {
                return data;
            }
            return null;
        }

        $("#articulo").select2({
            dropdownParent: $("#modalglobal"),
            data: selectData,
            multiple: true,
            placeholder: 'Buscar artículos...',
            templateResult: formatSelect2Item,
            templateSelection: formatSelect2Selection,
            closeOnSelect: false,
            matcher: matchCustom
        });
    }

    function obtenerEstadoCaducidad(fechaCaducidad) {
        if (!fechaCaducidad) {
            return { colorFondo: '#f8fafc', colorTexto: '#64748b', etiqueta: 'Sin caducidad' };
        }
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fecha = new Date(fechaCaducidad + 'T00:00:00');
        if (isNaN(fecha.getTime())) {
            return { colorFondo: '#f8fafc', colorTexto: '#64748b', etiqueta: 'Sin caducidad' };
        }
        const unMesDespues = new Date(hoy);
        unMesDespues.setMonth(unMesDespues.getMonth() + 1);
        if (fecha < hoy) {
            return { colorFondo: '#fee2e2', colorTexto: '#991b1b', etiqueta: 'Caducado' };
        }
        if (fecha <= unMesDespues) {
            return { colorFondo: '#fff3cd', colorTexto: '#8a6d1f', etiqueta: 'Menos de 1 mes' };
        }
        return { colorFondo: '#dff4e4', colorTexto: '#2f6d3f', etiqueta: 'Vigente' };
    }

    function formatSelect2Selection(state) {
        if (!state.id) return state.text;
        const item = state.data;
        // Mostrar algo corto en los "chips" de la caja
        return $(`<span style="font-size:0.85rem;"><b>${item.STOCK_FOLIO}</b> (${item.CLAVE_ARTICULO})</span>`);
    }

    function formatSelect2Item(state) {
        if (!state.id) return state.text;
        const item = state.data;
        const estado = obtenerEstadoCaducidad(item.CADUCIDAD);
        const caducidadTexto = item.CADUCIDAD ? item.CADUCIDAD : 'Sin caducidad';
        const loteHTML = item.LOTE ? `<span><span class="item-label">Lote:</span> ${item.LOTE}</span>` : '';

        return $(`
            <div style="display:flex; justify-content:space-between; align-items:flex-start; width:100%;">
                <div style="flex:1;">
                    <div class="item-title">
                        ${item.NOMBRE ? item.NOMBRE.toUpperCase() : ''}
                    </div>
                    <div class="item-subtitle">
                        <span><span class="item-label">Folio:</span> ${item.STOCK_FOLIO || ''}</span>
                        <span><span class="item-label">Clave:</span> ${item.CLAVE_ARTICULO || ''}</span>
                        ${loteHTML}
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px; margin-left: 10px;">
                    <span style="background:${estado.colorFondo}; color:${estado.colorTexto}; font-size:11px; padding:3px 8px; border-radius:12px; font-weight:600; white-space:nowrap; border:1px solid rgba(0,0,0,0.05);">
                        ${estado.etiqueta}
                    </span>
                    <span style="font-size:12px; font-weight:500;" class="item-icon">
                        <i class="mdi mdi-calendar"></i> ${caducidadTexto}
                    </span>
                </div>
            </div>
        `);
    }

    // Prevenir que el modal bloquee el foco del input de búsqueda en Select2
    if ($.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype._enforceFocus = function() {};
    }
    
    // Remover tabindex y eventos de focusin del modal global para asegurar que Select2 funcione
    setTimeout(function() {
        $('#modalglobal').removeAttr('tabindex');
        $('#modalglobal').off('focusin.modal');
    }, 500);

    $(document).ready(function() {
        cargarAlmacenes();

        $('#cliente_mostrador').select2({
            dropdownParent: $('#modalglobal'),
            placeholder: 'Selecciona un cliente',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '../ajax/precios.buscar_hospitales.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term, solo_clientes: 1 };
                },
                processResults: function(data) {
                    return {
                        results: (data.items || []).map(it => ({ id: it.CLIENTE_ID, text: it.NOMBRE }))
                    };
                },
                cache: true
            }
        });

        // Al cambiar de cliente, recargar los artículos disponibles para actualizar sus precios si aplica
        $("#cliente_mostrador").on("change", function() {
            if ($("#invalmacen").val()) {
                cargarCatalogoArticulos();
            }
        });


        $("#invalmacen").on("change", function() {
            $("#articulosMostradorBody").empty();
            limpiarArticuloTemporal();
            actualizarTotales();
            $("#maletaid").val("");
            cargarCatalogoArticulos();
        });



        $("#btnLimpiarRemisionMostrador").on("click", function() {
            $("#invalmacen").val("");
            $("#maletaid").val("");
            $("#articulosMostradorBody").empty();
            limpiarArticuloTemporal();
            actualizarTotales();
            $("#tiposalida").val('VENTA_DIRECTA');
        });

        $("#btnAgregarArticuloMostrador").on("click", function() {
            const selecciones = $("#articulo").select2('data');

            if (!selecciones || selecciones.length === 0) {
                Swal.fire({
                    icon: "warning",
                    html: "Selecciona al menos un artículo de la lista."
                });
                return;
            }

            selecciones.forEach(sel => {
                const item = sel.data;
                const idArticulo = item.ID || "";
                const invdetid = item.INVDETID || "";
                const nombre = item.NOMBRE || "";
                const sub = parseFloat(item.SUBTOTAL || 0);
                const iv = parseFloat(item.IVA || 0);
                const tot = parseFloat(item.TOTAL || 0);

                const row = `
                <tr>
                    <td style="vertical-align: middle;">${$("#articulosMostradorBody tr").length + 1}</td>
                    <td>
                        <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                        <input type="hidden" name="invdetidarray[]" value="${invdetid}">
                        <input type="hidden" name="lotearray[]" value="${item.LOTE || ''}">
                        <input type="hidden" name="caducidadarray[]" value="${item.CADUCIDAD || ''}">
                        <input type="hidden" name="seriearray[]" value="${item.SERIE || ''}">
                        <input type="hidden" name="cantidadarray[]" value="1">
                        <input type="text" class="form-control" style="background:#eef2f5; border:1px solid #d1d5db; color:#4b5563;" readonly value="${item.STOCK_FOLIO || ''}">
                    </td>
                    <td>
                        <input type="text" class="form-control" style="background:#eef2f5; border:1px solid #d1d5db; color:#4b5563;" readonly value="${item.CLAVE_ARTICULO || ''}">
                    </td>
                    <td>
                        <input type="text" class="form-control" style="background:#eef2f5; border:1px solid #d1d5db; color:#4b5563;" readonly value="${nombre}">
                    </td>
                    <td><input type="number" step="0.01" class="form-control input-subtotal-row" name="subtotalarray[]" value="${sub.toFixed(2)}" oninput="recalcularFila(this)"></td>
                    <td><input type="number" step="0.01" class="form-control input-iva-row" name="ivaarray[]" value="${iv.toFixed(2)}" readonly style="background:#eef2f5; border:1px solid #d1d5db; color:#4b5563;"></td>
                    <td><input type="number" step="0.01" class="form-control input-total-row" name="totalarray[]" value="${tot.toFixed(2)}" readonly style="background:#eef2f5; border:1px solid #d1d5db; color:#4b5563;"></td>
                    <td style="vertical-align: middle;">
                        <button type="button" class="btn btnQuitarArticulo" style="border-radius:50%; width:36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center; background:#ff6b6b; color:white; border:none;">
                            <i class="mdi mdi-delete" style="margin:0; font-size:16px;"></i>
                        </button>
                    </td>
                </tr>
                `;
                $("#articulosMostradorBody").append(row);
            });

            actualizarTotales();
            limpiarArticuloTemporal();
            inicializarAutocomplete(); // Refrescar para quitar los agregados de la lista
        });

        $(document).on("click", ".btnQuitarArticulo", function() {
            $(this).closest("tr").remove();
            $("#articulosMostradorBody tr").each(function(index) {
                $(this).children("td").first().text(index + 1);
            });
            actualizarTotales();
        });

        $("#btnGuardarRemisionMostrador").on("click", function() {
            const almacen = $("#invalmacen").val();
            const tipoSalida = $("#tiposalida").val();
            const maletaId = $("#maletaid").val();
            const totalRows = $("#articulosMostradorBody tr").length;

            if (!almacen || !tipoSalida) {
                Swal.fire({
                    icon: "warning",
                    html: "Completa todos los campos obligatorios."
                });
                return;
            }

            if (totalRows === 0) {
                Swal.fire({
                    icon: "warning",
                    html: "Agrega al menos un artículo."
                });
                return;
            }

            const formData = new FormData(document.getElementById("form-remision-mostrador"));
            $.ajax({
                url: "../ajax/remisiones.mostrador.guardar.php",
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
                            html: (resp.message || "Salida registrada") + "<br><b>Folio: " + (resp.folio || "") + "</b>"
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "warning",
                            html: (resp && resp.message) ? resp.message : "No fue posible guardar la salida."
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        html: "Error al guardar la salida."
                    });
                },
                complete: function() {
                    $("#loading").hide();
                }
            });
        });
    });
</script>