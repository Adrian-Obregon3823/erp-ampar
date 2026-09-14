<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="es">
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
            <!-- Inicia contenido principal -->
            <?php include_once("../includes/inventario.almacen.main.php");?>
            <!-- Finaliza contenido princial -->
          </div>
          <?php include_once("../includes/footer.php");?>
        </div>
      </div>
    </div>
    <?php include_once("../includes/foot.php");?>
  </body>
</html>
