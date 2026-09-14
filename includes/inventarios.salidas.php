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
            <h4>Salida de Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-salida-inventario">
                <input type="hidden" id="invtipo" name="invtipo" value="S">
                <div class="row">
                    <div class="col-8">
                        <div class="form-group">
                            <label for="invconcepto">Concepto</label>
                            <select class="form-control" id="invconcepto" name="invconcepto"></select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="invalmacen">Sucursal - Almacén</label>
                            <select class="form-control" id="invalmacen" name="invalmacen"></select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="invmotivo">Motivo</label>
                            <textarea class="form-control" id="invmotivo" name="invmotivo" placeholder="Motivo de Salida"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <input type="hidden" id="idarticulo" name="idarticulo">
                            <input type="text" class="form-control" id="articulo" name="articulo" placeholder="Artículo">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <input type="number" class="form-control" id="cantidad" name="cantidad" placeholder="Cantidad" min="1">
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
                            <th>Cantidad</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="productosBody"></tbody>
                </table>


                <button type="button" class="btn btn-primary" onclick="guardar();">Guardar</button>
            </form>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>
<script>
    $(document).ready(function() {

        //Get Conceptos de inventario
        var items1 = "";
        $.getJSON("../ajax/get.inventarios.conceptos.php?naturalezaconcepto=S", function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invconcepto").html(items1);
        });

        //Get Almacenes
        var items2 = "";
        $.getJSON("../ajax/get.sucalmacen.php", function(data) {
            items2 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items2 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#invalmacen").html(items2);
        });

        async function obtenerArticulos() {
            return await $.getJSON("../ajax/get.articulos.catalogo.php");
        }

        obtenerArticulos().then(function(resultarticulos) {
            $("#articulo").autocomplete({
                source: function(request, response) {
                    response($.map(resultarticulos, function(obj) {
                        var label = obj.CVE.toUpperCase() + ' - ' + obj.NOMBRE.toUpperCase();
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
                    $("#articulo").val(ui.item.label);
                    $("#idarticulo").val(ui.item.id);
                    return false;
                }
            });
        });

        $("#agregarProducto").click(function() {
            var idArticulo = $("#idarticulo").val();
            var articulo = $("#articulo").val();
            var cantidad = $("#cantidad").val();

            if (idArticulo === "" || articulo === "" || cantidad === "" || cantidad <= 0) {
                alert("Seleccione un artículo válido y una cantidad mayor a 0.");
                return;
            }

            var nuevaFila = `
            <tr>
                <td>
                    <input type="hidden" name="idarticulo[]" value="${idArticulo}">
                    <input type="text" class="form-control" value="${articulo}" readonly>
                </td>
                <td><input type="number" class="form-control" name="cantidad[]" value="${cantidad}" min="1" readonly></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarFila"><i class="menu-icon mdi mdi-delete-forever"></i></button>
                </td>
            </tr>
        `;

            $("#productosBody").append(nuevaFila);

            // Limpiar los campos después de agregar
            $("#idarticulo").val("");
            $("#articulo").val("");
            $("#cantidad").val("");
        });

        // Eliminar fila
        $(document).on("click", ".eliminarFila", function() {
            $(this).closest("tr").remove();
        });
    });



    function guardar() {

        if ($("#invconcepto").val() == "") {
            alert("Concepto es un campo obligatorio");
            $("#invconcepto").focus();
        } else if ($("#invalmacen").val() == "") {
            alert("Almacén es un campo obligatorio");
            $("#invalmacen").focus();
        } else if ($("#invmotivo").val() == "") {
            alert("Motivo es un campo obligatorio");
            $("#invmotivo").focus();
        } else {
            var formData = new FormData(document.getElementById("form-salida-inventario"));
            $.ajax({
                url: '../ajax/inventarios.registro.php',
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
                        alert("Salida guardada con éxito");
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