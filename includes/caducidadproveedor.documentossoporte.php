<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
$entradasalida = new entradasalida();
$activos = $entradasalida->getdsgarantiaproveedoractivosbyid(base64_decode($_GET['cpid']));
$inactivos = $entradasalida->getdsgarantiaproveedorinactivosbyid(base64_decode($_GET['cpid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Garantía de Proveedor</h4>
        </div>
        <div class="card-body">
            <form id="formSubirArchivo" method="POST" enctype="multipart/form-data" style="display:inline;" onsubmit="return false;">
                <input type="hidden" name="cpid" value="<?= htmlspecialchars(base64_decode($_GET['cpid'])) ?>">
                
                <input type="file" id="inputArchivo" name="archivo" style="display: none;" required>

                <label for="inputArchivo" style="cursor: pointer;">
                    <img src="../img/upload.jpg" title="Subir archivo" style="width: 50px; height: auto;">
                </label>
            </form>
            <br><br>
            <h5>📂 Archivos disponibles</h5>
            <ul>
            <?php if ($activos <> 0){ ?>
                <div class="container">
                    <?php foreach ($activos as $archivo): ?>
                        <div class="row align-items-center mb-2">
                            <!-- Archivo -->
                            <div class="col-md-5">
                                <a href="../uploads/garantiaproveedor/<?= htmlspecialchars($archivo['CADUCIDADPROVDETDS_NOMBRE']) ?>" target="_blank">
                                    <?= htmlspecialchars($archivo['CADUCIDADPROVDETDS_NOMBRE']) ?>
                                </a>
                            </div>

                            <!-- Usuario -->
                            <div class="col-md-4">
                                <?= htmlspecialchars($archivo['USUARIO_NOMBRE']) ?>
                            </div>

                            <!-- Botón eliminar -->
                            <div class="col-md-3 text-end">
                                <button class="btn btn-sm btn-danger" onclick="eliminarArchivo(<?= $archivo['CADUCIDADPROVDETDS_ID'] ?>)">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php }else{ ?>
                <div class="alert alert-warning text-center">No se encontraron registros</div>
            <?php } ?>
            </ul>
            <?php if ($inactivos <> 0){ ?>
                <h5 class="mt-4">🗑️ Archivos eliminados</h5>
                <ul>
                <?php foreach ($inactivos as $archivo): ?>
                    <li style="color:gray;">
                        <?=htmlspecialchars($archivo['CADUCIDADPROVDETDS_NOMBRE'])?>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>
<script>
document.getElementById('inputArchivo').addEventListener('change', function (e) {
    e.preventDefault(); // ← Previene cualquier comportamiento predeterminado por si acaso

    const form = document.getElementById('formSubirArchivo');
    const formData = new FormData(form);

    fetch('../ajax/caducidadproveedor.subirarchivo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(res => {
        if (res.trim() === '') {
            Swal.fire({
                html: "Archivo cargado con éxito",
                icon: "success",
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                html: res,
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
        }
    })
    .catch(err => {
        Swal.fire({
            html: "Error al cargar el archivo",
            icon: "error",
            customClass: {
                confirmButton: 'btn btn-danger'
            }
        });
        console.error(err);
    });
});
function eliminarArchivo(id) {
    Swal.fire({
          text: '¿Seguro que deseas eliminar el archivo?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          customClass: {
              confirmButton: 'btn btn-success',
              cancelButton: 'btn btn-secondary'
          },
          buttonsStyling: false // necesario si usas clases Bootstrap
      }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/caducidadproveedor.eliminararchivo.php',
                type: 'POST',
                data: {
                  id: id
                },
                dataType: 'html',
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(response) {
                    if (response.trim() === ""){
                        Swal.fire({
                            html: "Archivo eliminado con éxito",
                            icon: "success",
                            customClass: {
                                confirmButton: 'btn btn-success' // usa clases de Bootstrap
                            }
                        }).then(() => {
                            location.reload();
                        });
                    }else{
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
          }else{
            return false;
          }
      })
}
</script>