<?php include_once("../includes/includes.php"); ?>
<?php include_once("../includes/head.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Revisión de Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nueva-maleta">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="masucalmacen">Sucursal</label>
                            <select class="form-control" id="masucalmacen" name="masucalmacen"></select>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="generarrevision();">Revisión</button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div id="tabla-resultados"></div>
                        <button type="button" class="btn btn-success mt-3" onclick="procesarSeleccionados()">Procesar Seleccionados</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>
<script>
    $(document).ready(function() {
        // Llenar combo de sucursales
        $.getJSON("../ajax/get.sucursales.php", function(data) {
            let items = "<option value='0'></option>";
            $.each(data, function(index, item) {
                items += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#masucalmacen").html(items);
        });
    });

    function generarrevision() {
        let sucursal = $("#masucalmacen").val();

        if (sucursal == 0) {
            alert("Selecciona una sucursal");
            return;
        }

        $.getJSON("../ajax/almacenes.revision.php?sucursal=" + sucursal, function(data) {
            if (data.length === 0) {
                $("#tabla-resultados").html("<p>No hay datos.</p>");
                return;
            }
            let html = `<div class="table-responsive table-responsive-stack"><table class="table table-bordered table-sm w-100" style="width: 100% !important;">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nombre</th>
                                    <th>Faltantes</th>
                                    <th>Inventario</th>
                                    <th>Comprar</th>
                                </tr>
                            </thead>
                            <tbody>`;
            $.each(data, function(index, item) {
                html += `<tr>
                            <td data-label="#">
                                <input type="checkbox" class="check-articulo" name="seleccionados[]" checked>
                                <input type="hidden" class="articulo-id" value="${item.TIPOMALETADET_ARTICULOID}">
                            </td>
                            <td data-label="Nombre">${item.ARTICULONOMBRE}</td>
                            <td data-label="Faltantes">${item.FALTANTES}</td>
                            <td data-label="Inventario">${item.INVENTARIO}</td>
                            <td data-label="Comprar">
                                <input type="text" class="form-control cantidad-comprar" value="${item.COMPRAR}">
                            </td>
                        </tr>`;
            });
            html += `</tbody></table></div>`;
            $("#tabla-resultados").html(html);
        });
    }

    function procesarSeleccionados() {
        let seleccionados = [];

        $("#tabla-resultados table tbody tr").each(function() {
            let checkbox = $(this).find(".check-articulo");
            if (checkbox.is(":checked")) {
                let articulo_id = $(this).find(".articulo-id").val();
                let cantidad = $(this).find(".cantidad-comprar").val();
                seleccionados.push({
                    articulo_id: articulo_id,
                    cantidad: cantidad
                });
            }
        });

        if (seleccionados.length === 0) {
            alert("No hay artículos seleccionados.");
            return;
        }

        // Enviar por AJAX a tu PHP que procese
        $.ajax({
            url: '../ajax/almacenes.guardar.solicitudcompra.php',
            method: 'POST',
            data: {
                sucursalid: $("#masucalmacen").val(),
                articulos: seleccionados
            },
            success: function(response) {
                if (response.trim() === "") {
                    alert("Artículos procesados correctamente.");
                } else {
                    alert(response);
                }
            }
        });
    }
</script>