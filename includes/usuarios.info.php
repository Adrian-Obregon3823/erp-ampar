<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Datos Generales</h4>
        </div>
        <div class="card-body">
            <?php
            $login = new login();
            $res = $login->getusuariobyid(base64_decode($_GET['usuarioid']));
            if ($res == 0){
                echo "No se encontró información";
            }else{
                ?>
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start">
                    <div class="mr-md-4 mb-3 mb-md-0 text-center text-md-left">
                        <img class="img-md rounded-circle" src="<?=$res['usuario']['USUARIO_RUTAIMAGEN']?>" alt="Profile image" width="200px" height="200px" style="object-fit: cover;">
                    </div>
                    <div class="text-center text-md-left pt-md-2">
                        ALIAS: <b><?= $res['usuario']['USUARIO_ALIAS'] ?? $res['usuario']['USUARIO_NOMBRE'] ?></b><br>
                        NOMBRE: <b><?= $res['usuario']['USUARIO_NOMBRE'] ?></b><br>
                    </div>
                </div>
                <br><br><h4>Almacenes</h4>
                <?php
                if (!empty($res['almacenes'])){
                    echo "<ul>";
                    foreach ($res['almacenes'] as $r) {
                        echo "<li>" . htmlspecialchars($r['ALMACEN_NOMBRE']) . "</li>";
                    }
                    echo "</ul>";
                }else{
                    echo "No tiene almacenes asignados";
                }
                ?>
                <br><br><h4>Perfiles</h4>
                <?php
                if (!empty($res['perfiles'])){
                    echo "<ul>";
                    foreach ($res['perfiles'] as $r) {
                        echo "<li>" . htmlspecialchars($r['PERFIL_NOMBRE'] ?? 'Perfil ID: ' . $r['PERFIL_ID']) . "</li>";
                    }
                    echo "</ul>";
                }else{
                    echo "No tiene perfiles asignados";
                }
                ?>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>
<iframe id="iframeDescarga" style="display: none;"></iframe>
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

    function descargarExcel(almacenid) {
        document.getElementById('loading').style.display = 'block';

        // Abrimos la descarga en un iframe para no perder la vista actual
        document.getElementById('iframeDescarga').src = "../includes/almacenes.info.exportarexcel.php?almacenid=" + almacenid;

        // Opcional: quitar el loading después de unos segundos aunque no sepamos si terminó
        setTimeout(function() {
            document.getElementById('loading').style.display = 'none';
        }, 5000); // ajusta el tiempo si tarda más
    }

</script>