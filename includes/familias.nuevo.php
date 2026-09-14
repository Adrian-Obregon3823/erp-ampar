<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nueva Familia</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-nueva-familia">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="famnombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="famnombre" name="famnombre" placeholder="Nombre de la Familia" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="famdescripcion">Descripción</label>
                            <textarea class="form-control" id="famdescripcion" name="famdescripcion" placeholder="Descripción de la Familia" maxlength="250"></textarea>
                        </div>
                        <button type="button" class="btn btn-success" onclick="guardarfamilia();">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function guardarfamilia() {
        Swal.fire({
            text: '¿Seguro que deseas agregar la familia?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, agregar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                if ($("#famnombre").val() == "") {
                    Swal.fire({
                        html: "El nombre de la Familia es obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#famnombre').focus();
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-nueva-familia"));
                    $.ajax({
                        url: '../ajax/familias.nuevo.php',
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
                                    html: "Familia guardada con éxito",
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
                        complete: function() {
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
