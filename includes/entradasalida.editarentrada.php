<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$entradaid = isset($_GET['esid']) ? base64_decode($_GET['esid']) : '';
$entradasalida = new entradasalida();
$res = $entradasalida->getinfoentradasalidabyid($entradaid);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Edición de Entrada a Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editentrada-inventario">
                <input type="hidden" name="cabeceraEditada" id="cabeceraEditada" value="0">
                <input type="hidden" name="invtipo" id="invtipo" value="E">
                <input type="hidden" id="esid" name="esid" value="<?= $entradaid ?>">
                <div class="row mb-md-2">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="invalmacen" name="invalmacen">
                                <option value="<?= $res[0]['ALMACEN_ID'] ?>"><?= $res[0]['ALMACEN_NOMBRE'] ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invsucursal">Categoría</label>
                            <input type="hidden" id="invsucursalid_real" value="<?= $res[0]['SUCURSAL_ID'] ?? '' ?>">
                            <input type="text" id="invsucursal" name="invsucursal" class="form-control" value="<?= $res[0]['SUCURSAL_NOMBRE'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invtipoalmacen">Tipo de Almacén</label>
                            <input type="text" id="invtipoalmacen" name="invtipoalmacen" class="form-control" value="<?= $res[0]['TIPOALMACEN_NOMBRE'] ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="row mb-md-2">
                    <div class="col-12 col-md-8 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invconcepto">Concepto <span class="text-danger">*</span></label>
                            <select class="form-control" id="invconcepto" name="invconcepto"></select>
                        </div>
                    </div>
                </div>
                <div class="row mb-md-2">
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label for="invmotivo">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo" maxlength="250"><?= $res[0]['ES_MOTIVO'] ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row mb-md-2">
                    <div class="col-12 col-md-9 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="proveedor">Proveedor</label>
                            <input type="hidden" class="form-control" id="idproveedor" name="idproveedor">
                            <input type="text" class="form-control" id="proveedor" name="proveedor">
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="correoproveedor">Correo Proveedor</label>
                            <input type="text" readonly class="form-control" id="correoproveedor" name="correoproveedor">
                        </div>
                    </div>
                </div>
                <div class="border rounded p-3 mb-3 mt-3" style="background-color: #f8fbff; border-color: #e0e6ed !important;">
                    <div class="row align-items-center mb-2">
                        <div class="col-12 col-md-8 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Artículo <span class="text-danger">*</span></label>
                                <input type="hidden" id="idarticulo" name="idarticulo">
                                <input type="hidden" id="seguimiento" name="seguimiento">
                                <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Buscar artículo...">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" placeholder="Cant." min="1">
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end mt-2">
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Lote <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="text" class="form-control" id="lote_global" placeholder="Se aplicará a todas">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Caducidad <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="date" class="form-control" id="caducidad_global">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <div class="form-group mb-0">
                                <label class="text-muted small font-weight-bold">Serie <span class="font-weight-normal">(Opcional)</span></label>
                                <input type="text" class="form-control" id="serie_global" placeholder="Se aplicará a todas">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <button type="button" class="btn btn-warning px-5" id="agregarProducto">
                                Agregar Producto
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive w-100">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
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
                                            <input type="hidden" name="esdetidarray[]" value="<?= $d['ESDET_ID'] ?>">
                                            <input type="hidden" name="idarticuloarray[]" value="<?= $d['ESDET_ARTICULOID'] ?>">
                                            <input type="hidden" name="seguimientoarray[]" value="<?= $d['SEGUIMIENTO'] ?>">
                                            <input type="hidden" name="estadoarray[]" value="original"> <!-- NUEVO -->
                                            <input type="text" class="form-control" value="<?= $d['CLAVE_ARTICULO'] ?> - <?= $d['ARTICULO_NOMBRE'] ?>" readonly style="min-width: 150px;">
                                            <input type="hidden" class="form-control" name="cantidadarray[]" value="1" readonly>
                                        </td>
                                        <td><input type="text" class="form-control loteInput" name="lotearray[]" value="<?= $d['ESDET_LOTE'] ?>" style="min-width: 100px;"></td>
                                        <td>
                                            <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="<?= $d['ESDET_CADUCIDAD'] ?>" style="min-width: 130px;">
                                            <input type="hidden" name="caducidadmenor1anio[]" class="banderaCaducidad">
                                        </td>
                                        <td><input type="text" class="form-control serieInput" name="seriearray[]" value="<?= $d['ESDET_SERIE'] ?>" style="min-width: 100px;"></td>
                                        <td>
                                            <img src="../img/eliminar.png" alt="Eliminar" class="eliminarFila" style="cursor:pointer;width:20px;height:20px;" title="Eliminar">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <button type="button" class="btn btn-success" onclick="editar();">Modificar</button>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        //Get Conceptos de inventario
        var items1 = "";
        $.getJSON("../ajax/get.inventarios.conceptos.php?naturalezaconcepto=E", function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
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

        // Evento cuando cambia el almacén
        $("#invalmacen").change(function() {
            const almacenId = $("#invalmacen").val();
            $("#invsucursal").val("");
            $("#invsucursalid_real").val(""); // <-- IMPORTANTE: limpiar el ID oculto
            $("#invtipoalmacen").val("");

            if (!almacenId) {
                return;
            }

            $.getJSON("../ajax/get.almaceninfo.php?almacenid=" + almacenId, function(data) {
                if (data.SUCURSAL_NOMBRE) {
                    $("#invsucursal").val(data.SUCURSAL_NOMBRE);
                    // OJO: Si tu JSON devuelve el ID de la sucursal, asígnalo aquí para que no marque error al guardar.
                    // Si tu ajax lo devuelve como SUCURSAL_ID, sería así:
                    if (data.SUCURSAL_ID) {
                        $("#invsucursalid_real").val(data.SUCURSAL_ID);
                    }
                }
                if (data.TIPOALMACEN_NOMBRE) {
                    $("#invtipoalmacen").val(data.TIPOALMACEN_NOMBRE);
                }
            });
        });

        //Get Proveedores
        async function obtenerproveedores() {
            return await $.getJSON("../ajax/get.proveedores.php");
        }

        obtenerproveedores().then(function(resultprov) {
            let seleccionado = false;

            $("#proveedor").autocomplete({
                source: function(request, response) {
                    response($.map(resultprov, function(obj) {
                        var label = obj.NOMBRE.toUpperCase();
                        if (label.includes(request.term.toUpperCase())) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                correo: obj.EMAIL
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#proveedor").val(ui.item.label);
                    $("#idproveedor").val(ui.item.id);
                    $("#correoproveedor").val(ui.item.correo);
                    seleccionado = true;
                    return false;
                }
            });

            // Setear proveedor traído de PHP después de inicializar autocomplete
            $("#proveedor").val("<?= $res[0]['PROVEEDOR_NOMBRE'] ?>");
            $("#idproveedor").val("<?= $res[0]['ES_IDPROVEEDOR'] ?>");
            $("#correoproveedor").val("<?= $res[0]['ES_CORREOPROVEEDOR'] ?>");

            // Si el usuario escribe, desactiva la selección
            $("#proveedor").on("input", function() {
                seleccionado = false;
            });

            // Al salir del campo, valida si se seleccionó algo; si no, limpia los campos
            $("#proveedor").on("blur", function() {
                if (!seleccionado) {
                    $("#proveedor").val('');
                    $("#idproveedor").val('');
                    $("#correoproveedor").val('');
                }
            });
        });

        async function obtenerArticulos() {
            return await $.getJSON("../ajax/get.articulos.catalogo.php");
        }

        obtenerArticulos().then(function(resultarticulos) {
            $("#articulo").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        var label = obj.CLAVE_ARTICULO + ' - ' + obj.NOMBRE.toUpperCase();
                        if (label.includes(request.term.toUpperCase())) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                seguimiento: obj.SEGUIMIENTO,
                                cve: obj.CLAVE_ARTICULO
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#articulo").val(ui.item.label);
                    $("#idarticulo").val(ui.item.id);
                    $("#seguimiento").val(ui.item.seguimiento);
                    return false;
                }
            });
        });

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var seguimiento = $("#seguimiento").val();
            var cantidad = parseInt($("#cantidad").val());

            var loteGlobal = $("#lote_global").val();
            var caducidadGlobal = $("#caducidad_global").val();
            var serieGlobal = $("#serie_global").val();

            if (idArticulo === "" || articulo === "" || isNaN(cantidad) || cantidad <= 0) {
                Swal.fire({
                    html: "Seleccione un artículo válido y una cantidad mayor a 0.",
                    icon: "warning",
                    customClass: {
                        confirmButton: 'btn btn-success' // usa clases de Bootstrap
                    }
                });
                return;
            }

            for (let i = 0; i < cantidad; i++) {
                let readonlyLote = '';
                let readonlyCaducidad = '';
                let readonlySerie = '';

                var nuevaFila = `
                    <tr>
                        <td>
                            <input type="hidden" name="esdetidarray[]" value=""> <!-- vacío -->
                            <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                            <input type="hidden" name="seguimientoarray[]" value="${seguimiento}">
                            <input type="hidden" name="estadoarray[]" value="nuevo">
                            <input type="text" class="form-control" value="${articulo}" readonly>
                            <input type="hidden" class="form-control" name="cantidadarray[]" value="1" min="1" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control loteInput" name="lotearray[]" placeholder="Lote" value="${loteGlobal}" ${readonlyLote}>
                        </td>
                        <td>
                            <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="${caducidadGlobal}" ${readonlyCaducidad}>
                            <input type="hidden" name="caducidadmenor1anio[]" class="banderaCaducidad">
                        </td>
                        <td>
                            <input type="text" class="form-control serieInput" name="seriearray[]" placeholder="Serie" value="${serieGlobal}" ${readonlySerie}>
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
            $("#cantidad").val("");
            $("#lote_global").val("");
            $("#caducidad_global").val("");
            $("#serie_global").val("");
        });

        $(document).on("change", ".caducidadInput", function() {
            const hoy = new Date();
            const unAnioDespues = new Date();
            unAnioDespues.setFullYear(hoy.getFullYear() + 1);

            const fechaCaducidad = new Date($(this).val());
            const banderaInput = $(this).siblings(".banderaCaducidad");

            if (fechaCaducidad < unAnioDespues) {
                banderaInput.val("1"); // menor a un año
            } else {
                banderaInput.val("0"); // mayor o igual a un año
            }
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

        $("#invconcepto, #invmotivo, #proveedor, #correoproveedor").on("change input", function() {
            $("#cabeceraEditada").val("1");
        });

    });

    // 1. Guardamos snapshot de originales al cargar
    var originales = {
        cabecera: {
            almacen: "<?= $res[0]['ALMACEN_NOMBRE'] ?>",
            almacenid: "<?= $res[0]['ALMACEN_ID'] ?>",
            sucursal: "<?= $res[0]['SUCURSAL_NOMBRE'] ?>",
            concepto: "<?= $res[0]['CONCEPTO_NOMBRE'] ?>",
            motivo: "<?= $res[0]['ES_MOTIVO'] ?>",
            proveedor: "<?= $res[0]['PROVEEDOR_NOMBRE'] ?>",
            idproveedor: "<?= $res[0]['ES_IDPROVEEDOR'] ?>",
            correoproveedor: "<?= $res[0]['ES_CORREOPROVEEDOR'] ?>"
        },
        articulos: <?= json_encode(array_map(function ($d) {
                        return [
                            "id" => $d['ESDET_ID'],   // ID único de detalle
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
                sucursal: $("#invsucursal").val(), // <-- CORREGIDO: era .text()
                concepto: $("#invconcepto option:selected").text(),
                motivo: $("#invmotivo").val(),
                proveedor: $("#proveedor").val(),
                idproveedor: $("#idproveedor").val(),
                correoproveedor: $("#correoproveedor").val()
            },
            articulos: []
        };

        $("#productosBody tr").each(function() {
            let articulo = {
                id: $(this).find('input[name="esdetidarray[]"]').val() || null, // ESDET_ID
                articuloid: $(this).find('input[name="idarticuloarray[]"]').val(), // ESDET_ARTICULOID
                nombre: $(this).find('input[type="text"]').first().val(),
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
            if (key === 'almacenid') continue; // Se maneja con 'almacen'

            if ((modificado.cabecera[key] || "") != (original.cabecera[key] || "")) {
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
                    // Removido 'cantidad' del chequeo porque no es columna actualizable en el detalle de la base de datos
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
                            nombre: art.nombre,
                            lote: art.lote,
                            caducidad: art.caducidad,
                            serie: art.serie,
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
                if ($("#proveedor").val() == "") {
                    Swal.fire({
                        html: "Proveedor es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#proveedor').focus();
                        }
                    });
                    return;
                }
                if ($("#correoproveedor").val() == "") {
                    Swal.fire({
                        html: "Correo Proveedor es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#correoproveedor').focus();
                        }
                    });
                    return;
                }
                // Validar formato de correo electrónico
                let correoaux = $("#correoproveedor").val();
                let regexCorreoaux = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!regexCorreoaux.test(correoaux)) {
                    Swal.fire({
                        html: "Ingrese un correo electrónico válido",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#correoproveedor').focus();
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

                // Verifica si hay productos con caducidad menor a un año
                let requiereProveedor = false;
                $(".banderaCaducidad").each(function() {
                    if ($(this).val() === "1") {
                        requiereProveedor = true;
                        return false; // rompe el each
                    }
                });

                const proveedor = $("#proveedor").val().trim();
                const idProveedor = $("#idproveedor").val().trim();
                const correo = $("#correoproveedor").val().trim();
                const correoRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                // Si se requiere proveedor y no está bien capturado
                if (requiereProveedor) {
                    if (!proveedor || !idProveedor || !correo || !correoRegex.test(correo)) {
                        Swal.fire({
                            html: "Hay productos con caducidad menor a un año. Debes seleccionar un proveedor válido con correo.",
                            icon: "warning",
                            customClass: {
                                confirmButton: 'btn btn-success'
                            },
                            didClose: () => {
                                $('#proveedor').focus();
                            }
                        });
                        return;
                    }
                } else {
                    // Si no se requiere proveedor pero hay uno capturado, validar su correo si lo hay
                    if (proveedor !== "" && idProveedor !== "") {
                        if (correo === "" || !correoRegex.test(correo)) {
                            Swal.fire({
                                html: "El proveedor seleccionado no tiene un correo válido.",
                                icon: "warning",
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                didClose: () => {
                                    $('#correoproveedor').focus();
                                }
                            });
                            return;
                        }
                    }
                }

                // Ahora sí: todo está validado
                var formData = new FormData(document.getElementById("form-editentrada-inventario"));
                formData.append("invsucursalid", $("#invsucursalid_real").val());
                formData.append("invalmacenid", $("#invalmacen").val());

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