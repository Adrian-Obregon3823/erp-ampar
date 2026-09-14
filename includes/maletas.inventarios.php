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
<?php include_once("../includes/head.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Traspasos Maletas</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-intercambio-inventario">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invnaturaleza">Naturaleza</label>
                            <select class="form-control" id="invnaturaleza" name="invnaturaleza">
                                <option value="1">Sucursal - Almacén a Maleta</option>
                                <option value="2">Maleta a Sucursal - Almacén</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="invalmacen">Sucursal - Almacén</label>
                            <select class="form-control" id="invalmacen" name="invalmacen"></select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="invmaleta">Maleta</label>
                            <select class="form-control" id="invmaleta" name="invmaleta"></select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invmotivo">Motivo</label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <input type="hidden" id="idarticulofiltro" name="idarticulofiltro">
                            <input type="text" class="form-control" id="articulofiltro" name="articulofiltro" placeholder="Artículo">
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
                            <th>Artículo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="productosBody"></tbody>
                </table>


                <button type="button" class="btn btn-primary" onclick="guardar();">Transferir</button>
            </form>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>
<script>
    $(document).ready(function() {

        //Get Almacenes
        var items2 = "";
        $.getJSON("../ajax/get.sucalmacen.php", function(data) {
            items2 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items2 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(items2);
        });

        //Get Maletas
        $("#invalmacen").change(function() {
            var items3 = "";
            $.getJSON("../ajax/get.maletas.catalogo.php?sucalmacenid=" + $("#invalmacen").val(), function(data) {
                let maletasValidas = data.filter(m => m.ID && m.NOMBRE);
                console.log(data);
                if (maletasValidas.length > 0) {
                    items3 += "<option value=''></option>";
                    $.each(data, function(index, item) {
                        items3 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                    });
                    $("#invmaleta").html(items3);
                } else {
                    $("#invmaleta").html("<option value='0'></option>");
                }
            });
        });

        async function obtenerArticulos(maletaidvar) {
            return await $.getJSON("../ajax/get.articulosfiltrados.catalogo.php?maletaid=" + maletaidvar);
        }

        $("#invmaleta").change(function() {
            obtenerArticulos($("#invmaleta").val()).then(function(resultarticulos) {
                $("#articulofiltro").autocomplete({
                    source: function(request, response) {
                        response($.map(resultarticulos, function(obj) {
                            var label = obj.ID + ' - ' + obj.NOMBRE.toUpperCase();
                            if (label.includes(request.term.toUpperCase())) {
                                return {
                                    label: label,
                                    id: obj.ID,
                                    nombre: obj.NOMBRE
                                };
                            }
                            return null;
                        }).filter(Boolean));
                    },
                    minLength: 1,
                    select: function(event, ui) {
                        $("#articulofiltro").val(ui.item.label);
                        $("#idarticulofiltro").val(ui.item.id);
                        return false;
                    }
                });
            });
        });


        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulofiltro").val();
            var articulo = $("#articulofiltro").val();

            if (idArticulo === "" || articulo === "") {
                alert("Seleccione un artículo válido");
                return;
            }

            // Validar si ya existe un artículo con ese ID
            var existe = false;
            $("input[name='idarticulofiltro[]']").each(function() {
                if ($(this).val() === idArticulo) {
                    existe = true;
                    return false; // salir del loop
                }
            });

            if (existe) {
                alert("Este artículo ya fue agregado.");
                return;
            }

            var nuevaFila = `
                <tr>
                    <td>
                        <input type="hidden" name="idarticulofiltro[]" value="${idArticulo}">
                        <input type="text" class="form-control" value="${articulo}" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm eliminarFila">
                            <i class="menu-icon mdi mdi-delete-forever"></i>
                        </button>
                    </td>
                </tr>
            `;

            $("#productosBody").append(nuevaFila);

            // Limpiar los campos después de agregar
            $("#idarticulofiltro").val("");
            $("#articulofiltro").val("");
        });


        // Eliminar fila
        $(document).on("click", ".eliminarFila", function() {
            $(this).closest("tr").remove();
        });
    });

    function guardar() {

        if ($("#invalmacen").val() == "") {
            alert("Sucursal -Almacén es un campo obligatorio");
            $("#invalmacen").focus();
        } else if ($("#invmotivo").val() == "") {
            alert("Motivo es un campo obligatorio");
            $("#invmotivo").focus();
        } else {
            var formData = new FormData(document.getElementById("form-intercambio-inventario"));
            $.ajax({
                url: '../ajax/inventarios.registrointercambio.php',
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
                        alert("Intercambio guardado con éxito");
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
    }
</script>