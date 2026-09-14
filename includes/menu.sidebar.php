<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php

$usuarioId = $usersesion['USUARIO_ID']; // Suponiendo que guardas el ID de usuario en sesión
$permisos = new Permisos();
$menuTree = $permisos->getMenuUsuario($usuarioId);
$permisos->renderSidebar($menuTree);

/*
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item">
      <a class="nav-link" href="../gui/dashboard.php">
        <i class="menu-icon mdi mdi-chart-line"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>
    <!-- Almacen -->
    <li class="nav-item nav-category">Almacenes</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/sucursales.php">
        <i class="menu-icon mdi mdi-home"></i>
        <span class="menu-title">Sucursales</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/almacenes.php">
        <i class="menu-icon mdi mdi-archive"></i>
        <span class="menu-title">Almacenes</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/entradasalida.php">
        <i class="menu-icon mdi mdi-qrcode"></i>
        <span class="menu-title">Entradas - Salidas</span>
      </a>
    </li>
    <!--
    <li class="nav-item">
      <a class="nav-link" href="../gui/maletas.php">
        <i class="menu-icon mdi mdi-medical-bag"></i>
        <span class="menu-title">Maletas</span>
      </a>
    </li>
    -->
    <li class="nav-item">
      <a class="nav-link" href="../gui/traspasos.php">
        <i class="menu-icon mdi mdi-arrow-right-bold"></i>
        <span class="menu-title">Traspasos</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/compras.php">
      <i class="menu-icon mdi mdi-cart"></i>
        <span class="menu-title">Compras</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/articulos.php">
      <i class="menu-icon mdi mdi-needle"></i>
        <span class="menu-title">Artículos</span>
      </a>
    </li>
    <!-- Eventos -->
    <li class="nav-item nav-category">Eventos</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/eventos.php">
      <i class="menu-icon mdi mdi-needle"></i>
        <span class="menu-title">Eventos</span>
      </a>
    </li>
    <!-- oc -->
    <li class="nav-item nav-category">Ordenes de Compra</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/oc.php">
      <i class="menu-icon mdi mdi-needle"></i>
        <span class="menu-title">OC</span>
      </a>
    </li>
    <!-- Ventas -->
    <li class="nav-item nav-category">Ventas</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/remisiones.php">
      <i class="menu-icon mdi mdi-needle"></i>
        <span class="menu-title">Remisiones</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/facturacion.php">
        <i class="menu-icon mdi mdi-home"></i>
        <span class="menu-title">Facturas</span>
      </a>
    </li>
    <!-- EPC -->
    <li class="nav-item nav-category">EPC</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/tickets.php">
      <i class="menu-icon mdi mdi-alert-circle-outline"></i>
        <span class="menu-title">Tickets</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/escaneos.php">
        <i class="menu-icon mdi mdi-barcode-scan"></i>
        <span class="menu-title">Escaneos</span>
      </a>
    </li>
    <!-- Configuración -->
    <li class="nav-item nav-category">Configuración</li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/usuarios.php">
        <i class="menu-icon mdi mdi-account-circle-outline"></i>
        <span class="menu-title">Usuarios</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/permisos.php">
        <i class="menu-icon mdi mdi-account-check"></i>
        <span class="menu-title">Permisos</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gui/catalogos.php">
        <i class="menu-icon mdi mdi-cogs"></i>
        <span class="menu-title">Más</span>
      </a>
    </li>
    <hr>
    <li class="nav-item">
      <a class="nav-link" href="../gui/bitacora.php">
        <i class="menu-icon mdi mdi-file-document"></i>
        <span class="menu-title">Bitácora de Movimientos</span>
      </a>
    </li>
  </ul>
</nav>
*/
?>