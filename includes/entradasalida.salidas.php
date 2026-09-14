<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/head.php"); ?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        /* Asegura que esté sobre el modal (Bootstrap usa 1050) */
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
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Salida de Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-salida-inventario">
                <input type="hidden" id="invtipo" name="invtipo" value="S">
                <input type="hidden" id="idproveedor" name="idproveedor" value="">
                <input type="hidden" id="correoproveedor" name="correoproveedor" value="">
                <div class="row">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="invalmacen" name="invalmacen"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group">
                            <label for="invsucursal">Categoría</label>
                            <input type="text" id="invsucursal" name="invsucursal" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="invtipoalmacen">Tipo de Almacén</label>
                            <input type="text" id="invtipoalmacen" name="invtipoalmacen" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="form-group">
                            <label for="invconcepto">Concepto <span class="text-danger">*</span></label>
                            <select class="form-control" id="invconcepto" name="invconcepto"></select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invmotivo">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo de Salida" maxlength="250"></textarea>
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
                            <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Artículo">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="form-group mb-md-0 mt-2 mt-md-0">
                            <button type="button" class="btn btn-warning w-100" id="agregarProducto">Agregar Producto</button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="150px">Folio</th>
                                <th width="150px">Clave</th>
                                <th>Artículo</th>
                                <th width="150px">Caducidad</th>
                                <th width="100px">Cantidad</th>
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
<script>
    $(document).ready(function() {

        function obtenerEstadoCaducidad(fechaCaducidad) {
            if (!fechaCaducidad) {
                return {
                    clase: 'estado-verde',
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
                    clase: 'estado-verde',
                    colorFondo: '#dff4e4',
                    colorTexto: '#2f6d3f',
                    etiqueta: 'Sin caducidad'
                };
            }

            const unMesDespues = new Date(hoy);
            unMesDespues.setMonth(unMesDespues.getMonth() + 1);

            if (fecha < hoy) {
                return {
                    clase: 'estado-rojo',
                    colorFondo: '#f8d7da',
                    colorTexto: '#8a1f2d',
                    etiqueta: 'Caducado'
                };
            }

            if (fecha <= unMesDespues) {
                return {
                    clase: 'estado-amarillo',
                    colorFondo: '#fff3cd',
                    colorTexto: '#8a6d1f',
                    etiqueta: 'Menos de 1 mes'
                };
            }

            return {
                clase: 'estado-verde',
                colorFondo: '#dff4e4',
                colorTexto: '#2f6d3f',
                etiqueta: 'Vigente'
            };
        }

        function inicializarAutocompleteArticulos(resultarticulos) {
            const normalizarTexto = function(texto) {
                return (texto || '')
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toUpperCase();
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

            const obtenerInvdetIdsEnTabla = function() {
                const ids = new Set();
                $('#productosBody tr').each(function() {
                    const invdetid = $(this).find('input[name="invdetidarray[]"]').val();
                    if (invdetid) {
                        ids.add(String(invdetid));
                    }
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
                    const idsAgregados = obtenerInvdetIdsEnTabla();

                    // Función auxiliar para obtener prioridad de caducidad (menor = primero)
                    function getPrioridadCaducidad(fechaCaducidad) {
                        if (!fechaCaducidad) return 3; // Sin caducidad al final (verde)
                        const hoy = new Date();
                        hoy.setHours(0, 0, 0, 0);
                        const fecha = new Date(fechaCaducidad + 'T00:00:00');
                        if (isNaN(fecha.getTime())) return 3;
                        const unMesDespues = new Date(hoy);
                        unMesDespues.setMonth(unMesDespues.getMonth() + 1);
                        if (fecha < hoy) return 1; // Caducado (rojo) - primero
                        if (fecha <= unMesDespues) return 2; // Próximo a caducar (amarillo) - segundo
                        return 3; // Vigente (verde) - último
                    }

                    var resultados = $.map(resultarticulos, function(obj) {
                        var label = obj.STOCK_FOLIO + ' - ' + '(' + obj.CLAVE_ARTICULO + ') ' + obj.NOMBRE.toUpperCase();
                        const searchable = normalizarTexto((obj.STOCK_FOLIO || '') + ' ' + (obj.CLAVE_ARTICULO || '') + ' ' + (obj.NOMBRE || ''));
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

                    // Ordenar: caducados (rojo) primero, próximos a caducar (amarillo) segundo, vigentes (verde) al final
                    resultados.sort(function(a, b) {
                        var prioA = getPrioridadCaducidad(a.caducidad);
                        var prioB = getPrioridadCaducidad(b.caducidad);
                        if (prioA !== prioB) return prioA - prioB;
                        // Dentro del mismo grupo, ordenar por fecha de caducidad ascendente (más próxima primero)
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

            $(window).off('resize.autocompleteSalida').on('resize.autocompleteSalida', function() {
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

        //Get Conceptos de inventario
        var items1 = "";
        $.getJSON("../ajax/get.inventarios.conceptos.php?naturalezaconcepto=S", function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                var filtro = item.FILTRO ? item.FILTRO : '';
                items1 += "<option value='" + item.ID + "' data-filtro='" + filtro + "'>" + item.NOMBRE + "</option>";
            });
            $("#invconcepto").html(items1);
        });

        //Get Almacenes
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            itemsal += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(itemsal);
        });

        async function obtenerArticulos(almacenid) {
            let filtro = $("#invconcepto option:selected").data("filtro");
            let prm = "";
            if (filtro) {
                prm = "&filtros=" + filtro;
            } else {
                // Si no hay filtro configurado, por defecto mostramos vigentes
                prm = "&filtros=vigentes";
            }
            return await $.getJSON("../ajax/get.articulosfiltrados.catalogo.php?almacenid=" + almacenid + "&incluirMaletas=1" + prm);
        }

        $("#invconcepto").change(function() {
            if ($("#invalmacen").val()) {
                obtenerArticulos($("#invalmacen").val()).then(function(resultarticulos) {
                    inicializarAutocompleteArticulos(resultarticulos);
                });
            }
        });

        $("#invalmacen").change(function() {
            const almacenId = $("#invalmacen").val();
            $("#invsucursal").val("");
            $("#invtipoalmacen").val("");

            if (!almacenId) {
                return;
            }

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data.SUCURSAL_NOMBRE) {
                    $("#invsucursal").val(data.SUCURSAL_NOMBRE);
                }
                if (data.TIPOALMACEN_NOMBRE) {
                    $("#invtipoalmacen").val(data.TIPOALMACEN_NOMBRE);
                }
            });

            // Obtener artículos del almacén seleccionado
            obtenerArticulos($("#invalmacen").val()).then(function(resultarticulos) {
                inicializarAutocompleteArticulos(resultarticulos);
            });
        });


        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var folioarticulo = $("#folioarticulo").val();
            var cvearticulo = $("#cvearticulo").val();
            var invdetid = $("#invdetid").val();
            var lote = $("#lote").val();
            var caducidad = $("#caducidad").val();
            var serie = $("#serie").val();

            if (idArticulo === "" || articulo === "" || invdetid === "") {
                Swal.fire({
                    html: "Seleccione un artículo válido",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
                return;
            }

            // Validar si ya existe un artículo con ese ID
            var existe = false;
            $("input[name='invdetidarray[]']").each(function() {
                if ($(this).val() === invdetid) {
                    existe = true;
                    return false; // salir del loop
                }
            });

            if (existe) {
                Swal.fire({
                    html: "Este artículo ya fue agregado.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
                return;
            }

            var nuevaFila = `
                <tr>
                    <td>
                        <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                        <input type="hidden" name="invdetidarray[]" value="${invdetid}">
                        <input type="hidden" name="lotearray[]" value="${lote}">
                        <input type="hidden" name="caducidadarray[]" value="${caducidad}">
                        <input type="hidden" name="seriearray[]" value="${serie}">
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
                    <td>1</td>
                    <td>
                        <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                    </td>
                </tr>
            `;

            $("#productosBody").append(nuevaFila);

            // Limpiar los campos después de agregar
            $("#idarticulo").val("");
            $("#articulo").val("");
            $("#invdetid").val("");
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
            text: '¿Seguro que deseas crear la solicitud de salida?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if (!$("#invalmacen").val()) {
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#invalmacen').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#invconcepto").val() == "") {
                    Swal.fire({
                        html: "Concepto es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#invconcepto').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#invmotivo").val() == "") {
                    Swal.fire({
                        html: "Descripción es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#invmotivo').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-salida-inventario"));
                    $.ajax({
                        url: '../ajax/entradasalida.registro.php',
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
                            if (response.trim() === "") {
                                Swal.fire({
                                    html: "Registro guardado con éxito",
                                    icon: "success",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                                    }
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: {
                                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
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