<?php
include_once("../config/variables.php");
date_default_timezone_set($GLOBALS['date_default_timezone_set']);
include_once("../class/util.php");
//include_once('../lib/phpqrcode/qrlib.php');
include_once("../class/bdfirebird.php");
include_once("../class/permisos.php");
include_once("../class/bitacora.php");
include_once("../class/sucursales.php");
include_once("../class/compras.php");
include_once("../class/almacenes.php");
include_once("../class/entradasalida.php");
include_once("../class/traspasos.php");
include_once("../class/maletas.php");
include_once("../class/requerimientosmaterial.php");
include_once("../class/articulos.php");
include_once("../class/eventos.php");
include_once("../class/proyectos.php");
include_once("../class/remisiones.php");
include_once("../class/oc.php");
include_once("../class/mail.php");
include_once("../class/paginacion.php");
include_once("../class/login.php");
include_once("../class/bitacora.php");
include_once("../class/rfid.php");
include_once("../class/tickets.php");
include_once("../class/notificaciones.php");
include_once("../class/whatsapp.php");
include_once("../class/dashboardinventario.php");
include_once("../class/recepcionesmercancia.php");
include_once("../class/actividades.php");

// Calcular si el usuario en sesión es administrador global
if (isset($_SESSION['ampar']['usuario']['USUARIO_ID'])) {
    $dbAdminCheck = new FirebirdConnection();
    $uid = (int)$_SESSION['ampar']['usuario']['USUARIO_ID'];
    
    $sqlPerfil = "SELECT 1 FROM AMPAR_CAT_USUARIOSPERFILES WHERE USUARIOP_USUARIOID = $uid AND USUARIOP_PERFILID = 1";
    $sqlTipo = "SELECT 1 FROM AMPAR_CAT_USUARIOSTIPOPERMISOS WHERE USUARIOSTIPOPERMISOS_USUARIOID = $uid AND USUARIOSTIPOPERMISOS_TIPOID = 1";
    
    $isP = count($dbAdminCheck->query($sqlPerfil)) > 0;
    $isT = count($dbAdminCheck->query($sqlTipo)) > 0;
    
    if ($isP && $isT) {
        $GLOBALS['isAdmin'] = true;
    }
    $dbAdminCheck->close();
}

include_once("../class/equipocapital.php");
?>