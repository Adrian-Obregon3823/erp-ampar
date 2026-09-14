<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
  <style>
    .container-fluid > .main-panel { margin-left:0!important; padding-left:0!important; width:100%!important; }
    .page-body-wrapper { margin-left:0!important; }
  </style>
    <?php 
    $eventoid = base64_decode($_GET['eventoid']);
    $eventos = new eventos();
    $res = $eventos->geteventobyid($eventoid);
    ?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.externo.php"); ?>
      <div class="container-fluid">
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
                      <div class="row justify-content-center w-100">
                        <div class="col-12 col-md-6 col-lg-4">
                          <div class="card">
                            <div class="card-header">
                              <b><h4>Evento</h4></b>
                            </div>
                            <div class="card-body">
                              <b><h2><div align="center">Folio: <span style="color:green"><?=$res[0]["EVENTO_FOLIO"]?></span></div></h2></b>
                              <b><div align="center">Duración: <?=$res[0]["DURACION_HORAS"]?> HRS.</div></b>
                              <br>
                              <div align="center">Lugar: <span style="color:darkgray"><?=$res[0]['HOSPITAL_NOMBRE']?></span></div>
                              <div align="center">Fecha: <span style="color:darkgray"><?=$res[0]['EVENTO_FECHAI']?></span></div>
                              <div align="center">
                                <div class="col-8">
                                  <br>
                                  <div class="form-group">
                                    <label for="quienescanea">Validación</label>
                                    <select class="form-control" id="quienescanea" name="quienescanea">
                                          <option value=""></option>
                                          <option value=""><?=$res[0]['ESPECIALISTA_NOMBRE']?></option>
                                          <option value=""><?=$res[0]['CHOFER_NOMBRE']?></option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label for="clave">Clave</label>
                                    <input type="test" class="form-control" id="clave" name="clave">
                                  </div>
                                </div>
                                <div class="col-8">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-warning" id="eagregarmaleta">Validar</button>
                                    </div>
                                </div>
                              </div>
                              <b>Maletas</b>
                              <table cellpadding="5">
                                <?php
                                foreach ($res as $r){
                                  ?>
                                    <tr><td> - <?=$r['ALMACEN_NOMBRE']?></td></tr>
                                  <?php
                                }
                                ?>
                              </table>
                            </div>
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