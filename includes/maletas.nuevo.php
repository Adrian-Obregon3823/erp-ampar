<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/head.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nueva Maleta</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nueva-maleta">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="matipoalmacen">Tipo de Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="matipoalmacen" name="matipoalmacen"></select>
                        </div>
                        <div class="form-group">
                            <label for="maalmacen">Almacén <span class="text-danger">*</span></label>
                            <select class="form-control" id="maalmacen" name="maalmacen" disabled></select>
                        </div>
                        <div class="form-group">
                            <label for="mafamilia">Familia <span class="text-danger">*</span></label>
                            <select class="form-control" id="mafamilia" name="mafamilia"></select>
                        </div>
                        <div class="form-group">
                            <label for="madivision">División <span class="text-danger">*</span></label>
                            <select class="form-control" id="madivision" name="madivision" disabled></select>
                        </div>
                        <div class="form-group">
                            <label for="matipomaleta">Tipo de Maleta <span class="text-danger">*</span></label>
                            <select class="form-control" id="matipomaleta" name="matipomaleta" disabled></select>
                        </div>
                        <div class="form-group">
                            <label for="mamaletanom">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mamaletanom" name="mamaletanom" placeholder="Nombre de Maleta">
                        </div>
                        <div class="form-group">
                            <label for="mamaletades">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="mamaletades" name="mamaletades" placeholder="Descripción de Maleta"></textarea>
                        </div>
                        <button type="button" class="btn btn-success" onclick="guardarmaleta();">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>
<script>
    $(document).ready(function() {

        //Get Tipo Almacén
        var itemstipoalmacen = "";
        $.getJSON("../ajax/get.almacentipo.php", function(data) {
            itemstipoalmacen += "<option value='0'></option>";
            $.each(data, function(index, item) {
                itemstipoalmacen += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#matipoalmacen").html(itemstipoalmacen);
        });

        //Change Tipo Almacén -> populate Almacenes
        $("#matipoalmacen").change(function() {
            var tipoalmacen = $(this).val();
            var itemsalmacen = "";
            if (tipoalmacen == 0 || !tipoalmacen) {
                $("#maalmacen").html("<option value='0'></option>").prop("disabled", true);
                return;
            }
            $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=" + tipoalmacen, function(data) {
                itemsalmacen += "<option value='0'></option>";
                if (data && data.length > 0) {
                    $.each(data, function(index, item) {
                        if (item.ID) { // avoid empty IDs
                            itemsalmacen += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                        }
                    });
                    $("#maalmacen").html(itemsalmacen).prop("disabled", false);
                } else {
                    $("#maalmacen").html("<option value='0'>Sin almacenes en este tipo</option>").prop("disabled", true);
                }
            });
        });

        // Get Familias
        var itemsmafamilia = "";
        $.getJSON("../ajax/get.familias.php", function(data) {
            itemsmafamilia += "<option value='0'></option>";
            $.each(data, function(index, item) {
                itemsmafamilia += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#mafamilia").html(itemsmafamilia);
            $("#madivision").html("<option value='0'></option>");
            $("#matipomaleta").html("<option value='0'></option>");
        });

    });

    $("#mafamilia").change(function() {
        var familiaid = $(this).val();
        var itemsmadivision = "";
        if (familiaid == 0 || !familiaid) {
            $("#madivision").html("<option value='0'></option>").prop("disabled", true);
            $("#matipomaleta").html("<option value='0'></option>").prop("disabled", true);
            return;
        }
        $.getJSON("../ajax/get.divisiones.php?familiaid=" + familiaid, function(data) {
            itemsmadivision += "<option value='0'></option>";
            if (data && data[0] && data[0].ID !== "") {
                $.each(data, function(index, item) {
                    itemsmadivision += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                });
                $("#madivision").html(itemsmadivision).prop("disabled", false);
            } else {
                $("#madivision").html("<option value='0'>Sin divisiones en esta familia</option>").prop("disabled", true);
            }
            $("#matipomaleta").html("<option value='0'></option>").prop("disabled", true);
        });
    });

    $("#madivision").change(function() {
        var divisionid = $(this).val();
        var itemsmatipomaletas = "";
        if (divisionid == 0) {
            $("#matipomaleta").html("<option value='0'></option>").prop("disabled", true);
            return;
        }
        $.getJSON("../ajax/get.catalogotipomaleta.php?divisionid=" + divisionid, function(data) {
            let tipoMaletasValidas = data.filter(m => m.ID && m.NOMBRE);
            if (tipoMaletasValidas.length > 0) {
                itemsmatipomaletas += "<option value='0'></option>";
                $.each(data, function(index, item) {
                    itemsmatipomaletas += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
                });
                $("#matipomaleta").html(itemsmatipomaletas).prop("disabled", false);
            } else {
                $("#matipomaleta").html("<option value='0'>Sin tipos de maleta en esta división</option>").prop("disabled", true);
            }
        });
    });

    function guardarmaleta() {
        Swal.fire({
            text: '¿Seguro que deseas agregar la maleta?',
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
                if ($("#matipoalmacen").val() == 0 || !$("#matipoalmacen").val()) {
                    Swal.fire({
                        html: "Tipo de Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#matipoalmacen').focus();
                        }
                    });
                } else if ($("#maalmacen").val() == 0 || !$("#maalmacen").val()) {
                    Swal.fire({
                        html: "Almacén es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#maalmacen').focus();
                        }
                    });
                } else if ($("#mafamilia").val() == 0 || !$("#mafamilia").val()) {
                    Swal.fire({
                        html: "Familia es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#mafamilia').focus();
                        }
                    });
                } else if ($("#madivision").val() == 0) {
                    Swal.fire({
                        html: "División es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#madivision').focus();
                        }
                    });
                } else if ($("#matipomaleta").val() == 0) {
                    Swal.fire({
                        html: "Tipo de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#matipomaleta').focus();
                        }
                    });
                } else if ($("#mamaletanom").val() == "") {
                    Swal.fire({
                        html: "Nombre de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#mamaletanom').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else if ($("#mamaletades").val() == "") {
                    Swal.fire({
                        html: "Descripción de Maleta es un campo obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success' // usa clases de Bootstrap
                        },
                        didClose: () => {
                            $('#mamaletades').focus(); // Ahora sí se aplica bien el foco
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-nueva-maleta"));
                    $.ajax({
                        url: '../ajax/maletas.nuevo.php',
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
                                    html: "Maleta guardada con éxito",
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