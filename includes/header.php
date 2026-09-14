<?php
// Poor man's cron para verificación de actividades diarias.
if (!class_exists('actividades')) {
    @include_once(__DIR__ . '/../class/actividades.php');
}
if (class_exists('actividades')) {
    $act = new actividades();
    $act->autoVerificarActividades();
    $act->autoEnviarRecordatorios();
}

$notificacionesPendientes = [];
if (class_exists('notificaciones') && !empty($usersesion['USUARIO_ID'])) {
    $notificacionesPendientes = notificaciones::getPendientes($usersesion['USUARIO_ID']);
}
$totalNotificaciones = is_array($notificacionesPendientes) ? count($notificacionesPendientes) : 0;
?>
<style>
    @media (min-width: 768px) {
        #countDropdown+.dropdown-menu.navbar-dropdown {
            min-width: 400px !important;
            max-width: 440px !important;
            width: 400px !important;
        }
    }
    #countDropdown+.dropdown-menu.navbar-dropdown {
        border-radius: 16px;
        box-shadow: 0 16px 48px -12px rgba(0,0,0,0.18) !important;
        border: 1px solid rgba(0,0,0,0.06);
        padding: 0;
        margin-top: 12px;
    }
    .dropdown-menu-header-notif {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 24px;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }
    #notif-list {
        max-height: 440px;
        overflow-y: auto;
        padding: 12px 0 16px 0;
        background: #f8fafc;
    }
    #notif-list::-webkit-scrollbar {
        width: 6px;
    }
    #notif-list::-webkit-scrollbar-track {
        background: transparent; 
    }
    #notif-list::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 10px;
    }
    #notif-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
    #notif-list .dropdown-item.preview-item {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        margin: 0 16px 12px 16px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0 !important; 
        border-left: 4px solid #cbd5e1; /* default left border */
        width: auto;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        padding: 16px !important;
        background-color: #ffffff !important;
    }
    #notif-list .dropdown-item.preview-item:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0,0,0,0.08);
    }
    #notif-list .dropdown-item.preview-item .btn {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    #notif-list .dropdown-item.preview-item:hover .btn {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    /* Estilos extra para botones de las cards */
    #notif-list .btn {
        border-radius: 24px !important;
        padding: 6px 16px !important;
        font-weight: 600 !important;
    }
    #notif-badge {
        position: absolute !important;
        width: auto !important;
        height: auto !important;
        min-width: 18px;
        min-height: 18px;
        padding: 0 4px !important;
        border-radius: 12px !important;
        font-size: 0.6rem !important;
        font-weight: 700;
        line-height: 1 !important;
        text-align: center;
        color: #fff !important;
        background-color: #ef4444 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        top: 4px !important;
        right: 4px !important;
        border: 2px solid #fff;
        transform: translate(50%, -50%);
    }
