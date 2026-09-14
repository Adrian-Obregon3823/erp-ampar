<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$maletas = new maletas();
$familiaid = isset($_GET['familiaid']) ? base64_decode($_GET['familiaid']) : '';
$infofamilia = $maletas->getinfofamiliabyid($familiaid);

if (!$infofamilia || empty($infofamilia)) {
    echo "<div class='alert alert-danger'>Familia no encontrada.</div>";
    exit;
}
$fam = $infofamilia[0];
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Editar Familia</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-familia">
                <input type="hidden" name="familiaid" value="<?= $fam['FAMILIA_ID'] ?>">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="famnombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="famnombre" name="famnombre" placeholder="Nombre de la Familia" maxlength="100" value="<?= htmlspecialchars($fam['FAMILIA_NOMBRE']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="famdescripcion">Descripción</label>
                            <textarea class="form-control" id="famdescripcion" name="famdescripcion" placeholder="Descripción de la Familia" maxlength="250"><?= htmlspecialchars($fam['FAMILIA_DESCRIPCION']) ?></textarea>
                        </div>
                        <button type="button" class="btn btn-success" onclick="guardaredicionfamilia();">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function guardaredicionfamilia() {
        Swal.fire({
            text: '¿Seguro que deseas guardar los cambios?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
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
                    var formData = new FormData(document.getElementById("form-editar-familia"));
                    $.ajax({
                        url: '../ajax/familias.editar.php',
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
                                    html: "Familia actualizada con éxito",
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
