<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$maletas = new maletas();
$divisionid = isset($_GET['divisionid']) ? base64_decode($_GET['divisionid']) : '';
$infodivision = $maletas->getinfodivisionbyid($divisionid);

if (!$infodivision || empty($infodivision)) {
    echo "<div class='alert alert-danger'>División no encontrada.</div>";
    exit;
}
$div = $infodivision[0];
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Editar División</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-editar-division">
                <input type="hidden" name="divisionid" value="<?= $div['DIVISION_ID'] ?>">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="divnombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="divnombre" name="divnombre" placeholder="Nombre de la División" maxlength="100" value="<?= htmlspecialchars($div['DIVISION_NOMBRE']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="divfamilia">Familia <span class="text-danger">*</span></label>
                            <select class="form-control" id="divfamilia" name="divfamilia">
                                <option value=""></option>
                                <?php
                                $maletas_cls = new maletas();
                                $familias = $maletas_cls->getcatalogofamilias();
                                if ($familias <> 0) {
                                    foreach ($familias as $fam) {
                                        $selected = ($fam['ID'] == $div['DIVISION_FAMILIAID']) ? 'selected' : '';
                                        echo "<option value='{$fam['ID']}' $selected>{$fam['NOMBRE']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="divdescripcion">Descripción</label>
                            <textarea class="form-control" id="divdescripcion" name="divdescripcion" placeholder="Descripción de la División" maxlength="250"><?= htmlspecialchars($div['DIVISION_DESCRIPCION']) ?></textarea>
                        </div>
                        <button type="button" class="btn btn-success" onclick="guardarediciondivision();">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function guardarediciondivision() {
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
                if ($("#divnombre").val() == "") {
                    Swal.fire({
                        html: "El nombre de la División es obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#divnombre').focus();
                        }
                    });
                } else if ($("#divfamilia").val() == "") {
                    Swal.fire({
                        html: "Seleccionar una Familia es obligatorio",
                        icon: "warning",
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        didClose: () => {
                            $('#divfamilia').focus();
                        }
                    });
                } else {
                    var formData = new FormData(document.getElementById("form-editar-division"));
                    $.ajax({
                        url: '../ajax/divisiones.editar.php',
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
                                    html: "División actualizada con éxito",
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