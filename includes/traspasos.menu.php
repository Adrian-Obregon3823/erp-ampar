<div class="d-sm-flex align-items-center justify-content-between border-bottom">
    <ul class="nav nav-tabs">
        <?php if (basename($_SERVER['PHP_SELF']) != 'disputas.php') { ?>
        <li class="nav-item">
            <a class="nav-link" id="home-tab" data-toggle="modal" data-target="#modalglobal" data-title="Traspaso de Almacén" style="cursor:pointer" data-url="../includes/traspasos.nuevo.php" aria-selected="false">Nuevo</a>
        </li>
        <?php } ?>
        <li class="nav-item">
            <a class="nav-link" href="../gui/disputas.php" <?= basename($_SERVER['PHP_SELF']) == 'disputas.php' ? 'style="border-bottom: 2px solid #007bff; color: #007bff;"' : '' ?>>Disputas</a>
        </li>
        <!--
        <li class="nav-item">
        <a class="nav-link d-flex align-items-center" id="home-tab" data-toggle="modal" data-target="#modalglobal" data-title="Traspaso de Almacén" style="cursor:pointer" data-url="../includes/traspasos.scan.php" aria-selected="false">
            <img src="../img/scan.png" height="30px">
        </a>
        </li>
        -->
    </ul>
</div>