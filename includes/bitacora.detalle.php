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
$sucursalid = isset($_GET['sucursalid']) ? base64_decode($_GET['sucursalid']) : '';
$almacenid = isset($_GET['almacenid']) ? base64_decode($_GET['almacenid']) : '';
$eventoid = isset($_GET['eventoid']) ? base64_decode($_GET['eventoid']) : '';
$stockid = isset($_GET['stockid']) ? base64_decode($_GET['stockid']) : '';
$returnUrl = isset($_GET['return']) ? base64_decode($_GET['return']) : '';
$res = $bitacora->getbitacoradetalle($sucursalid, $almacenid, $eventoid, $stockid);
?>
<?php if ($returnUrl): ?>
  <button class="btn btn-secondary mb-3" id="btnVolver">
    ← Volver
  </button>
  <script>
  document.getElementById("btnVolver").addEventListener("click", function() {
    $('#loading').show();

    $.ajax({
      url: "<?= addslashes($returnUrl) ?>",
      type: "GET",
      success: function(response) {
        $('#modalglobal .modal-body').html(response);
      },
      error: function() {
        $('#modalglobal .modal-body').html('<p>Error al volver.</p>');
      },
      complete: function() {
        $('#loading').hide();
      }
    });
  });
  </script>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
  <body>
    <div id="loading" style="display:none">
      <img id="loading-image" src="../img/logo.png"/>
    </div>
    <div class="container-scroller"> 
      <div class="container-fluid page-body-wrapper">
            <!-- Inicia contenido principal -->
              <div class="container-fluid">
                <div class="card card-rounded">
                  <div class="card-body">
                    <h4 class="card-title">Bitácora de Actividades</h4>
                      <?php if ($res<>0){?>
                        <div style="width: 100%;">
                          <table class="table table-bordered" style="font-size:9px !important; width:100%">
                            <thead>
                              <tr>
                                <th width="150px">Fecha</th>
                                <th width="250px">Usuario</th>
                                <th style="white-space: normal;word-break: break-word;">Acción Realizada</th>
                              </tr>
                            </thead>
                            <tbody>
                              
                                <?php foreach ($res as $r){?>
                                  <tr>
                                    <td><?= $r['BITACORA_FECHA'] ?></td>
                                    <td><?= $r['USUARIO_NOMBRE'] ?></td>
                                    <td style="white-space: normal;word-break: break-word;"><?= $r['BITACORA_COMENTARIO'] ?></td>
                                  </tr>
                                <?php }?>
                              
                            </tbody>
                          </table>
                        </div>
                        <?php }else{?>
                          No se encontró información
                        <?php }?>
                    
                  </div>
                </div>
              </div>
            <!-- Finaliza contenido princial -->
          
          <?php include_once("../includes/modalglobal.php")?>
      </div>
    </div>
  </body>
</html>