<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ PARA AGREGAR UN ALMACEN, DE TIPO 1 O 2
*********************************************************************************
*/
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nuevo Almacén</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nuevo-almacen">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="alsucursal">Categoría <span class="text-danger">*</span></label>
                            <select class="form-control" id="alsucursal" name="alsucursal" required></select>
                        </div>
                        <div class="form-group">
                            <label for="altipoalmacen">Tipo de Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="altipoalmacen" name="altipoalmacen" required></select>
                        </div>
                        <div class="form-group">
                            <label for="almacennom">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="almacennom" name="almacennom" placeholder="Nombre de Almacén" maxlength="50" required>
                        </div>
                        <div class="form-group">
                            <label for="aldescripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="aldescripcion" name="aldescripcion" placeholder="Descripción de Almacén" maxlength="250" required></textarea>
                        </div>
                        <h5>Dirección</h5><br>
                        <div class="form-group">
                            <label for="alcalle">Calle</label>
                            <input type="text" class="form-control" id="alcalle" name="alcalle" placeholder="Calle" maxlength="250">
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <label for="alnumext"># Exterior</label>
                                    <input type="text" class="form-control" id="alnumext" name="alnumext" maxlength="10">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="alnumint"># Interior</label>
                                    <input type="text" class="form-control" id="alnumint" name="alnumint" maxlength="10">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alcolonia">Colonia</label>
                            <input type="text" class="form-control" id="alcolonia" name="alcolonia" placeholder="Colonia" maxlength="250">
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-4 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="alcp">CP</label>
                                    <input type="text" class="form-control" id="alcp" name="alcp" placeholder="Código Postal" maxlength="5">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="altel">Teléfono</label>
                                    <input type="text" class="form-control" id="altel" name="altel" placeholder="Teléfono" maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="alpais">País</label>
                                    <select class="form-control" id="alpais" name="alpais"></select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="alestados">Estado</label>
                                    <select class="form-control" id="alestados" name="alestados"></select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <div class="form-group">
                                    <label for="almunicipios">Municipio</label>
                                    <select class="form-control" id="almunicipios" name="almunicipios"></select>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success" onclick="guardar();">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {

        //Get Sucursal
        var items1 = "";
        $.getJSON("../ajax/get.sucursales.php?usuarioid=<?= $usersesion['USUARIO_ID'] ?>", function(data) {
            items1 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#alsucursal").html(items1);
        });

        //Get Tipo Almacen
        var items2 = "";
        $.getJSON("../ajax/get.almacentipo.php", function(data) {
            items2 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items2 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#altipoalmacen").html(items2);
        });

        //Get Paises
        var items3 = "";
        $.getJSON("../ajax/get.paises.php", function(data) {
            items3 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items3 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#alpais").html(items3);
        });


    });

    $("#alpais").change(function() {
        //Get Estados
        var items4 = "";
        $.getJSON("../ajax/get.estados.php?paisid=" + $("#alpais").val(), function(data) {
            items4 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items4 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#alestados").html(items4);
        });
    });

    $("#alestados").change(function() {
        //Get Municipios
        var items5 = "";
        $.getJSON("../ajax/get.municipios.php?estadoid=" + $("#alestados").val(), function(data) {
            items5 += "<option value=''></option>";
            $.each(data, function(index, item) {
                items5 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#almunicipios").html(items5);
        });
    });

    function guardar() {
        Swal.fire({
            text: '¿Seguro que deseas agregar el almacén?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, agregar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false // necesario si usas clases Bootstrap
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#alsucursal").val() == "") {
                    Swal.fire({
                        html: "Sucursal es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#alsucursal').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#altipoalmacen").val() == "") {
                    Swal.fire({
                        html: "Tipo de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#altipoalmacen').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#almacennom").val() == "") {
                    Swal.fire({
                        html: "Nombre de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#almacennom').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#aldescripcion").val() == "") {
                    Swal.fire({
                        html: "Descripción de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#aldescripcion').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-nuevo-almacen"));
                    $.ajax({
                        url: '../ajax/almacenes.nuevo.php',
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
                                    html: "Almacén guardado con éxito",
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