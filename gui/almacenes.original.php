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
                      <div class="col-5">
                        <?php include_once("../includes/almacenes.nuevo.php");?>
                      </div>
                      <div class="col-7">
                        <div class="card">
                          <div class="card-header">
                            <h4>Almacenes</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $almacenes = new almacenes();
                            $query = $almacenes->getqueryalmacenes();
                            $params = ['%%'];

                            // Obtener la página actual desde GET
                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $page = max(1, $page); // Evita números negativos

                            // Instancia de la conexión y la paginación
                            $conn = new FirebirdConnection();
                            $paginate = new Paginate($conn);

                            $totalRecords = $paginate->getTotalRecords($query, $params);
                            $recordsPerPage = 100; // Debe coincidir con el valor en executeQuery
                            $totalPages = ceil($totalRecords / $recordsPerPage);
                            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                          

                            // Ejecutar la consulta paginada
                            $res = $paginate->executeQuery($query, $params, $recordsPerPage, $page);
                            if ($res) {
                              echo $paginate->renderPagination($query, $params, $page, $recordsPerPage, "articulos.php");
                              ?>
                              <table class="table table-striped">
                                <thead>
                                  <tr>
                                    <th scope="col">Id</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Ciudad</th>
                                    <th scope="col" width="100px"></th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($res as $row){?>
                                  <tr>
                                    <td><?=$row['ALMACEN_ID']?></td>
                                    <td><?=$row['ALMACEN_NOMBRE']?></td>
                                    <td><?=$row['MUNICIPIO_NOMBRE']?></td>
                                    <td>
                                      <button class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Información Almacén" data-url="../includes/almacenes.info.php?almacenid=<?=base64_encode($row['ALMACEN_ID'])?>" aria-selected="false"><i class="menu-icon mdi mdi-information-outline"></i></button>
                                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Editar Almacén" data-url="../includes/almacenes.editar.php?almacenid=<?=base64_encode($row['ALMACEN_ID'])?>" aria-selected="false"><i class="menu-icon mdi mdi-lead-pencil"></i></button>
                                      <button class="btn btn-danger btn-sm" onclick="desactivar('<?=$row['ALMACEN_ID']?>');"><i class="menu-icon mdi mdi-delete-forever"></i></button>
                                    </td>
                                  </tr>
                                  <?php } ?>
                                </tbody>
                              </table>
                              <?php
                              echo $paginate->renderPagination($query, $params, $page, $recordsPerPage, "articulos.php"); 
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
		var url = button.data('url'); // Obtén la URL del atributo data-url
		var title = button.data('title'); // Obtén el título del atributo data-title

		// Cambia el título del encabezado del modal
		var modal = $(this);
		modal.find('.modal-title').text(title); // Actualiza el título en el header del modal

		// Realiza la solicitud AJAX para cargar el contenido de la URL en el modal
		$.ajax({
			url: url,
			type: 'GET',
			success: function(response) {
				modal.find('.modal-body').html(response); // Inserta el contenido en el modal
			},
			error: function() {
				modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
			}
		});
	});

  function desactivar(id){
    if (confirm('¿Seguro que deseas desactivar almacén?')){
      alert ("Eliminar");
    }else{
      return false;
    }
  }

</script>