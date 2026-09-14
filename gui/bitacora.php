<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ PRINCIPAL DE BITACORA
*********************************************************************************
*/
?>
<?php
$bitacora = new bitacora();
$anioActual = date('Y');
$anio = $_GET['anio'] ?? $anioActual;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$ip = $_GET['ip'] ?? '';
$comentario = $_GET['comentario'] ?? '';
$sucursal = $_GET['sucursal'] ?? '';
$almacen = $_GET['almacen'] ?? '';
$articulo = $_GET['articulo'] ?? '';

$res = $bitacora->getbitacora($anio, $fecha_inicio, $fecha_fin, $usuario, $ip, $comentario, $sucursal, $almacen, $articulo);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
    <style>
      /* Mobile Cards Styles */
      .mobile-bitacora-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
        background-color: #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      }
      .mobile-bitacora-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
      }
      .mobile-bitacora-fecha {
        font-weight: 600;
        color: #475569;
        font-size: 0.85rem;
      }
      .mobile-bitacora-usuario {
        font-weight: 700;
        color: #1f3bb3;
        font-size: 0.95rem;
      }
      .mobile-bitacora-body {
        font-size: 0.95rem;
        color: #334155;
        line-height: 1.4;
        margin-bottom: 12px;
        word-break: break-word;
      }
      .mobile-bitacora-actions {
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid #f1f5f9;
        padding-top: 10px;
      }
    </style>
  </head>
  <body>
    <div id="loading" style="display:none">
      <img id="loading-image" src="../img/logo.png"/>
    </div>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <!-- Inicia contenido principal -->
              <div class="container-fluid">
                <div class="row mb-4">
                  <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalFiltro">Buscar</button>
                    <button class="btn btn-success" id="downloadExcelButton">Descargar Excel</button>
                  </div>
                </div>

                <div class="card card-rounded">
                  <div class="card-body">
                    <h4 class="card-title">Bitácora de Actividades</h4>
                    
                      <?php if ($res<>0){?>
                        <!-- Vista de Escritorio -->
                        <div class="table-responsive d-none d-md-block" style="width: 100%;">
                          <table class="table table-bordered" style="font-size:9px !important; width:100%">
                            <thead>
                              <tr>
                                <th width="40px"></th>  
                                <th width="150px">Fecha</th>
                                <th width="250px">Usuario</th>
                                <th style="white-space: normal;word-break: break-word;">Acción Realizada</th>
                              </tr>
                            </thead>
                            <tbody>
                              
                                <?php foreach ($res as $r){?>
                                  <tr>
                                    <td><a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Actividad" data-url="../includes/bitacora.info.php?bitacoraid=<?=base64_encode($r['BITACORA_ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;"></a></td>
                                    <td><?= $r['BITACORA_FECHA'] ?></td>
                                    <td><?= $r['USUARIO_NOMBRE'] ?></td>
                                    <td style="white-space: normal;word-break: break-word;"><?= $r['BITACORA_COMENTARIO'] ?></td>
                                  </tr>
                                <?php }?>
                              
                            </tbody>
                          </table>
                        </div>

                        <!-- Vista Móvil (Tarjetas) -->
                        <div class="d-block d-md-none mt-2">
                          <?php foreach ($res as $r){?>
                            <div class="mobile-bitacora-card">
                              <div class="mobile-bitacora-header">
                                <span class="mobile-bitacora-usuario"><i class="mdi mdi-account-circle mr-1"></i> <?= $r['USUARIO_NOMBRE'] ?></span>
                                <span class="mobile-bitacora-fecha"><?= $r['BITACORA_FECHA'] ?></span>
                              </div>
                              <div class="mobile-bitacora-body">
                                <?= $r['BITACORA_COMENTARIO'] ?>
                              </div>
                              <div class="mobile-bitacora-actions">
                                <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Actividad" data-url="../includes/bitacora.info.php?bitacoraid=<?=base64_encode($r['BITACORA_ID'])?>" aria-selected="false" class="btn btn-sm btn-outline-primary w-100">
                                  <i class="mdi mdi-information-outline"></i> Ver Detalle
                                </a>
                              </div>
                            </div>
                          <?php }?>
                        </div>
                        <?php }else{?>
                          <div class="alert alert-warning">No se encontró información</div>
                        <?php }?>
                    
                  </div>
                </div>
              </div>

              <!-- Modal Filtro -->
              <div class="modal fade" id="modalFiltro" tabindex="-1" aria-labelledby="modalFiltroLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <form id="filtroBitacora" method="get">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalFiltroLabel">Filtrar Bitácora</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                      </div>
                      <div class="modal-body row g-3">
                        <div class="col-md-4">
                          <label for="anio" class="form-label">Año</label>
                          <select class="form-select" id="anio" name="anio" onchange="document.getElementById('filtroBitacora').submit();">
                            <?php for ($a = $anioActual; $a >= 2020; $a--): ?>
                              <option value="<?= $a ?>" <?= ($anio == $a) ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endfor; ?>
                          </select>
                        </div>
                        <div class="col-md-4">
                          <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                          <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?= $fecha_inicio ?>">
                        </div>
                        <div class="col-md-4">
                          <label for="fecha_fin" class="form-label">Fecha fin</label>
                          <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" value="<?= $fecha_fin ?>">
                        </div>
                        <div class="col-md-4">
                          <label for="usuario" class="form-label">Usuario</label>
                          <input type="text" class="form-control" name="usuario" id="usuario" value="<?= $usuario ?>">
                        </div>
                        <div class="col-md-4">
                          <label for="ip" class="form-label">IP</label>
                          <input type="text" class="form-control" name="ip"  id="ip"  value="<?= $ip ?>">
                        </div>
                        <div class="col-md-4">
                          <label for="comentario" class="form-label">Comentario</label>
                          <input type="text" class="form-control" name="comentario" id="comentario" value="<?= $comentario ?>">
                        </div>
                        <div class="col-md-6">
                          <label for="sucursales" class="form-label">Sucursal</label>
                          <select class="form-select" id="sucursales" name="sucursal"></select>
                        </div>
                        <div class="col-md-6">
                          <label for="almacenes" class="form-label">Almacén</label>
                          <select class="form-select" id="almacenes" name="almacenes"></select>
                        </div>
                        <div class="col-md-12">
                          <label for="articulo" class="form-label">Artículo</label>
                          <input type="text" class="form-control" name="articulo" id="articulo" value="<?= $articulo ?>">
                        </div>
                      </div>
                      <div class="modal-footer">
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">Limpiar Filtros</button>
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                      </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <!-- Finaliza contenido princial -->
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
  $(document).ready(function(){
    // Get Sucursal
    var items1 = "";
    $.getJSON("../ajax/get.sucursales.php", function(data) {
        items1 += "<option value=''></option>";
        $.each(data, function(index, item) {
            items1 += "<option value='" + item.ID + "'" + (item.ID == "<?= $sucursal ?>" ? " selected" : "") + ">" + item.NOMBRE + "</option>";
        });
        $("#sucursales").html(items1);
    });

    // Get Almacen
    var items2 = "";
    $.getJSON("../ajax/get.almacenes.catalogo.php", function(data) {
        items2 += "<option value=''></option>";
        $.each(data, function(index, item) {
            items2 += "<option value='" + item.ID + "'" + (item.ID == "<?= $almacen ?>" ? " selected" : "") + ">" + item.NOMBRE + "</option>";
        });
        $("#almacenes").html(items2);
    });
  });

  document.getElementById("downloadExcelButton").addEventListener("click", function () {
    const params = new URLSearchParams({
      anio: '<?= $anio ?>',
      fecha_inicio: '<?= $fecha_inicio ?>',
      fecha_fin: '<?= $fecha_fin ?>',
      usuario: '<?= $usuario ?>',
      ip: '<?= $ip ?>',
      comentario: '<?= $comentario ?>',
      sucursal: '<?= $sucursal ?>',
      almacen: '<?= $almacen ?>',
      articulo: '<?= $articulo ?>'
    });

    // Mostrar loading
    document.getElementById("loading").style.display = "block";

    fetch(`../includes/bitacora.exportarexcel.php?${params.toString()}`, {
      method: 'GET'
    })
    .then(response => {
      if (!response.ok) {
        throw new Error("Error en la descarga.");
      }
      return response.blob();
    })
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = "bitacora.xlsx";
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    })
    .catch(error => {
      alert("No se pudo descargar el archivo.");
      console.error(error);
    })
    .finally(() => {
      document.getElementById("loading").style.display = "none";
    });
  });

  $('#modalglobal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var url = button.data('url');
    var title = button.data('title');

    var modal = $(this);
    modal.find('.modal-title').text(title);
    modal.find('.modal-dialog').addClass('modal-xl')

    // Mostrar loading
    $('#loading').show();

    // AJAX
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        modal.find('.modal-body').html(response);
      },
      error: function() {
        modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
      },
      complete: function() {
        // Ocultar loading al finalizar (éxito o error)
        $('#loading').hide();
      }
    });
  });

  function limpiarFiltros() {
    const form = document.getElementById("filtroBitacora");
    form.reset();

    // Limpiar campos individuales correctamente usando jQuery con ID
    $("#fecha_inicio").val('');
    $("#fecha_fin").val('');
    $("#usuario").val('');
    $("#ip").val('');
    $("#comentario").val('');
    $("#articulo").val('');

    // Limpiar selects dinámicos cargados por AJAX
    $("#sucursales").val('').trigger('change');
    $("#almacenes").val('').trigger('change');
    $("#anio").val(new Date().getFullYear()).trigger('change'); // opcional si quieres resetear el año al actual
  }

</script>