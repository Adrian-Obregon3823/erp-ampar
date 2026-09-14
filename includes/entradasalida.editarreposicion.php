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
                <input type="hidden" name="invtipo" id="invtipo" value="R">
                <input type="hidden" id="esid" name="esid" value="<?= $entradaid ?>">
                <div class="row mb-md-2">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invsucursal">Categoría <span class="text-danger">*</span></label>
                            <select id="invsucursal" name="invsucursal" class="form-control" disabled>
                                <option value="<?= $res[0]['SUCURSAL_ID'] ?>"><?= $res[0]['SUCURSAL_NOMBRE'] ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-md-2">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="invalmacen" name="invalmacen" disabled>
                                <option value="<?= $res[0]['ALMACEN_ID'] ?>"><?= $res[0]['ALMACEN_NOMBRE'] ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-8 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="invconcepto">Concepto <span class="text-danger">*</span></label>
                            <select class="form-control" id="invconcepto" name="invconcepto" disabled>
                                <option value="<?= $res[0]['ES_CONCEPTOID'] ?>"><?= $res[0]['CONCEPTO_NOMBRE'] ?></option>
                            </select>
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
                <div class="row mb-md-2 mb-3 mt-3">
                    <div class="col-12 col-md-9 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="proveedor">Proveedor</label>
                            <input type="hidden" class="form-control" id="idproveedor" name="idproveedor" value="<?= $res[0]['ES_IDPROVEEDOR'] ?>">
                            <input type="text" class="form-control" id="proveedor" name="proveedor" value="<?= $res[0]['PROVEEDOR_NOMBRE'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="correoproveedor">Correo Proveedor</label>
                            <input type="text" readonly class="form-control" id="correoproveedor" name="correoproveedor" value="<?= $res[0]['ES_CORREOPROVEEDOR'] ?>">
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
                                                <?php if ($d['ESDET_STOCKIDSALIDA'] != NULL) { ?>
                                                    <b>&#10132;</b>
                                                <? } ?>
                                                <input type="hidden" name="esdetidarray[]" value="<?= $d['ESDET_ID'] ?>">
                                                <input type="hidden" name="idarticuloarray[]" value="<?= $d['ESDET_ARTICULOID'] ?>">
                                                <input type="hidden" name="seguimientoarray[]" value="<?= $d['SEGUIMIENTO'] ?>">
                                                <input type="hidden" name="estadoarray[]" value="original"> <!-- NUEVO -->
                                                <input type="text" class="form-control" value="<?= $d['CLAVE_ARTICULO'] ?> - <?= $d['ARTICULO_NOMBRE'] ?>" readonly style="min-width: 150px;">
                                                <input type="hidden" class="form-control" name="cantidadarray[]" value="1" readonly>
                                            </div>
                                        </td>
                                        <td><input type="text" class="form-control loteInput" name="lotearray[]" value="<?= $d['ESDET_LOTE'] ?>" <?= ($d['ESDET_STOCKIDSALIDA'] != NULL || $d['SEGUIMIENTO'] == 'S' || $d['SEGUIMIENTO'] == 'N') ? 'readonly' : '' ?> style="min-width: 100px;"></td>
                                        <td>
                                            <input type="date" class="form-control caducidadInput" name="caducidadarray[]" value="<?= $d['ESDET_CADUCIDAD'] ?>" <?= ($d['ESDET_STOCKIDSALIDA'] != NULL || $d['SEGUIMIENTO'] == 'S' || $d['SEGUIMIENTO'] == 'N') ? 'readonly' : '' ?> style="min-width: 130px;">
                                            <input type="hidden" name="caducidadmenor1anio[]" class="banderaCaducidad">
                                        </td>
                                        <td><input type="text" class="form-control serieInput" name="seriearray[]" value="<?= $d['ESDET_SERIE'] ?>" <?= ($d['ESDET_STOCKIDSALIDA'] != NULL || $d['SEGUIMIENTO'] == 'L' || $d['SEGUIMIENTO'] == 'N') ? 'readonly' : '' ?> style="min-width: 100px;"></td>
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
                            "serie" => $d['ESDET_SERIE'],
                            "cantidad" => 1
                        ];
                    }, $res)) ?>
    };

    // 2. Función para obtener el estado actual del formulario
    function obtenerEstadoActual() {
        let estado = {
            cabecera: {
                sucursal: $("#invsucursal option:selected").text(),
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
                cantidad: $(this).find('input[name="cantidadarray[]"]').val(),
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
            if ((modificado.cabecera[key] || "") != (original.cabecera[key] || "")) {
                cambios.cabecera[key] = {
                    original: original.cabecera[key],
                    nuevo: modificado.cabecera[key]
                };
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
                    ["nombre", "lote", "caducidad", "serie", "cantidad"].forEach(key => {
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
            text: '¿Seguro que deseas modificar la solicitud de reposición?',
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
                formData.append("invsucursalid", $("#invsucursal").val());
                formData.append("invalmacenid", $("#invalmacen").val());
                formData.append("invconcepto", $("#invconcepto").val());

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