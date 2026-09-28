<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$usuarioid = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
$perfilid  = $_SESSION['ampar']['usuario']['USUARIO_PERFILID'] ?? 0;

$db = new FirebirdConnection();

$isAdmin = $GLOBALS['isAdmin'] ?? false;

// Administrador ve todo, otros solo ven las suyas
$filtroUser = $isAdmin ? "" : " AND (D.DISPUTA_USUARIO_CREADOR = $usuarioid OR D.DISPUTA_USUARIO_DESTINO = $usuarioid) ";

$sql = "
    SELECT D.*, 
           T.TRASPASO_FOLIO,
           U1.USUARIO_NOMBRE as NOMBRE_CREADOR,
           U2.USUARIO_NOMBRE as NOMBRE_DESTINO
    FROM AMPAR_DISPUTAS D
    LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = D.DISPUTA_TRASPASOID
    LEFT JOIN AMPAR_CAT_USUARIOS U1 ON U1.USUARIO_ID = D.DISPUTA_USUARIO_CREADOR
    LEFT JOIN AMPAR_CAT_USUARIOS U2 ON U2.USUARIO_ID = D.DISPUTA_USUARIO_DESTINO
    WHERE 1=1 $filtroUser
    ORDER BY D.DISPUTA_STATUS ASC, D.DISPUTA_ID DESC
";

$disputas = $db->query($sql);
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Disputas de Traspasos - AMPAR</title>
    <?php include_once("../includes/head.php"); ?>
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge-status-1 { background-color: #f59e0b; color: white; }
        .badge-status-2 { background-color: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include_once("../includes/header.php"); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once("../includes/menu.sidebar.php"); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <?php include_once("../includes/traspasos.menu.php"); ?>
                    <div class="row mt-3">
                        <div class="col-md-12 grid-margin">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="font-weight-bold mb-0">Disputas de Traspasos</h4>
                                    <p class="text-muted mb-0">Administración y revisión de incidencias</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Folio Traspaso</th>
                                                    <th>Motivo</th>
                                                    <th>Iniciada por</th>
                                                    <th>Destino original</th>
                                                    <th>Fecha</th>
                                                    <th>Estatus</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if($disputas <> 0) { foreach ($disputas as $d) { ?>
                                                    <tr>
                                                        <td><strong><?= $d['TRASPASO_FOLIO'] ?></strong></td>
                                                        <td><?= htmlspecialchars($d['DISPUTA_MOTIVO']) ?></td>
                                                        <td><?= $d['NOMBRE_CREADOR'] ?></td>
                                                        <td><?= $d['NOMBRE_DESTINO'] ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($d['DISPUTA_FECHA'])) ?></td>
                                                        <td>
                                                            <?php if($d['DISPUTA_STATUS'] == 1) { ?>
                                                                <span class="badge badge-status-1">En Revisión</span>
                                                            <?php } else { ?>
                                                                <span class="badge badge-status-2">Resuelta</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info" onclick="verDisputa(<?= $d['DISPUTA_ID'] ?>)">
                                                                <i class="mdi mdi-eye"></i> <?= ($isAdmin && $d['DISPUTA_STATUS'] == 1) ? 'Resolver' : 'Ver Detalles' ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php } } else { ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted py-4">No hay disputas registradas.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once("../includes/footer.php"); ?>
            </div>
        </div>
    </div>
    
    <?php include_once("../includes/modalglobal.php"); ?>
    <?php include_once("../includes/foot.php"); ?>
    
    <script>
        function verDisputa(id) {
            $('#modalglobal .modal-content').html('<div class="modal-body text-center py-5"><i class="mdi mdi-spin mdi-loading" style="font-size: 3rem;"></i><p class="mt-2">Cargando detalles...</p></div>');
            $('#modalglobal').modal('show');
            $('#modalglobal .modal-content').load('../includes/disputa.info.php?id=' + id);
        }

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('autoopen')) {
                const autoopenId = urlParams.get('autoopen');
                if (autoopenId && !isNaN(autoopenId)) {
                    verDisputa(autoopenId);
                }
            }
        });
    </script>
</body>
</html>
