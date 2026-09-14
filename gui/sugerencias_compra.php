<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <div id="loading" style="display:none">
      <img id="loading-image" src="../img/logo.png"/>
    </div>
    <?php 
      $sucursalId = isset($_GET['sucursalid']) ? (int)$_GET['sucursalid'] : 0;
      $oc = new oc();
      $sugerencias = [];
      if ($sucursalId > 0) {
          $sugerencias = $oc->getSugerenciasCompra($sucursalId);
      }
    ?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="card shadow-sm border-0">
                  <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 font-weight-bold text-dark">Sugerencias de Compra</h4>
                    <?php if ($sucursalId > 0 && !empty($sugerencias)): ?>
                      <button class="btn btn-primary btn-rounded btn-fw shadow-sm" id="btnAutoborradores">
                        <i class="mdi mdi-auto-fix mr-1"></i> Generar Borradores Automáticos
                      </button>
                    <?php endif; ?>
                  </div>
                  <div class="card-body">
                    <!-- Filters -->
                    <div class="row align-items-end mb-4">
                      <div class="col-md-4">
                        <label for="filtroSucursal">Sucursal</label>
                        <select class="form-control" id="filtroSucursal">
                          <option value="">Seleccione Sucursal</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <button class="btn btn-warning w-100" id="btnConsultarSugerencias">Consultar</button>
                      </div>
                    </div>

                    <?php if ($sucursalId > 0): ?>
                      <?php if (!empty($sugerencias)): ?>
                        <div class="table-responsive" style="width: 100%;">
                          <table class="table table-striped table-hover">
                            <thead>
                              <tr>
                                <th>Clave</th>
                                <th>Artículo</th>
                                <th class="text-center">Stock Mínimo</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center">Stock en Tránsito</th>
                                <th class="text-center text-primary font-weight-bold">Sugerencia</th>
                                <th>Proveedor Predeterminado</th>
                                <th class="text-end">Costo Est.</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($sugerencias as $s): ?>
                                <tr>
                                  <td><?= htmlspecialchars($s['CLAVE_ARTICULO'] ?: 'S/K') ?></td>
                                  <td><?= htmlspecialchars($s['ARTICULO_NOMBRE']) ?></td>
                                  <td class="text-center"><?= number_format($s['STOCK_MINIMO'], 0) ?></td>
                                  <td class="text-center"><?= number_format($s['STOCK_ACTUAL'], 0) ?></td>
                                  <td class="text-center"><?= number_format($s['STOCK_TRANSITO'], 0) ?></td>
                                  <td class="text-center text-primary font-weight-bold"><?= number_format($s['CANTIDAD_SUGERIDA'], 0) ?></td>
                                  <td><?= htmlspecialchars($s['PROVEEDOR_NOMBRE']) ?></td>
                                  <td class="text-end">$<?= number_format($s['COSTO_UNITARIO'], 2) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php else: ?>
                        <div class="alert alert-success text-center">
                          <i class="mdi mdi-check-circle mr-2"></i> Todos los artículos tienen niveles óptimos de stock para esta sucursal.
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="alert alert-info text-center">
                        <i class="mdi mdi-information mr-2"></i> Seleccione una sucursal para consultar sugerencias de compra.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php include_once("../includes/modalglobal.php")?>
          <?php include_once("../includes/footer.php");?>
        </div>
      </div>
    </div>
    <?php include_once("../includes/foot.php");?>
  </body>
</html>
<script>
$(document).ready(function() {
    // Cargar Sucursales
    $.getJSON("../ajax/get.sucursales.php", function(data) {
        let options = "<option value=''>Seleccione Sucursal</option>";
        if (data && data !== 0) {
            $.each(data, function(_, item) {
                if (item.ID) {
                    const selected = (item.ID == "<?= $sucursalId ?>") ? "selected" : "";
                    options += "<option value='" + item.ID + "' " + selected + ">" + item.NOMBRE + "</option>";
                }
            });
        }
        $("#filtroSucursal").html(options);
    });

    $("#btnConsultarSugerencias").on("click", function() {
        const suc = $("#filtroSucursal").val();
        if (suc) {
            window.location.href = "sugerencias_compra.php?sucursalid=" + suc;
        } else {
            Swal.fire({ icon: 'warning', text: 'Seleccione una sucursal.' });
        }
    });

    $("#btnAutoborradores").on("click", function() {
        const suc = "<?= $sucursalId ?>";
        if (!suc) return;

        Swal.fire({
            title: '¿Generar borradores?',
            text: 'Se crearán automáticamente órdenes de compra de borrador agrupadas por proveedor predeterminado para los productos con faltantes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../ajax/oc.generar_borradores.php",
                    type: "POST",
                    data: { sucursalid: suc },
                    dataType: "json",
                    beforeSend: function() {
                        $("#loading").show();
                    },
                    success: function(resp) {
                        if (resp && resp.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                text: resp.message
                            }).then(function() {
                                window.location.href = "oc.php";
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                text: resp.message || 'Error al generar los borradores.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            text: 'Error de servidor.'
                        });
                    },
                    complete: function() {
                        $("#loading").hide();
                    }
                });
            }
        });
    });
});
</script>
