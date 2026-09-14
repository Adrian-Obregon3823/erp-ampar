<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Información de Almacén</h4>
        </div>
        <br>
        <a href="../includes/almacen.info.exportarexcel.php?almacenid=<?= $_GET['almacenid'] ?>">
            <img src="../img/excel.png" width="50px">
        </a>

        <div class="card-body">
            <?php
            $almacenes = new almacenesv2();
            $res = $almacenes->getarticulosalmacenbyalmacenid(base64_decode($_GET['almacenid']));
            if ($res == 0){
                echo "No se encntró información";
            }else{
                $agrupado = [];
                $grantotal = 0;
                foreach ($res as $r) {
                    $id = $r['ARTICULO_ID'];
                    if (!isset($agrupado[$id])) {
                        $agrupado[$id] = [
                            'nombre' => $r['NOMBRE'],
                            'items' => [],
                            'total' => 0
                        ];
                    }
                    $agrupado[$id]['items'][] = $r;
                    $agrupado[$id]['total'] += 1;
                    $grantotal+= 1;
                }       
                echo "<h2>Total:".$grantotal."</h2><br>";  
                ?>
                <div class="accordion" id="accordionArticulos">
                    <?php foreach ($agrupado as $id => $grupo): 
                        $collapseId = "collapse$id";
                        $headingId = "heading$id";
                    ?>
                        <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= $headingId ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div><?= $grupo['nombre'] ?></div>
                                <div><strong><?= $grupo['total'] ?></strong></div>
                            </div>
                        </button>
                        </h2>
                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>">
                            <div class="accordion-body">
                            <table class="table table-striped">
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Clave</th>
                                    <th>Lote</th>
                                    <th>Caducidad</th>
                                    <th>Serie</th>
                                </tr>
                                <?php foreach ($grupo['items'] as $item): ?>
                                <tr>
                                    <?php $articuloId = 'A' . str_pad($item['INVENTARIODET_ID'], 6, '0', STR_PAD_LEFT);?>
                                    <td><?= $articuloId ?></td>
                                    <td><?= $item['INVENTARIO_FECHA'] ?></td>
                                    <td><?= $item['CLAVE_ARTICULO'] ?></td>
                                    <td><?= $item['INVENTARIODET_LOTE'] ?></td>
                                    <td><?= $item['INVENTARIODET_CADUCIDAD'] ?></td>
                                    <td><?= $item['INVENTARIODET_SERIE'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            </div>
                        </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.accordion-button').forEach(button => {
    const targetSelector = button.getAttribute('data-bs-target');
    const target = document.querySelector(targetSelector);

    button.addEventListener('click', function (e) {
      // Verificar si ya está abierto
      if (target.classList.contains('show')) {
        e.preventDefault(); // evita que se abra de nuevo
        const instance = bootstrap.Collapse.getOrCreateInstance(target);
        instance.hide(); // lo cierra
      }
      // Si está cerrado, Bootstrap lo abrirá automáticamente
    });
  });
});
</script>