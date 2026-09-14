<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<style>
    .ui-autocomplete {
        z-index: 10000 !important;
        /* Asegura que esté sobre el modal (Bootstrap usa 1050) */
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }
</style>
<?php
$maleta = new maletas();
$res = $maleta->getinfotipomaletabyid(base64_decode($_GET['tipomaletaid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Editar Tipo Maleta</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-tipomaleta">
                <input type="hidden" class="form-control" id="tmeditidtipomaleta" name="tmeditidtipomaleta" value="<?= $res[0]['TIPOMALETA_ID'] ?>">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="tmeditfamilia">Familia <span class="text-danger">*</span></label>
                            <input type="hidden" class="form-control" id="tmeditfamiliaid" name="tmeditfamiliaid" value="<?= $res[0]['DIVISION_FAMILIAID'] ?>">
                            <select class="form-control" id="tmeditfamiliaselect" name="tmeditfamiliaselect"></select>
                        </div>
                        <div class="form-group">
                            <label for="tmeditdivision">División <span class="text-danger">*</span></label>
                            <input type="hidden" class="form-control" id="tmeditdivision" name="tmeditdivision" value="<?= $res[0]['DIVISION_ID'] ?>">
                            <select class="form-control" id="tmeditdivisionselect" name="tmeditdivisionselect"></select>
                        </div>
                        <div class="form-group">
                            <label for="tmeditnombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tmeditnombre" name="tmeditnombre" placeholder="Nombre de Tipo de maleta" value="<?= $res[0]['TIPOMALETA_NOMBRE'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="tmeditdescripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="tmeditdescripcion" name="tmeditdescripcion" placeholder="Descripción de Tipo de maleta"><?= $res[0]['TIPOMALETA_DESCRIPCION'] ?></textarea>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group mb-0">
                                    <input type="hidden" id="tmeditidarticulo" name="tmeditidarticulo">
                                    <input type="hidden" id="tmeditcvearticulo" name="tmeditcvearticulo">
                                    <input type="text" class="form-control" id="tmeditarticulo" name="tmeditarticulo" placeholder="Artículo">
                                </div>
                            </div>
                            <div class="col-12 col-md-3 mb-2 mb-md-0">
                                <div class="form-group mb-0">
                                    <input type="number" class="form-control" id="tmeditcantidadsugerida" name="tmeditcantidadsugerida" placeholder="Cantidad" min="1">
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="button" class="btn btn-warning w-100" id="tmeditagregararticulo">Agregar Artículo</button>
                            </div>
                        </div>
                        <div class="table-responsive w-100">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="150px">Clave</th>
                                        <th>Artículo</th>
                                        <th width="100px">Cantidad</th>
                                        <th width="30px">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($res[0]['TIPOMALETADET_ID'] <> '') { ?>
                                        <?php foreach ($res as $re) { ?>
                                            <tr>
                                                <td><?= $re['CLAVE_ARTICULO'] ?></td>
                                                <td><?= $re['ARTICULO_NOMBRE'] ?></td>
                                                <td width="100px">
                                                    <input type="hidden" name="tmeditiddetallearrayexistente[]" value="<?= $re['TIPOMALETADET_ID'] ?>">
                                                    <input type="number" class="form-control" name="tmeditcantidadarrayexistente[]" value="<?= $re['TIPOMALETADET_CANTIDADSUGERIDA']; ?>" min="1" required>
                                                </td>
                                                <td width="30px">
                                                    <img src="../img/eliminar.png" style="cursor: pointer; width:20px !important; height: 20px !important;" onclick="eliminartm('<?= $re['TIPOMALETADET_ID'] ?>')">
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <table class="table">
                                <tbody id="tmeditarticulosBody"></tbody>
                            </table>
                        </div>
                        <br>
                        <button type="button" class="btn btn-primary" onclick="editartm();">Modificar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    async function tmeditobtenerArticulos() {
        return await $.getJSON("../ajax/get.articulos.catalogo.php");
    }

    // Cargar familias y preseleccionar la actual
    var familiaActual = "<?= $res[0]['DIVISION_FAMILIAID'] ?>";
    $.getJSON("../ajax/get.familias.php", function(data) {
        var opts = "<option value=''></option>";
        $.each(data, function(i, item) {
            var selected = (String(item.ID) === String(familiaActual)) ? ' selected' : '';
            opts += "<option value='" + item.ID + "'" + selected + ">" + item.NOMBRE + "</option>";
        });
        $("#tmeditfamiliaselect").html(opts);
        $("#tmeditfamiliaselect").change(function() {
            var selectedFamilia = $(this).val();
            $("#tmeditfamiliaid").val(selectedFamilia);
            // Cargar divisiones de la nueva familia
            cargaDivisionesEdit(selectedFamilia, "");
        });
        $("#tmeditfamiliaid").val($("#tmeditfamiliaselect").val());
    });

    // Cargar divisiones
    var divisionActual = "<?= $res[0]['DIVISION_ID'] ?>";
    cargaDivisionesEdit(familiaActual, divisionActual);

    function cargaDivisionesEdit(familiaid, selDivisionId) {
        if (!familiaid || familiaid === "") {
            $("#tmeditdivisionselect").html("<option value=''></option>").prop("disabled", true);
            $("#tmeditdivision").val("");
            return;
        }
        $.getJSON("../ajax/get.divisiones.php?familiaid=" + familiaid, function(data) {
            var opts = "<option value=''></option>";
            if (data && data[0] && data[0].ID !== "") {
                $.each(data, function(i, item) {
                    var selected = (String(item.ID) === String(selDivisionId)) ? ' selected' : '';
                    opts += "<option value='" + item.ID + "'" + selected + ">" + item.NOMBRE + "</option>";
                });
            }
            $("#tmeditdivisionselect").html(opts).prop("disabled", false);
            $("#tmeditdivisionselect").off("change").change(function() {
                $("#tmeditdivision").val($(this).val());
            });
            $("#tmeditdivision").val($("#tmeditdivisionselect").val());
        });
    }

    tmeditobtenerArticulos().then(function(resultarticulos) {
        $("#tmeditarticulo").autocomplete({
            source: function(request, response) {
                response($.map(resultarticulos, function(obj) {
                    var label = obj.CLAVE_ARTICULO + ' - ' + obj.NOMBRE.toUpperCase();
                    if (label.includes(request.term.toUpperCase())) {
                        return {
                            label: label,
                            id: obj.ID,
                            nombre: obj.NOMBRE,
                            cve: obj.CLAVE_ARTICULO
                        };
                    }
                    return null;
                }).filter(Boolean));
            },
            minLength: 1,
            select: function(event, ui) {
                $("#tmeditcvearticulo").val(ui.item.cve);
                $("#tmeditarticulo").val(ui.item.nombre);
                $("#tmeditidarticulo").val(ui.item.id);
                return false;
            }
        });
    });

    $("#tmeditagregararticulo").click(function() {
        var idArticulo = $("#tmeditidarticulo").val();
        var cvearticulo = $("#tmeditcvearticulo").val();
        var articulo = $("#tmeditarticulo").val();
        var cantidad = $("#tmeditcantidadsugerida").val();

        if (idArticulo === "" || articulo === "" || cantidad === "" || cantidad <= 0) {
            alert("Seleccione un artículo válido y una cantidad sugerida mayor a 0.");
            return;
        }

        var nuevaFila = `
            <tr>
                <td width="150px">
                    <input type="hidden" name="tmeditidarticuloarray[]" value="${idArticulo}">
                    <input type="text" class="form-control" name="tmeditcvearticuloarray[]" value="${cvearticulo}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" name="tmeditarticuloarray[]" value="${articulo}" readonly>
                </td>
                <td width="100px"><input type="number" class="form-control" name="tmeditcantidadarray[]" value="${cantidad}" min="1" readonly></td>
                <td width="30px">
                    <img src="../img/eliminar.png" alt="Eliminar" class="tmediteliminarFila" style="cursor: pointer; width:20px !important; height: 20px !important;" title="Eliminar">
                </td>
            </tr>
        `;

        $("#tmeditarticulosBody").append(nuevaFila);

        // Limpiar los campos después de agregar
        $("#tmeditidarticulo").val("");
        $("#tmeditarticulo").val("");
        $("#tmeditcvearticulo").val("");
        $("#tmeditcantidadsugerida").val("");
    });

    // Eliminar fila
    $(document).on("click", ".tmediteliminarFila", function() {
        $(this).closest("tr").remove();
    });

    function editartm() {
        if (confirm('Seguro que deseas modificar el tipo de maleta?')) {
            if ($("#tmeditidtipomaleta").val() == "") {
                alert("ID es un campo obligatorio");
                $("#tmeditidtipomaleta").focus();
            } else if ($("#tmeditfamiliaid").val() == "") {
                alert("Familia es un campo obligatorio");
                $("#tmeditfamiliaselect").focus();
            } else if ($("#tmeditdivision").val() == "") {
                alert("División es un campo obligatorio");
                $("#tmeditdivisionselect").focus();
            } else if ($("#tmeditnombre").val() == "") {
                alert("Nombre es un campo obligatorio");
                $("#tmeditnombre").focus();
            } else if ($("#tmeditdescripcion").val() == "") {
                alert("Descripción es un campo obligatorio");
                $("#tmeditdescripcion").focus();
            } else {
                var formData = new FormData(document.getElementById("form-editar-tipomaleta"));
                $.ajax({
                    url: '../ajax/tipomaletas.editar.php',
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
                            alert("Tipo Maleta modificado con éxito");
                            var modal = $('#modalglobal');
                            var url = "../includes/tipomaletas.editar.php?tipomaletaid=<?= $_GET['tipomaletaid'] ?>";
                            $.ajax({
                                url: url,
                                type: 'GET',
                                success: function(newContent) {
                                    modal.find('.modal-body').html(newContent);
                                },
                                error: function() {
                                    modal.find('.modal-body').html('<p>Error al recargar el contenido.</p>');
                                }
                            });
                        } else {
                            alert(response);
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
    }

    function eliminartm(tipomaletadetid) {
        if (confirm("Seguro que deseas eliminar detalle?")) {
            // 🔴 1. Guarda temporalmente el HTML de los artículos nuevos
            const articulosNuevosHTML = $("#tmeditarticulosBody").html();

            $.ajax({
                url: '../ajax/tipomaletas.eliminardet.php',
                type: 'POST',
                data: {
                    tipomaletadetid: tipomaletadetid
                },
                dataType: 'html',
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(response) {
                    if (response.trim() === "") {
                        alert("Detalle en tipo de Maleta eliminado con éxito");

                        // 🔵 2. Recarga el modal
                        var modal = $('#modalglobal');
                        var url = "../includes/tipomaletas.editar.php?tipomaletaid=<?= $_GET['tipomaletaid'] ?>";
                        $.ajax({
                            url: url,
                            type: 'GET',
                            success: function(newContent) {
                                modal.find('.modal-body').html(newContent);

                                // 🟢 3. Vuelve a insertar los artículos nuevos
                                setTimeout(() => {
                                    $("#tmeditarticulosBody").html(articulosNuevosHTML);
                                }, 100);
                            },
                            error: function() {
                                modal.find('.modal-body').html('<p>Error al recargar el contenido.</p>');
                            }
                        });
                    } else {
                        alert(response);
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
    }
</script>