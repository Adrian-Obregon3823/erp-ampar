<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
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
                      <div class="col-4">
                        <div class="card">
                          <div class="card-header">
                            <h4>Compras</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $compras = new compras();
                            $res = $compras->getcompras();
                            if ($res <> 0) {
                              ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col">Id</th>
                                      <th scope="col">Nombre</th>
                                      <th scope="col">Estado</th>
                                      <th scope="col" width="100px"></th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($res as $row){?>
                                    <tr>
                                      <td><?=$row['COMPRA_FOLIO']?></td>
                                      <td><?=$row['NOMBRE']?></td>
                                      <td style="color:#<?=$row['STATUS_COLOR']?>"><?=$row['STATUS_NOMBRE']?></td>
                                      <td>
                                        <a href="../gdocs/compras.formato.php?compraid=<?=base64_encode($row['COMPRA_ID'])?>" target="_blank"><img src="../img/pdf.png" style="width:25px; height:auto; cursor:pointer;" title="Solicitud de Compra"></a>
                                      </td>
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
                                      <span class="mobile-card-title"><?=$row['COMPRA_FOLIO']?></span>
                                      <span class="mobile-card-badge" style="color:#<?=$row['STATUS_COLOR']?>; border: 1px solid #<?=$row['STATUS_COLOR']?>40; background-color: #<?=$row['STATUS_COLOR']?>15;">
                                        <?=$row['STATUS_NOMBRE']?>
                                      </span>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Nombre</span>
                                        <span class="mobile-card-value"><?=$row['NOMBRE']?></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a href="../gdocs/compras.formato.php?compraid=<?=base64_encode($row['COMPRA_ID'])?>" target="_blank"><img src="../img/pdf.png" style="width:28px; height:auto; cursor:pointer;" title="Solicitud de Compra"></a>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                              <?php
                            } else {
                                  echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
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

</script>