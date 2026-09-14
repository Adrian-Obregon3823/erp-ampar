<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ PRINCIPAL DE ampar_cat_sucursales DE MICROSIP
*********************************************************************************
*/
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
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
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <?php //include_once("../includes/almacenes.menu.php");?>
                </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12 col-md-6">
                        <div class="card">
                          <div class="card-header">
                            <h4>Sucursales</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $sucursales = new sucursales();
                            $res = $sucursales->getsucursales();

                            if ($res <> 0) {
                              ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col" width="100px"></th>
                                      <th scope="col">ID</th>
                                      <th scope="col">NOMBRE</th>
                                      <th scope="col">MATRIZ</th>
                                      <th scope="col">CIUDAD</th>
                                      
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($res as $row){?>
                                    <tr>
                                      <td>
                                        <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Información Sucursal" data-url="../includes/sucursales.info.php?sucursalid=<?=base64_encode($row['ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:20px"></a>
                                      </td>
                                      <td><b><?=$row['FOLIO']?></b></td>
                                      <td><?=$row['NOMBRE']?></td>
                                      <td><?=($row['ES_MATRIZ']==TRUE)?'&#9679;':''?></td>
                                      <td><?=$row['CIUDAD_NOMBRE']?></td>
                                    </tr>
                                    <?php } ?>
                                  </tbody>
                                </table>
                              </div>
                              
                              <!-- Mobile View (Cards) -->
                              <div class="d-block d-md-none mt-2">
                                <?php foreach ($res as $row){?>
                                  <div class="mobile-card">
                                    <div class="mobile-card-header">
                                      <span class="mobile-card-title"><?=$row['NOMBRE']?></span>
                                      <?php if ($row['ES_MATRIZ']==TRUE): ?>
                                        <span class="mobile-card-badge" style="color:#007bff; border: 1px solid #007bff40; background-color: #007bff15;">Matriz</span>
                                      <?php endif; ?>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">ID / Folio</span>
                                        <span class="mobile-card-value font-weight-bold"><?=$row['FOLIO']?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Ciudad</span>
                                        <span class="mobile-card-value"><?=$row['CIUDAD_NOMBRE']?></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Información Sucursal" data-url="../includes/sucursales.info.php?sucursalid=<?=base64_encode($row['ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:28px"></a>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                              <?php
                            } else {
                                  echo "No se encontraron registros<br>";
                            } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Finaliza contenido princial -->
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
  $('#modalglobal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var url = button.data('url');
    var title = button.data('title');

    var modal = $(this);
    modal.find('.modal-title').text(title);

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
</script>