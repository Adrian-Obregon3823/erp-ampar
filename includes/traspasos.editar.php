<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$traspasoid = isset($_GET['traspasoid']) ? base64_decode($_GET['traspasoid']) : '';
$traspasos = new traspasos();
$res = $traspasos->getinfotraspasobyid($traspasoid);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Edición de Traspaso</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editentrada-inventario">
                <input type="hidden" name="cabeceraEditada" id="cabeceraEditada" value="0">
                <input type="hidden" id="traspasoid" name="traspasoid" value="<?= $traspasoid ?>">
                <div class="row align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="form-group">
                            <label for="deinvalmacen">De Almacén <span class="text-danger">*</span></label>
                            <input type="hidden" id="desucursalid" name="desucursalid" value="<?= $res[0]['DESUCURSALID'] ?>">
                            <input type="hidden" id="deinvalmacenid" name="deinvalmacenid" value="<?= $res[0]['TRASPASO_DEALMACENID'] ?>">
                            <input type="text" class="form-control" id="deinvalmacen" name="deinvalmacen" value="<?= $res[0]['DEALMACEN'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 text-center py-2 py-md-0">
                        <div class="d-none d-md-block">↔</div>
                        <div class="d-block d-md-none">↕</div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="form-group">
                            <label for="ainvalmacen">A Almacén <span class="text-danger">*</span></label>
                            <input type="hidden" id="asucursalid" name="asucursalid" value="<?= $res[0]['ASUCURSALID'] ?>">
                            <input type="hidden" id="ainvalmacenid" name="ainvalmacenid" value="<?= $res[0]['TRASPASO_AALMACENID'] ?>">
                            <input type="text" class="form-control" id="ainvalmacen" name="ainvalmacen" value="<?= $res[0]['AALMACEN'] ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invmotivo">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo" maxlength="250"><?= $res[0]['TRASPASO_MOTIVO'] ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row align-items-end">
                    <div class="col-12 col-md-9 mb-2 mb-md-0">
                        <div class="form-group mb-md-0">
                            <input type="hidden" id="idarticulo" name="idarticulo">
                            <input type="hidden" id="cvearticulo" name="cvearticulo">
                            <input type="hidden" id="stockid" name="stockid">
                            <input type="hidden" id="folioarticulo" name="folioarticulo">
                            <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Artículo">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="form-group mb-md-0 mt-2 mt-md-0">
                            <button type="button" class="btn btn-warning w-100" id="agregarProducto">Agregar Producto</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Cve</th>
                                <th>Artículo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productosBody">
                            <?php if ($res[0]['TRASPASODET_ID'] <> "") { ?>
                                <?php foreach ($res as $d): ?>
                                    <tr data-traspasodet="<?= $d['TRASPASODET_ID'] ?>" class="filaOriginal">
                                        <td>
                                            <input type="hidden" name="traspasoidarray[]" value="<?= $d['TRASPASODET_ID'] ?>">
                                            <input type="hidden" name="stockidarray[]" value="<?= $d['TRASPASODET_STOCKID'] ?>">
                                            <input type="hidden" name="estadoarray[]" value="original"> <!-- NUEVO -->
                                            <input type="text" class="form-control" value="<?= $d['STOCK_FOLIO'] ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" value="<?= $d['CLAVE_ARTICULO'] ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" value="<?= $d['ARTICULO_NOMBRE'] ?>" readonly>
                                        </td>
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

        async function obtenerArticulos(almacenid) {
            return await $.getJSON("../ajax/get.articulosfiltrados.catalogo.php?almacenid=" + almacenid + "&incluirMaletas=1");
        }

        // Filter autocomplete based on project allowed articles
        window.isProjectDest = false;
        window.allowedDestArticles = [];
        
        let destAlmacenId = $('#ainvalmacenid').val();
        if (destAlmacenId) {
            $.getJSON("../ajax/get.articulos_permitidos_proyecto.php?almacenid=" + destAlmacenId, function(data) {
                if (data && data.is_project) {
                    window.isProjectDest = true;
                    window.allowedDestArticles = data.allowed ? data.allowed.map(String) : [];
                }
            });
        }

        obtenerArticulos('<?= $res[0]['TRASPASO_DEALMACENID'] ?>').then(function(resultarticulos) {
            $("#articulo").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        // Si hay artículos permitidos (porque es un almacén de proyecto), validamos
                        if (window.isProjectDest) {
                            let allowed = window.allowedDestArticles || [];
                            if (!allowed.includes(String(obj.ID)) && !allowed.includes(String(-obj.ID))) {
                                return null;
                            }
                        }

                        var label = obj.STOCK_FOLIO + ' - ' + obj.NOMBRE.toUpperCase() + ' (' + obj.CLAVE_ARTICULO + ')';
                        if (label.includes(request.term.toUpperCase())) {
                            return {
                                label: label,
                                id: obj.ID,
                                nombre: obj.NOMBRE,
                                cve: obj.CLAVE_ARTICULO,
                                stockid: obj.INVDETID,
                                folioarticulo: obj.STOCK_FOLIO,
                            };
                        }
                        return null;
                    }).filter(Boolean));
                },
                minLength: 1,
                select: function(event, ui) {
                    $("#articulo").val(ui.item.nombre);
                    $("#idarticulo").val(ui.item.id);
                    $("#cvearticulo").val(ui.item.cve);
                    $("#stockid").val(ui.item.stockid);
                    $("#folioarticulo").val(ui.item.folioarticulo);
                    return false;
                }
            });
        });

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var cveArticulo = $("#cvearticulo").val();
            var articulo = $("#articulo").val();
            var stockid = $("#stockid").val();
            var folioarticulo = $("#folioarticulo").val();

            if (idArticulo === "" || articulo === "" || stockid === "") {
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
            $("input[name='stockidarray[]']").each(function() {
                if ($(this).val() === stockid) {
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

                var nuevaFila = `
                    <tr>
                        <td>
                            <input type="hidden" name="traspasoidarray[]" value=""> <!-- vacío -->
                            <input type="hidden" name="stockidarray[]" value="${stockid}">
                            <input type="hidden" name="idarticuloarray[]" value="${idArticulo}">
                            <input type="hidden" name="estadoarray[]" value="nuevo">
                            <input type="text" class="form-control" value="${folioarticulo}" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control" value="${cveArticulo}" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control" value="${articulo}" readonly>
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
            $("#cvearticulo").val("");
            $("#articulo").val("");
            $("#stockid").val("");
            $("#folioarticulo").val("");

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

        $("#invmotivo").on("change input", function() {
            $("#cabeceraEditada").val("1");
        });

    });

    // 1. Guardamos snapshot de originales al cargar
    var originales = {
        cabecera: {
            motivo: "<?= $res[0]['TRASPASO_MOTIVO'] ?>"
        },
        articulos: <?= json_encode(array_map(function ($d) {
                        return [
                            "id" => $d['TRASPASODET_ID'],   // ID único de detalle
                            "stockid" => $d['TRASPASODET_STOCKID'],
                            "folioarticulo" => $d['STOCK_FOLIO'],
                            "nombre" => $d['ARTICULO_NOMBRE']
                        ];
                    }, $res)) ?>
    };

    // 2. Función para obtener el estado actual del formulario
    function obtenerEstadoActual() {
        let estado = {
            cabecera: {
                motivo: $("#invmotivo").val()
            },
            articulos: []
        };

        $("#productosBody tr").each(function() {
            let articulo = {
                id: $(this).find('input[name="traspasoidarray[]"]').val() || null, // TRASPASODET_ID
                stockid: $(this).find('input[name="stockidarray[]"]').val() || null, // TRASPASODET_STOCKID
                articuloid: $(this).find('input[name="idarticuloarray[]"]').val(), // STOCK_ARTICULOID
                folioarticulo: $(this).find('input[type="text"]').eq(0).val(),
                nombre: $(this).find('input[type="text"]').eq(2).val(),
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
                    stockid: art.stockid,
                    folioarticulo: art.folioarticulo,
                    articuloid: art.articuloid,
                    nombre: art.nombre
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
                    ["nombre"].forEach(key => {
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
            text: '¿Seguro que deseas modificar la solicitud de traspaso?',
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
                if (!$("#deinvalmacen").val()) {
                    Swal.fire({
                        html: "De Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#deinvalmacen').focus();
                        }
                    });
                    return;
                }
                if (!$("#ainvalmacen").val()) {
                    Swal.fire({
                        html: "A Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#ainvalmacen').focus();
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

                // Ahora sí: todo está validado
                var formData = new FormData(document.getElementById("form-editentrada-inventario"));

                // 1. Obtener estado actual
                let actual = obtenerEstadoActual();

                // 2. Detectar cambios
                let cambios = obtenerCambios(originales, actual);

                // 3. Agregar SOLO cambios al formData
                formData.append("cambios", JSON.stringify(cambios));

                $.ajax({
                    url: '../ajax/traspasos.update.php',
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
</script>