<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$entradaid = isset($_GET['esid']) ? base64_decode($_GET['esid']) : '';
$entradasalida = new entradasalida();
$res = $entradasalida->getinfoentradasalidabyid($entradaid);
?>
<style>
    .caducidad-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
    }

    .ui-autocomplete {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
</style>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Edición de Salida a Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editentrada-inventario">
                <input type="hidden" name="cabeceraEditada" id="cabeceraEditada" value="0">
                <input type="hidden" name="invtipo" id="invtipo" value="S">
                <input type="hidden" id="esid" name="esid" value="<?= $entradaid ?>">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="invalmacen" name="invalmacen">
                                <option value="<?= $res[0]['ALMACEN_ID'] ?>"><?= $res[0]['ALMACEN_NOMBRE'] ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="invsucursal">Categoría</label>
                            <input type="text" id="invsucursal" name="invsucursal" class="form-control" value="<?= $res[0]['SUCURSAL_NOMBRE'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="invtipoalmacen">Tipo de Almacén</label>
                            <input type="text" id="invtipoalmacen" name="invtipoalmacen" class="form-control" value="<?= $res[0]['TIPOALMACEN_NOMBRE'] ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
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
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo" maxlength="250"><?= $res[0]['ES_MOTIVO'] ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <input type="hidden" id="idarticulo" name="idarticulo">
                            <input type="hidden" id="invdetid" name="invdetid">
                            <input type="hidden" id="folioarticulo" name="folioarticulo">
                            <input type="hidden" id="seguimiento" name="seguimiento">
                            <input type="hidden" id="lote" name="lote">
                            <input type="hidden" id="caducidad" name="caducidad">
                            <input type="hidden" id="serie" name="serie">
                            <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Artículo">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <button type="button" class="btn btn-warning" id="agregarProducto">Agregar Producto</button>
                        </div>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Artículo</th>
                            <th>Lote</th>
                            <th>Caducidad</th>
                            <th># Serie</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="productosBody">
                        <?php if ($res[0]['ESDET_ID'] <> "") { ?>
                            <?php foreach ($res as $d): ?>
                                <tr data-esdet="<?= $d['ESDET_ID'] ?>" class="filaOriginal"
                                    data-articulo="<?= $d['CLAVE_ARTICULO'] ?> - <?= $d['ARTICULO_NOMBRE'] ?>"
                                    data-lote="<?= $d['ESDET_LOTE'] ?>"
                                    data-caducidad="<?= $d['ESDET_CADUCIDAD'] ?>"
                                    data-serie="<?= $d['ESDET_SERIE'] ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <b>&#10132;</b>
                                            <input type="hidden" name="esdetidarray[]" value="<?= $d['ESDET_ID'] ?>">
                                            <input type="hidden" name="invdetidarray[]" value="<?= $d['ESDET_STOCKIDSALIDA'] ?>">
                                            <input type="hidden" name="idarticuloarray[]" value="<?= $d['ESDET_ARTICULOID'] ?>">
                                            <input type="hidden" name="seguimientoarray[]" value="<?= $d['SEGUIMIENTO'] ?>">
                                            <input type="hidden" name="estadoarray[]" value="original"> <!-- NUEVO -->
                                            <input type="text" class="form-control" value="<?= $d['STOCK_FOLIO'] ?>" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" value="<?= $d['CLAVE_ARTICULO'] ?> - <?= $d['ARTICULO_NOMBRE'] ?>" readonly>
                                    </td>
                                    <td><input type="text" class="form-control loteInput" name="lotearray[]" value="<?= $d['ESDET_LOTE'] ?>" readonly></td>
                                    <td>
                                        <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="<?= $d['ESDET_CADUCIDAD'] ?>" readonly>
                                    </td>
                                    <td><input type="text" class="form-control serieInput" name="seriearray[]" value="<?= $d['ESDET_SERIE'] ?>" readonly></td>
                                    <td>
                                        <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor:pointer;width:20px;height:20px;" title="Eliminar">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>
                <br>
                <button type="button" class="btn btn-success" onclick="editar();">Modificar</button>
            </form>
        </div>
    </div>
</div>
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
                    const estado = $(this).find('input[name="estadoarray[]"]').val();
                    if (estado === 'eliminado') {
                        return;
                    }

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
                        var label = obj.STOCK_FOLIO + ' - ' + obj.NOMBRE.toUpperCase() + ' (' + obj.CLAVE_ARTICULO + ')';
                        var label2 = obj.NOMBRE.toUpperCase() + ' (' + obj.CLAVE_ARTICULO + ')';
                        const searchable = normalizarTexto((obj.STOCK_FOLIO || '') + ' ' + (obj.CLAVE_ARTICULO || '') + ' ' + (obj.NOMBRE || ''));
                        if (searchable.includes(termino) && !idsAgregados.has(String(obj.INVDETID))) {
                            return {
                                label: label,
                                label2: label2,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                seguimiento: obj.SEGUIMIENTO,
                                cve: obj.CLAVE_ARTICULO,
                                invdetid: obj.INVDETID,
                                lote: obj.LOTE,
                                caducidad: obj.CADUCIDAD,
                                serie: obj.SERIE,
                                folioarticulo: obj.STOCK_FOLIO
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
                    $("#articulo").val(ui.item.label2);
                    $("#idarticulo").val(ui.item.id);
                    $("#seguimiento").val(ui.item.seguimiento);
                    $("#invdetid").val(ui.item.invdetid);
                    $("#lote").val(ui.item.lote);
                    $("#caducidad").val(ui.item.caducidad);
                    $("#serie").val(ui.item.serie);
                    $("#folioarticulo").val(ui.item.folioarticulo);
                    return false;
                }
            });

            $(window).off('resize.autocompleteSalidaEdit').on('resize.autocompleteSalidaEdit', function() {
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
            $("#invconcepto").val("<?= $res[0]['ES_CONCEPTOID'] ?>");
        });

        //Get Almacenes
        var itemsal = "";
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            itemsal += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(itemsal);
            $("#invalmacen").val("<?= $res[0]['ALMACEN_ID'] ?>");
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

            // Actualizar artículos disponibles según el nuevo almacén
            obtenerArticulos(almacenId).then(function(resultarticulos) {
                inicializarAutocompleteArticulos(resultarticulos);
            });
        });

        async function obtenerArticulos(almacenid) {
            let filtro = $("#invconcepto option:selected").data("filtro");
            let prm = "";
            if (filtro) {
                prm = "&filtros=" + filtro;
            } else {
                prm = "&filtros=vigentes";
            }
            return await $.getJSON("../ajax/get.articulosfiltrados.catalogo.php?almacenid=" + almacenid + "&incluirMaletas=1" + prm);
        }

        obtenerArticulos('<?= $res[0]['ES_ALMACENID'] ?>').then(function(resultarticulos) {
            inicializarAutocompleteArticulos(resultarticulos);
        });

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var seguimiento = $("#seguimiento").val();
            var invdetid = $("#invdetid").val();
            var folioarticulo = $("#folioarticulo").val();
            var lote = $("#lote").val();
            var caducidad = $("#caducidad").val();
            var serie = $("#serie").val();

            if (idArticulo === "" || articulo === "" || invdetid === "") {
                Swal.fire({
                    html: "Seleccione un artículo válido.",
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

            for (let i = 0; i < 1; i++) {
                // Determinar los atributos readonly según el seguimiento
                let readonlyLote = '';
                let readonlyCaducidad = '';
                let readonlySerie = '';

                if (seguimiento === 'N') {
                    readonlyLote = 'readonly';
                    readonlyCaducidad = 'readonly';
                    readonlySerie = 'readonly';
                } else if (seguimiento === 'L') {
                    readonlySerie = 'readonly';
                } else if (seguimiento === 'S') {
                    readonlyLote = 'readonly';
                    readonlyCaducidad = 'readonly';
                }

                //Como es salida todo es readonly
                readonlyLote = 'readonly';
                readonlyCaducidad = 'readonly';
                readonlySerie = 'readonly';

                var nuevaFila = `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <b>&#10132;</b>
                                <input type="hidden" name="esdetidarray[]" value=""> <!-- vacío -->
                                <input type="hidden" name="invdetidarray[]" value="${invdetid}">
                                <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                                <input type="hidden" name="seguimientoarray[]" value="${seguimiento}">
                                <input type="hidden" name="estadoarray[]" value="nuevo">
                                <input type="text" class="form-control" value="${folioarticulo}" readonly>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="form-control" value="${articulo}" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control loteInput" name="lotearray[]" placeholder="Lote" value="${lote}" ${readonlyLote}>
                        </td>
                        <td>
                            <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="${caducidad}" ${readonlyCaducidad}>
                        </td>
                        <td>
                            <input type="text" class="form-control serieInput" name="seriearray[]" value="${serie}" placeholder="Serie" ${readonlySerie}>
                        </td>
                        <td>
                            <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                        </td>
                    </tr>
                `;
                $("#productosBody").append(nuevaFila);
            }

            // Limpiar los campos después de agregar
            $("#idarticulo").val("");
            $("#articulo").val("");
            $("#seguimiento").val("");
            $("#invdetid").val("");
            $("#folioarticulo").val("");
            $("#lote").val("");
            $("#caducidad").val("");
            $("#serie").val("");

        });

        $(document).on("click", ".eliminarFila", function() {
            const tr = $(this).closest("tr");

            // Si es fila original, marcarla como eliminada
            if (tr.hasClass("filaOriginal")) {
                tr.find('input[name="estadoarray[]"]').val('eliminado');
                tr.hide(); // opcional, para que no se vea
            } else {
                // Si es fila nueva, simplemente eliminar
                tr.remove();
            }
        });

        $(document).on("change", ".loteInput, .caducidadInput, .serieInput", function() {
            const tr = $(this).closest("tr");
            if (tr.hasClass("filaOriginal")) {
                tr.find('input[name="estadoarray[]"]').val('editado');
            }
        });

        $("#invconcepto, #invmotivo").on("change input", function() {
            $("#cabeceraEditada").val("1");
        });

        $("#invconcepto").change(function() {
            if ($("#invalmacen").val()) {
                obtenerArticulos($("#invalmacen").val()).then(function(resultarticulos) {
                    inicializarAutocompleteArticulos(resultarticulos);
                });
            }
        });

    });

    // 1. Guardamos snapshot de originales al cargar
    var originales = {
        cabecera: {
            almacen: "<?= $res[0]['ALMACEN_NOMBRE'] ?>",
            almacenid: "<?= $res[0]['ALMACEN_ID'] ?>",
            sucursal: "<?= $res[0]['SUCURSAL_NOMBRE'] ?>",
            concepto: "<?= $res[0]['CONCEPTO_NOMBRE'] ?>",
            motivo: "<?= $res[0]['ES_MOTIVO'] ?>"
        },
        articulos: <?= json_encode(array_map(function ($d) {
                        return [
                            "id" => $d['ESDET_ID'],   // ID único de detalle
                            "invdetid" => $d['ESDET_STOCKIDSALIDA'],
                            "folioarticulo" => $d['STOCK_FOLIO'],
                            "nombre" => $d['CLAVE_ARTICULO'] . " - " . $d['ARTICULO_NOMBRE'],
                            "lote" => $d['ESDET_LOTE'],
                            "caducidad" => $d['ESDET_CADUCIDAD'],
                            "serie" => $d['ESDET_SERIE']
                        ];
                    }, $res)) ?>
    };

    // 2. Función para obtener el estado actual del formulario
    function obtenerEstadoActual() {
        let estado = {
            cabecera: {
                almacen: $("#invalmacen option:selected").text(),
                almacenid: $("#invalmacen").val(),
                sucursal: $("#invsucursal").val(),
                concepto: $("#invconcepto option:selected").text(),
                motivo: $("#invmotivo").val()
            },
            articulos: []
        };

        $("#productosBody tr").each(function() {
            let articulo = {
                id: $(this).find('input[name="esdetidarray[]"]').val() || null, // ESDET_ID
                invdetid: $(this).find('input[name="invdetidarray[]"]').val() || null, // ESDET_STOCKIDSALIDA
                articuloid: $(this).find('input[name="idarticuloarray[]"]').val(), // ESDET_ARTICULOID
                folioarticulo: $(this).find('input[type="text"]').eq(0).val(),
                nombre: $(this).find('input[type="text"]').eq(1).val(),
                lote: $(this).find('input[name="lotearray[]"]').val(),
                caducidad: $(this).find('input[name="caducidadarray[]"]').val(),
                serie: $(this).find('input[name="seriearray[]"]').val(),
                estado: $(this).find('input[name="estadoarray[]"]').val() // original, editado, nuevo, eliminado
            };
            estado.articulos.push(articulo);
        });

        return estado;
    }

    // 3. Función para detectar cambios
    function obtenerCambios(original, modificado) {
        let cambios = {
            cabecera: {},
            articulos: []
        };

        // --- CABECERA ---
        for (let key in modificado.cabecera) {
            // Saltar almacenid ya que se compara por almacen (nombre)
            if (key === 'almacenid') continue;

            if ((modificado.cabecera[key] || "") != (original.cabecera[key] || "")) {
                // Si cambió el almacén, guardar usando el ID no el nombre
                if (key === 'almacen') {
                    cambios.cabecera[key] = {
                        original: original.cabecera['almacenid'],
                        nuevo: modificado.cabecera['almacenid']
                    };
                } else {
                    cambios.cabecera[key] = {
                        original: original.cabecera[key],
                        nuevo: modificado.cabecera[key]
                    };
                }
            }
        }

        // --- ARTÍCULOS ---
        modificado.articulos.forEach(art => {
            if (art.estado === "eliminado") {
                cambios.articulos.push({
                    tipo: "eliminado",
                    id: art.id,
                    invdetid: art.invdetid,
                    folioarticulo: art.folioarticulo,
                    articuloid: art.articuloid,
                    nombre: art.nombre,
                    lote: art.lote,
                    caducidad: art.caducidad,
                    serie: art.serie
                });
                return;
            }

            if (!art.id) {
                // Artículo nuevo
                cambios.articulos.push({
                    tipo: "agregado",
                    nuevo: art
                });
            } else {
                let originalArt = original.articulos.find(o => o.id == art.id);
                if (!originalArt) {
                    cambios.articulos.push({
                        tipo: "agregado",
                        nuevo: art
                    });
                } else {
                    let dif = {};
                    ["nombre", "lote", "caducidad", "serie"].forEach(key => {
                        if ((art[key] || "") != (originalArt[key] || "")) {
                            dif[key] = {
                                original: originalArt[key],
                                nuevo: art[key]
                            };
                        }
                    });
                    if (Object.keys(dif).length > 0 || art.estado === "editado") {
                        cambios.articulos.push({
                            tipo: "modificado",
                            id: art.id,
                            nombre: art.nombre, // <-- agregar el nombre
                            lote: art.lote, // opcional
                            caducidad: art.caducidad, // opcional
                            serie: art.serie, // opcional
                            cambios: dif
                        });
                    }
                }
            }
        });

        return cambios;
    }

    function editar() {
        Swal.fire({
            text: '¿Seguro que deseas modificar la solicitud de entrada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, modificar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#invsucursal").val() == "") {
                    Swal.fire({
                        html: "Sucursal es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invsucursal').focus();
                        }
                    });
                    return;
                }
                if (!$("#invalmacen").val()) {
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invalmacen').focus();
                        }
                    });
                    return;
                }
                if ($("#invconcepto").val() == "") {
                    Swal.fire({
                        html: "Concepto es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invconcepto').focus();
                        }
                    });
                    return;
                }
                if ($("#invmotivo").val() == "") {
                    Swal.fire({
                        html: "Descripción es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#invmotivo').focus();
                        }
                    });
                    return;
                }

                // Validar que haya al menos un artículo agregado
                if ($("#productosBody tr").length === 0) {
                    Swal.fire({
                        html: "Debes agregar al menos un artículo antes de guardar.",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    return;
                }

                // Validación para campos lote, caducidad y serie según seguimiento
                let valid = true;
                let mensajeError = '';

                $("#productosBody tr").each(function(index, tr) {
                    const seguimiento = $(tr).find('input[name="seguimientoarray[]"]').val();
                    const $lote = $(tr).find('input[name="lotearray[]"]');
                    const $caducidad = $(tr).find('input[name="caducidadarray[]"]');
                    const $serie = $(tr).find('input[name="seriearray[]"]');
                    const articulo = $(tr).find('input[type="text"]').first().val();

                    // Solo la caducidad es obligatoria
                    if ($caducidad.val().trim() === '') {
                        mensajeError = `El artículo "${articulo}" requiere que el campo Caducidad esté completo.`;
                        valid = false;
                        $caducidad.focus();
                        return false;
                    }
                });

                if (!valid) {
                    Swal.fire({
                        html: mensajeError,
                        icon: 'warning',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                    return;
                }

                // Validar fechas de caducidad para artículos con seguimiento 'L'
                let articulosCaducidadInvalida = [];

                $("#productosBody tr").each(function(index, tr) {
                    const seguimiento = $(tr).find('input[name="seguimientoarray[]"]').val();
                    const $caducidad = $(tr).find('input[name="caducidadarray[]"]');
                    const articulo = $(tr).find('input[type="text"]').first().val();

                    if (seguimiento === 'L') {
                        const fechaCaducidadStr = $caducidad.val();
                        if (fechaCaducidadStr) {
                            const fechaCaducidad = new Date(fechaCaducidadStr);
                            const hoy = new Date();
                            hoy.setHours(0, 0, 0, 0); // Limpiar horas

                            if (fechaCaducidad < hoy) {
                                articulosCaducidadInvalida.push(articulo);
                            }
                        }
                    }
                });

                if (articulosCaducidadInvalida.length > 0) {
                    Swal.fire({
                        html: `Los siguientes artículos tienen fecha de caducidad menor a hoy y no pueden agregarse:<br><strong>${articulosCaducidadInvalida.join("<br>")}</strong>`,
                        icon: "error",
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                    return;
                }

                // Ahora sí: todo está validado
                var formData = new FormData(document.getElementById("form-editentrada-inventario"));
                formData.append("invsucursalid", $("#invsucursal").val());
                formData.append("invalmacenid", $("#invalmacen").val());
                formData.append("idproveedor", '');
                formData.append("correoproveedor", '');

                // 1. Obtener estado actual
                let actual = obtenerEstadoActual();

                // 2. Detectar cambios
                let cambios = obtenerCambios(originales, actual);

                // 3. Agregar SOLO cambios al formData
                formData.append("cambios", JSON.stringify(cambios));

                $.ajax({
                    url: '../ajax/entradasalida.updatees.php',
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

            } else {
                return false;
            }
        });
    }

    function verificaCaducidadMenorUnAnio() {
        let tieneCaducidadMenor = false;
        $(".banderaCaducidad").each(function() {
            if ($(this).val() === "1") {
                tieneCaducidadMenor = true;
                return false; // rompe el each
            }
        });
        return tieneCaducidadMenor;
    }
</script>