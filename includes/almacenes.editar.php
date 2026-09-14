<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ PARA EDITAR UN ALMACEN, DE TIPO 1 O 2
*********************************************************************************
*/
?>
<?php
$almacen = new almacenes();
$res = $almacen->getinfoalmacen(base64_decode($_GET['almacenid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Edición de Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-almacen">
                <input type="hidden" id="almaceneditid" name="almaceneditid" value="<?= $res[0]['ALMACEN_ID'] ?>">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="aleditsucursal">Categoría <span class="text-danger">*</span></label>
                            <select class="form-control" id="aleditsucursal" name="aleditsucursal"></select>
                        </div>
                        <div class="form-group">
                            <label for="aledittipo">Tipo de Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="aledittipo" name="aledittipo"></select>
                        </div>
                        <div class="form-group">
                            <label for="almaceneditnom">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="almaceneditnom" name="almaceneditnom" placeholder="Nombre de Almacén" maxlength="50" value="<?= $res[0]['ALMACEN_NOMBRE'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="aleditdescripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="aleditdescripcion" name="aleditdescripcion" placeholder="Descripción de Almacén" maxlength="250"><?= $res[0]['ALMACEN_DESCRIPCION'] ?></textarea>
                        </div>
                        <h5>Dirección</h5><br>
                        <div class="form-group">
                            <label for="aleditcalle">Calle</label>
                            <input type="text" class="form-control" id="aleditcalle" name="aleditcalle" placeholder="Calle" maxlength="250" value="<?= $res[0]['ALMACEN_CALLE'] ?>">
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <label for="aleditnumext"># Exterior</label>
                                    <input type="text" class="form-control" id="aleditnumext" name="aleditnumext" maxlength="10" value="<?= $res[0]['ALMACEN_NUMEXT'] ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="aleditnumint"># Interior</label>
                                    <input type="text" class="form-control" id="aleditnumint" name="aleditnumint" maxlength="10" value="<?= $res[0]['ALMACEN_NUMINT'] ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="aleditcolonia">Colonia</label>
                            <input type="text" class="form-control" id="aleditcolonia" name="aleditcolonia" placeholder="Colonia" maxlength="250" value="<?= $res[0]['ALMACEN_COLONIA'] ?>">
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-4 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="aleditcp">CP</label>
                                    <input type="text" class="form-control" id="aleditcp" name="aleditcp" placeholder="Código Postal" maxlength="5" value="<?= $res[0]['ALMACEN_CP'] ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="aledittel">Teléfono</label>
                                    <input type="text" class="form-control" id="aledittel" name="aledittel" placeholder="Teléfono" maxlength="100" value="<?= $res[0]['ALMACEN_TELEFONO'] ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="aleditpais">País</label>
                                    <select class="form-control" id="aleditpais" name="aleditpais"></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="aleditestados">Estado</label>
                                    <select class="form-control" id="aleditestados" name="aleditestados"></select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="aleditmunicipios">Municipio</label>
                                    <select class="form-control" id="aleditmunicipios" name="aleditmunicipios"></select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="aleditstatus">Status</label>
                                    <select class="form-control" id="aleditstatus" name="aleditstatus">
                                        <option value="17">Activo</option>
                                        <option value="18">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success" onclick="editar();">Modificar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        $("#aleditstatus").val('<?= $res[0]['ALMACEN_STATUS'] ?>');

        //Get Categorías (Sucursales)
        var itemsSucursales = "";
        $.getJSON("../ajax/get.sucursales.php", function(data) {
            itemsSucursales += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsSucursales += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditsucursal").html(itemsSucursales);
            $("#aleditsucursal").val('<?= $res[0]['ALMACEN_SUCURSAL_MS'] ?>');
        });

        //Get Tipos de Almacén
        var itemsTipos = "";
        $.getJSON("../ajax/get.almacentipo.php", function(data) {
            itemsTipos += "<option value=''></option>";
            $.each(data, function(index, item) {
                itemsTipos += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aledittipo").html(itemsTipos);
            $("#aledittipo").val('<?= $res[0]['ALMACEN_TIPOALMACEN'] ?>');
        });

        //Get Paises
        var items3 = "";
        $.getJSON("../ajax/get.paises.php", function(data) {
            items3 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items3 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditpais").html(items3);
            $("#aleditpais").val('<?= $res[0]['ALMACEN_PAIS'] ?>');
        });

        //Get Estados
        var items4 = "";
        $.getJSON("../ajax/get.estados.php?paisid=<?= $res[0]['ALMACEN_PAIS'] ?>", function(data) {
            items4 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items4 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditestados").html(items4);
            $("#aleditestados").val('<?= $res[0]['ALMACEN_ESTADO'] ?>');
        });

        //Get Municipios
        var items5 = "";
        $.getJSON("../ajax/get.municipios.php?estadoid=<?= $res[0]['ALMACEN_ESTADO'] ?>", function(data) {
            items5 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items5 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditmunicipios").html(items5);
            $("#aleditmunicipios").val('<?= $res[0]['ALMACEN_MUNICIPIO'] ?>');
        });


    });

    $("#aleditpais").change(function() {
        //Get Estados
        var items1 = "";
        $.getJSON("../ajax/get.estados.php?paisid=" + $("#aleditpais").val(), function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditestados").html(items1);
        });
    });

    $("#aleditestados").change(function() {
        //Get Municipios
        var items2 = "";
        $.getJSON("../ajax/get.municipios.php?estadoid=" + $("#aleditestados").val(), function(data) {
            items2 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items2 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#aleditmunicipios").html(items2);
        });
    });

    function editar() {
        Swal.fire({
            text: '¿Seguro que deseas modificar la información del almacén?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, modificar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#aleditsucursal").val() == "") {
                    Swal.fire({
                        html: "Categoría es un campo obligatorio",
                        icon: "warning",
                        customClass: { confirmButton: 'btn btn-success' },
                        didClose: () => { $('#aleditsucursal').focus(); }
                    });
                } else if ($("#aledittipo").val() == "") {
                    Swal.fire({
                        html: "Tipo de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: { confirmButton: 'btn btn-success' },
                        didClose: () => { $('#aledittipo').focus(); }
                    });
                } else if ($("#almaceneditnom").val() == "") {
                    Swal.fire({
                        html: "Nombre de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: { confirmButton: 'btn btn-success' },
                        didClose: () => { $('#almaceneditnom').focus(); }
                    });
                } else if ($("#aleditdescripcion").val() == "") {
                    Swal.fire({
                        html: "Descripción de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: { confirmButton: 'btn btn-success' },
                        didClose: () => { $('#aleditdescripcion').focus(); }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-editar-almacen"));
                    $.ajax({
                        url: '../ajax/almacenes.editar.php',
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
                                    html: "Almacén modificado con éxito",
                                    icon: "success",
                                    customClass: { confirmButton: 'btn btn-success' }
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    html: response,
                                    icon: "warning",
                                    customClass: { confirmButton: 'btn btn-success' }
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