</style>
<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                <span class="icon-menu"></span>
            </button>
        </div>
        <div>
            <a class="navbar-brand brand-logo" href="../gui/dashboard.php">
                <img src="../img/logo2.png">
            </a>
        </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-top">
        <ul class="navbar-nav">
            <li class="nav-item">
                <img src="../img/logo.png" width="130px" class="d-lg-none">
                <img src="../img/logo.png" width="200px" class="d-none d-lg-block">
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                <h1 class="welcome-text">Hola, <span class="text-black fw-bold"><?= $usersesion['USUARIO_ALIAS'] ?></span></h1>
                <!--<h3 class="welcome-sub-text"><?= util::formatoFechaEspanol(date("Y-m-d H:i:s")); ?></h3>-->
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator" id="countDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="icon-bell"></i>
                    <span class="count" id="notif-badge" style="display:<?= $totalNotificaciones > 0 ? 'flex' : 'none' ?> !important;"><?= $totalNotificaciones > 5 ? '5+' : ($totalNotificaciones > 0 ? $totalNotificaciones : '') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="countDropdown">
                    <div class="dropdown-menu-header-notif d-flex justify-content-between align-items-center">
                        <p class="mb-0 font-weight-bold" style="font-size: 1.05rem; color: #0f172a;">Notificaciones</p>
                        <span class="badge badge-pill float-right" id="notif-count-text" style="color: #ffffff !important; background-color: #3b82f6 !important; font-weight: 700; padding: 6px 12px; font-size: 0.75rem; border-radius: 20px; box-shadow: 0 2px 4px rgba(59,130,246,0.3);"><?= $totalNotificaciones > 99 ? '99+' : ($totalNotificaciones > 0 ? $totalNotificaciones : '') ?></span>
                    </div>
                    <div id="notif-list">
                        <a class="dropdown-item py-3 text-muted <?= $totalNotificaciones > 0 ? 'd-none' : '' ?>" id="notif-empty" style="display:<?= $totalNotificaciones > 0 ? 'none !important' : 'block' ?>;">Sin notificaciones pendientes</a>
                        <?php foreach ($notificacionesPendientes as $n) { ?>
                            <?php
                            if ($n['NOTIF_TIPO'] === 'ENTRADA') {
                                $tipo = 'Entrada de recepción rechazada';
                                $color = 'danger';
                                $icon = 'alert-circle';
                            } else {
                                $tipo = ($n['NOTIF_TIPO'] === 'ESPECIALISTA') ? 'Especialista' : 'Chofer';
                                $color = ($n['NOTIF_TIPO'] === 'ESPECIALISTA') ? 'primary' : 'warning';
                                $icon = 'bell-ring';
                            }
                            $titulo = !empty($n['NOTIF_TITULO']) ? $n['NOTIF_TITULO'] : 'Invitacion de evento';
                            $mensaje = !empty($n['NOTIF_MENSAJE']) ? $n['NOTIF_MENSAJE'] : '';
                            ?>
                            <?php if ($n['NOTIF_TIPO'] === 'ENTRADA') { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <i class="mdi mdi-alert-circle text-danger" style="font-size: 1.8rem; line-height: 1;"></i>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Entrada de recepción rechazada</h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="verNotifEntrada(<?= (int)$n['NOTIF_EVENTOID'] ?>, <?= (int)$n['NOTIF_ID'] ?>)" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else if ($n['NOTIF_TIPO'] === 'RECEPCION_RECHAZADA') { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <i class="mdi mdi-alert-circle text-danger" style="font-size: 1.8rem; line-height: 1;"></i>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Entrada de recepción rechazada</h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="verNotifRecepcionRechazada(<?= (int)$n['NOTIF_EVENTOID'] ?>, <?= (int)$n['NOTIF_ID'] ?>)" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else if ($n['NOTIF_TIPO'] === 'RECEPCION_OC') { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <i class="mdi mdi-package-variant-closed text-success" style="font-size: 1.8rem; line-height: 1;"></i>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Mercancía de OC recepcionada</h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-outline-success btn-sm py-1 px-3" onclick="verNotifRecepcionOC(<?= (int)$n['NOTIF_EVENTOID'] ?>, <?= (int)$n['NOTIF_ID'] ?>)" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else if ($n['NOTIF_TIPO'] === 'RECEPCION_TRASPASO') { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <i class="mdi mdi-truck-check text-info" style="font-size: 1.8rem; line-height: 1;"></i>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Traspaso Recepcionado</h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-outline-info btn-sm py-1 px-3" onclick="verNotifRecepcionTraspaso(<?= (int)$n['NOTIF_EVENTOID'] ?>, <?= (int)$n['NOTIF_ID'] ?>)" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-check mr-1"></i>Entendido</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else if ($n['NOTIF_TIPO'] === 'SISTEMA') { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: 4px solid #f43f5e;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <div style="background: #ffe4e6; color: #e11d48; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                            <i class="mdi mdi-alert-decagram text-danger" style="font-size: 1.5rem; line-height: 1;"></i>
                                        </div>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">Alerta del Sistema</h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #e11d48;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-sm py-1 px-3" onclick="verNotifSistema(<?= (int)$n['NOTIF_ID'] ?>, '<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>')" style="background: #ffe4e6; color: #e11d48; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="mdi mdi-eye mr-1"></i>Ver</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else if ($n['NOTIF_TIPO'] === 'VIRTUAL_ALMACENISTA') { 
                                $btnStyle = 'background: #e0f2fe; color: #0284c7; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                                $iconHtml = '<i class="mdi mdi-information-outline" style="font-size: 1.5rem; line-height: 1;"></i>';
                                $iconWrapStyle = 'background: #e0f2fe; color: #0284c7; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                                $borderLeft = '4px solid #0ea5e9';
                                
                                $extra = $n['NOTIF_EXTRA'] ?? '';
                                if ($extra === 'ENTRADAS' || $extra === 'RECEPCIONES') {
                                    $borderLeft = '4px solid #f59e0b';
                                    $iconWrapStyle = 'background: #fef3c7; color: #d97706; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                                    $iconHtml = '<i class="mdi mdi-arrow-down-box" style="font-size: 1.5rem; line-height: 1;"></i>';
                                    $btnStyle = 'background: #fef3c7; color: #d97706; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                                } else if ($extra === 'SALIDAS' || $extra === 'TRASPASOS') {
                                    $borderLeft = '4px solid #ef4444';
                                    $iconWrapStyle = 'background: #fee2e2; color: #b91c1c; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                                    $iconHtml = '<i class="mdi mdi-arrow-up-box" style="font-size: 1.5rem; line-height: 1;"></i>';
                                    $btnStyle = 'background: #fee2e2; color: #b91c1c; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                                } else {
                                    $iconHtml = '<i class="mdi mdi-radar" style="font-size: 1.5rem; line-height: 1;"></i>';
                                }
                            ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: <?= $borderLeft ?>;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <div style="<?= $iconWrapStyle ?>"><?= $iconHtml ?></div>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h6>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-sm py-1 px-3" onclick="window.location.href='../gui/actividades.php'" style="<?= $btnStyle ?>"><i class="mdi mdi-eye mr-1"></i>Ir al Dashboard</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-<?= (int)$n['NOTIF_ID'] ?>" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">
                                    <div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">
                                        <i class="mdi mdi-<?= $icon ?> text-<?= $color ?>" style="font-size: 1.8rem; line-height: 1;"></i>
                                    </div>
                                    <div class="preview-item-content" style="flex-grow: 1; width: 100%;">
                                        <h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;"><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></h6>
                                        <p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #0d6efd;"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div>
                                            <button class="btn btn-success btn-sm mr-1 py-1 px-3" onclick="responderNotif(<?= (int)$n['NOTIF_ID'] ?>,<?= (int)$n['NOTIF_EVENTOID'] ?>,'<?= htmlspecialchars($n['NOTIF_TIPO'], ENT_QUOTES, 'UTF-8') ?>','ACEPTADO')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-check mr-1"></i>Aceptar</button>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="responderNotif(<?= (int)$n['NOTIF_ID'] ?>,<?= (int)$n['NOTIF_EVENTOID'] ?>,'<?= htmlspecialchars($n['NOTIF_TIPO'], ENT_QUOTES, 'UTF-8') ?>','RECHAZADO')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-close mr-1"></i>Rechazar</button>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <img class="img-xs rounded-circle" src="<?= $usersesion['USUARIO_RUTAIMAGEN'] ?>" alt="Profile image"> </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                    <div class="dropdown-header text-center">
                        <img class="img-md rounded-circle" src="<?= $usersesion['USUARIO_RUTAIMAGEN'] ?>" alt="Profile image" width="60px" height="60px">
                        <p class="mb-1 mt-3 font-weight-semibold"><?= $usersesion['USUARIO_ALIAS'] ?></p>
                        <p class="fw-light text-muted mb-0"><?= $usersesion['USUARIO_CORREO'] ?></p>
                    </div>
                    <a class="dropdown-item" href="../includes/cerrarsesion.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Salir</a>
                </div>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>

<script>
    (function() {
        var POLL_INTERVAL = 30000; // 30 segundos
        <?php
        $docRoot  = realpath($_SERVER['DOCUMENT_ROOT']);
        $ajaxDir  = realpath(__DIR__ . '/../ajax');
        $ajaxBase = '/' . ltrim(str_replace('\\', '/', substr($ajaxDir, strlen($docRoot))), '/');
        ?>
        var _ajaxBase = '<?= $ajaxBase ?>';

        function getById(id) {
            return document.getElementById(id);
        }

        function postForm(url, data) {
            var body = new URLSearchParams();
            Object.keys(data).forEach(function(key) {
                body.append(key, data[key]);
            });

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString(),
                credentials: 'same-origin'
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function updateNotifCounter(total) {
            var badge = getById('notif-badge');
            var count = getById('notif-count-text');
            var empty = getById('notif-empty');

            if (!badge || !count || !empty) {
                return;
            }

            if (!total) {
                badge.style.setProperty('display', 'none', 'important');
                badge.textContent = '';
                count.textContent = '';
                empty.style.setProperty('display', 'block', 'important');
                empty.classList.remove('d-none');
                return;
            }

            badge.style.setProperty('display', 'flex', 'important');
            badge.textContent = total > 5 ? '5+' : String(total);
            count.textContent = total > 99 ? '99+' : String(total);
            empty.style.setProperty('display', 'none', 'important');
            empty.classList.add('d-none');
        }

        function renderNotif(n) {
            var tipo = (n.NOTIF_TIPO === 'ESPECIALISTA') ? 'Especialista' : 'Chofer';
            var color = (n.NOTIF_TIPO === 'ESPECIALISTA') ? 'primary' : 'warning';
            var icon = 'bell-ring';
            var titulo = n.NOTIF_TITULO || 'Invitacion de evento';
            var mensaje = n.NOTIF_MENSAJE || '';

            if (n.NOTIF_TIPO === 'ENTRADA') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<i class="mdi mdi-alert-circle text-danger" style="font-size: 1.8rem; line-height: 1;"></i>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Entrada de recepción rechazada</h6>' +
                    '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;">' + escapeHtml(titulo) + '</p>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="verNotifEntrada(' + (n.NOTIF_EVENTOID || 0) + ', ' + n.NOTIF_ID + ')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'RECEPCION_RECHAZADA') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<i class="mdi mdi-alert-circle text-danger" style="font-size: 1.8rem; line-height: 1;"></i>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Entrada de recepción rechazada</h6>' +
                    '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;">' + escapeHtml(titulo) + '</p>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="verNotifRecepcionRechazada(' + (n.NOTIF_EVENTOID || 0) + ', ' + n.NOTIF_ID + ')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'RECEPCION_OC') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<i class="mdi mdi-package-variant-closed text-success" style="font-size: 1.8rem; line-height: 1;"></i>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">Mercancía de OC recepcionada</h6>' +
                    '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;">' + escapeHtml(titulo) + '</p>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-outline-success btn-sm py-1 px-3" onclick="verNotifRecepcionOC(' + (n.NOTIF_EVENTOID || 0) + ', ' + n.NOTIF_ID + ')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver detalle</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'RECEPCION_TRASPASO') {
                var isDisputa = (titulo === 'Disputa Resuelta');
                var headerText = isDisputa ? 'Disputa Resuelta' : 'Traspaso Recepcionado';
                var iconClass = isDisputa ? 'mdi-gavel text-danger' : 'mdi-truck-check text-info';
                var btnHtml = isDisputa ? 
                    '<button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="verNotifDisputa(' + (n.NOTIF_EVENTOID || 0) + ', ' + n.NOTIF_ID + ')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-eye mr-1"></i>Ver resolución</button>'
                    : '<button class="btn btn-outline-info btn-sm py-1 px-3" onclick="verNotifRecepcionTraspaso(' + (n.NOTIF_EVENTOID || 0) + ', ' + n.NOTIF_ID + ')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-check mr-1"></i>Entendido</button>';

                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<i class="mdi ' + iconClass + '" style="font-size: 1.8rem; line-height: 1;"></i>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">' + headerText + '</h6>' +
                    '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #212529;">' + (!isDisputa ? escapeHtml(titulo) : '') + '</p>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    btnHtml +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'SISTEMA') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: 4px solid #f43f5e;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<div style="background: #ffe4e6; color: #e11d48; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="mdi mdi-alert-decagram text-danger" style="font-size: 1.5rem; line-height: 1;"></i>' +
                    '</div>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">Alerta del Sistema</h6>' +
                    '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #e11d48;">' + escapeHtml(titulo) + '</p>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-sm py-1 px-3" onclick="verNotifSistema(' + n.NOTIF_ID + ', \'' + escapeHtml(titulo).replace(/\'/g, "\\\'") + '\', \'' + escapeHtml(mensaje).replace(/\'/g, "\\\'") + '\')" style="background: #ffe4e6; color: #e11d48; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="mdi mdi-eye mr-1"></i>Ver</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'REVISION_PRECIO') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: 4px solid #f43f5e;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<div style="background: #ffe4e6; color: #e11d48; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="mdi mdi-alert-decagram text-danger" style="font-size: 1.5rem; line-height: 1;"></i>' +
                    '</div>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">' + escapeHtml(titulo) + '</h6>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-sm py-1 px-3" onclick="window.location.href=\'../gui/articulos_proveedor.php?artid=' + n.NOTIF_EVENTOID + '\'" style="background: #ffe4e6; color: #e11d48; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="mdi mdi-currency-usd mr-1"></i>Revisar Precio</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'VIRTUAL_ENTRADA') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: 4px solid #f59e0b;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<div style="background: #fef3c7; color: #d97706; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="mdi mdi-clipboard-check-outline" style="font-size: 1.5rem; line-height: 1;"></i>' +
                    '</div>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">' + escapeHtml(titulo) + '</h6>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-sm py-1 px-3" onclick="window.location.href=\'../gui/entradasalida.php?autoopen=' + n.NOTIF_EVENTOID + '&tipo=E&almacenid=' + (n.NOTIF_EXTRA || '') + '\'" style="background: #fef3c7; color: #d97706; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="mdi mdi-eye mr-1"></i>Revisar Entrada</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'VIRTUAL_TRASPASO') {
                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: 4px solid #ef4444;">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<div style="background: #fee2e2; color: #b91c1c; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="mdi mdi-swap-horizontal" style="font-size: 1.5rem; line-height: 1;"></i>' +
                    '</div>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">' + escapeHtml(titulo) + '</h6>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-sm py-1 px-3" onclick="window.location.href=\'../gui/traspasos.php?autoopen=' + n.NOTIF_EVENTOID + '\'" style="background: #fee2e2; color: #b91c1c; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="mdi mdi-eye mr-1"></i>Revisar Traspaso</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            if (n.NOTIF_TIPO === 'VIRTUAL_ALMACENISTA') {
                var btnStyle = 'background: #e0f2fe; color: #0284c7; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                var iconHtml = '<i class="mdi mdi-information-outline" style="font-size: 1.5rem; line-height: 1;"></i>';
                var iconWrapStyle = 'background: #e0f2fe; color: #0284c7; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                var borderLeft = '4px solid #0ea5e9';

                if (n.NOTIF_EXTRA === 'ENTRADAS' || n.NOTIF_EXTRA === 'RECEPCIONES') {
                    borderLeft = '4px solid #f59e0b';
                    iconWrapStyle = 'background: #fef3c7; color: #d97706; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                    iconHtml = '<i class="mdi mdi-arrow-down-box" style="font-size: 1.5rem; line-height: 1;"></i>';
                    btnStyle = 'background: #fef3c7; color: #d97706; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                } else if (n.NOTIF_EXTRA === 'SALIDAS' || n.NOTIF_EXTRA === 'TRASPASOS') {
                    borderLeft = '4px solid #ef4444';
                    iconWrapStyle = 'background: #fee2e2; color: #b91c1c; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;';
                    iconHtml = '<i class="mdi mdi-arrow-up-box" style="font-size: 1.5rem; line-height: 1;"></i>';
                    btnStyle = 'background: #fee2e2; color: #b91c1c; border: none; border-radius: 20px; font-size: 0.75rem; font-weight: 700;';
                } else {
                    iconHtml = '<i class="mdi mdi-radar" style="font-size: 1.5rem; line-height: 1;"></i>';
                }

                return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff; border-left: ' + borderLeft + ';">' +
                    '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                    '<div style="' + iconWrapStyle + '">' + iconHtml + '</div>' +
                    '</div>' +
                    '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                    '<h6 class="preview-subject font-weight-bold mb-1" style="font-size: 0.88rem; color: #1e293b;">' + escapeHtml(titulo) + '</h6>' +
                    '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                    '<div>' +
                    '<button class="btn btn-sm py-1 px-3" onclick="window.location.href=\'../gui/actividades.php\'" style="' + btnStyle + '"><i class="mdi mdi-eye mr-1"></i>Ir al Dashboard</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }

            return '<div class="dropdown-item preview-item py-3 px-3 border-bottom" id="notif-item-' + n.NOTIF_ID + '" style="white-space: normal; line-height: 1.4; background-color: #ffffff;">' +
                '<div class="preview-thumbnail mr-3 d-flex align-items-start pt-1">' +
                '<i class="mdi mdi-' + icon + ' text-' + color + '" style="font-size: 1.8rem; line-height: 1;"></i>' +
                '</div>' +
                '<div class="preview-item-content" style="flex-grow: 1; width: 100%;">' +
                '<h6 class="preview-subject font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">' + escapeHtml(tipo) + '</h6>' +
                '<p class="font-weight-bold mb-1" style="font-size: 0.8rem; color: #0d6efd;">' + escapeHtml(titulo) + '</p>' +
                '<p class="text-muted mb-2" style="font-size: 0.78rem; line-height: 1.35;">' + escapeHtml(mensaje) + '</p>' +
                '<div>' +
                '<button class="btn btn-success btn-sm mr-1 py-1 px-3" onclick="responderNotif(' + n.NOTIF_ID + ',' + n.NOTIF_EVENTOID + ',\'' + n.NOTIF_TIPO + '\',\'ACEPTADO\')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-check mr-1"></i>Aceptar</button>' +
                '<button class="btn btn-outline-danger btn-sm py-1 px-3" onclick="responderNotif(' + n.NOTIF_ID + ',' + n.NOTIF_EVENTOID + ',\'' + n.NOTIF_TIPO + '\',\'RECHAZADO\')" style="font-size: 0.75rem; border-radius: 4px; font-weight: 600;"><i class="mdi mdi-close mr-1"></i>Rechazar</button>' +
                '</div>' +
                '</div>' +
                '</div>';
        }

        function cargarNotificaciones() {
            var list = getById('notif-list');
            if (!list) {
                return;
            }

            fetch(_ajaxBase + '/notificaciones.get.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    var items = list.querySelectorAll('[id^="notif-item-"]');
                    items.forEach(function(item) {
                        item.remove();
                    });

                    if (!Array.isArray(data) || data.length === 0) {
                        updateNotifCounter(0);
                        return;
                    }

                    updateNotifCounter(data.length);
                    data.forEach(function(n) {
                        list.insertAdjacentHTML('beforeend', renderNotif(n));
                    });
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al cargar:', error);
                });
        }

        window.responderNotif = function(notifid, eventoid, tipo, respuesta) {
            postForm(_ajaxBase + '/notificaciones.responder.php', {
                    notifid: notifid,
                    eventoid: eventoid,
                    tipo: tipo,
                    respuesta: respuesta
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function() {
                    var label = respuesta === 'ACEPTADO' ? 'aceptada' : 'rechazada';
                    var icon = respuesta === 'ACEPTADO' ? 'success' : 'info';
                    var item = getById('notif-item-' + notifid);
                    if (item) {
                        item.remove();
                    }

                    var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                    updateNotifCounter(remaining);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            text: 'Invitacion ' + label + '.',
                            icon: icon,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al responder:', error);
                });
        };

        window.verNotifRecepcionRechazada = function(recepcionid, notifid) {
            postForm(_ajaxBase + '/notificaciones.responder.php', {
                    notifid: notifid,
                    eventoid: recepcionid || 0,
                    tipo: 'RECEPCION_RECHAZADA',
                    respuesta: 'LEIDO'
                })
                .then(function() {
                    var item = getById('notif-item-' + notifid);
                    if (item) {
                        item.remove();
                    }
                    var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                    updateNotifCounter(remaining);
                    
                    window.location.href = '../gui/recepcionesmercancia.php?autoedit=' + btoa(String(recepcionid));
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al ver recepcion rechazada:', error);
                });
        };

        window.verNotifRecepcionOC = function(recepcionid, notifid) {
            postForm(_ajaxBase + '/notificaciones.responder.php', {
                    notifid: notifid,
                    eventoid: recepcionid || 0,
                    tipo: 'RECEPCION_OC',
                    respuesta: 'LEIDO'
                })
                .then(function() {
                    var item = getById('notif-item-' + notifid);
                    if (item) {
                        item.remove();
                    }
                    var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                    updateNotifCounter(remaining);

                    if (recepcionid && typeof $ !== 'undefined' && $('#modalglobal').length) {
                        var targetUrl = '../includes/recepcionesmercancia.info.php?id=' + btoa(String(recepcionid));
                        var btn = $('<a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Recepción REC-' + recepcionid + '" data-url="' + targetUrl + '" style="display:none;"></a>');
                        $('body').append(btn);
                        btn.trigger('click');
                        setTimeout(function() {
                            btn.remove();
                        }, 500);
                    }
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al ver recepcion OC:', error);
                });
        };

        window.verNotifRecepcionTraspaso = function(traspasoid, notifid) {
            postForm(_ajaxBase + '/notificaciones.responder.php', {
                    notifid: notifid,
                    eventoid: traspasoid || 0,
                    tipo: 'RECEPCION_TRASPASO',
                    respuesta: 'LEIDO'
                })
                .then(function() {
                    var item = getById('notif-item-' + notifid);
                    if (item) {
                        item.remove();
                    }
                    var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                    updateNotifCounter(remaining);
                    window.location.href = '../gui/actividades.php';
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al responder:', error);
                    window.location.href = '../gui/actividades.php';
                });
        };

        window.verNotifRecepcionTraspaso = function(eventoid, notifid) {
            postForm(_ajaxBase + '/notificaciones.marcarleida.php', {
                notifid: notifid
            }).then(function() {
                window.location.href = '../gui/traspasos.php';
            }).catch(function(e) {
                console.error(e);
                window.location.href = '../gui/traspasos.php';
            });
        };

        window.verNotifDisputa = function(eventoid, notifid) {
            postForm(_ajaxBase + '/notificaciones.marcarleida.php', {
                notifid: notifid
            }).then(function() {
                window.location.href = '../gui/disputas.php?autoopen=' + encodeURIComponent(eventoid);
            }).catch(function(e) {
                console.error(e);
                window.location.href = '../gui/disputas.php?autoopen=' + encodeURIComponent(eventoid);
            });
        };

        window.verNotifSistema = function(notifid, titulo, mensaje) {
            postForm(_ajaxBase + '/notificaciones.marcarleida.php', {
                notifid: notifid
            }).then(function() {
                var item = getById('notif-item-' + notifid);
                if (item) {
                    item.remove();
                }
                var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                updateNotifCounter(remaining);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: titulo,
                        text: mensaje,
                        icon: 'info',
                        confirmButtonText: 'Cerrar'
                    });
                }
            }).catch(function(e) {
                console.error(e);
            });
        };

        window.verNotifEntrada = function(eventoid, notifid) {
            postForm(_ajaxBase + '/notificaciones.responder.php', {
                    notifid: notifid,
                    eventoid: eventoid || 0,
                    tipo: 'ENTRADA',
                    respuesta: 'LEIDO'
                })
                .then(function() {
                    var item = getById('notif-item-' + notifid);
                    if (item) {
                        item.remove();
                    }
                    var remaining = document.querySelectorAll('#notif-list [id^="notif-item-"]').length;
                    updateNotifCounter(remaining);

                    if (eventoid && typeof $ !== 'undefined' && $('#modalglobal').length) {
                        var targetUrl = '../includes/entradasalida.info.php?esid=' + btoa(String(eventoid));
                        var btn = $('<a data-toggle="modal" data-target="#modalglobal" data-title="Información de Solicitud de Entradas/Salidas" data-url="' + targetUrl + '" style="display:none;"></a>');
                        $('body').append(btn);
                        btn.trigger('click');
                        setTimeout(function() {
                            btn.remove();
                        }, 500);
                    }
                })
                .catch(function(error) {
                    console.error('[Notificaciones] Error al ver entrada:', error);
                });
        };

        document.addEventListener('DOMContentLoaded', function() {
            cargarNotificaciones();
            setInterval(cargarNotificaciones, POLL_INTERVAL);
        });
    }());
</script>

<script>
    // Fix global: limpiar backdrop del modal al cerrarse en cualquier página
    $(document).on('hidden.bs.modal', '#modalglobal', function() {
        $(this).find('.modal-body').html('');
        $(this).find('.modal-dialog').removeClass('modal-xl modal-lg');
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal-backdrop').remove();
    });

    // Fix: manejar apertura dinámica del modal global con data-url
    $(document).on('click', '[data-target="#modalglobal"][data-url]', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // Evitar que Bootstrap también dispare show() y duplique el backdrop

        // Limpiar cualquier estado sobrante antes de abrir
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal-backdrop').remove();

        var url   = $(this).data('url');
        var title = $(this).data('title') || '';
        if (!url) return;
        var modal = $('#modalglobal');
        modal.find('.modal-title').text(title);
        modal.find('.modal-dialog').addClass('modal-xl');
        modal.find('.modal-body').html('<div class="text-center py-5"><i class="mdi mdi-spin mdi-loading" style="font-size:2rem;"></i></div>');
        modal.modal('show');
        $('#loading').show();
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) { modal.find('.modal-body').html(response); },
            error:   function() { modal.find('.modal-body').html('<p class="text-danger p-3">Error al cargar el contenido.</p>'); },
            complete: function() { $('#loading').hide(); }
        });
    });
</script>