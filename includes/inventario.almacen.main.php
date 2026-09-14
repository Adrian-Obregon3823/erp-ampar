<div class="row justify-content-center mt-4">

    <!-- Dashboard Global -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='inventarioglobal.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper mb-3" style="background-color: rgba(63, 81, 181, 0.12);">
                    <i class="mdi mdi-view-dashboard" style="color: #3F51B5;"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Almacenes</h5>
                <p class="text-muted small mb-0">Visualización de todo el inventario</p>
            </div>
        </div>
    </div>

    <!-- Maletas -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='maletas.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-info mb-3">
                    <i class="mdi mdi-medical-bag text-info"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Maletas</h5>
                <p class="text-muted small mb-0">Gestión de maletas y catálogos</p>
            </div>
        </div>
    </div>

    <!-- Equipo Capital -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='equipocapital.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-teal mb-3">
                    <i class="mdi mdi-cube-outline text-teal"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Equipo Capital</h5>
                <p class="text-muted small mb-0">Gestión de equipo capital</p>
            </div>
        </div>
    </div>

    <!-- Requerimientos de Material -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='../gui/requerimientosmaterial.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-primary mb-3">
                    <i class="mdi mdi-clipboard-text text-primary"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Requerimientos de Material</h5>
                <p class="text-muted small mb-0">Solicitud de material por almacén</p>
            </div>
        </div>
    </div>

    <!-- Entradas y Salidas -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='entradasalida.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-success mb-3">
                    <i class="mdi mdi-qrcode text-success"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Entradas - Salidas</h5>
                <p class="text-muted small mb-0">Gestione flujos de inventario</p>
            </div>
        </div>
    </div>

    <!-- Traspasos -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='traspasos.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-primary mb-3">
                    <i class="mdi mdi-arrow-right-bold text-primary"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Traspasos</h5>
                <p class="text-muted small mb-0">Movimientos entre sucursales</p>
            </div>
        </div>
    </div>

    <!-- Recepciones de Mercancía -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='recepcionesmercancia.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-warning mb-3">
                    <i class="mdi mdi-truck-check text-warning"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Recepciones</h5>
                <p class="text-muted small mb-0">Recepción de órdenes de compra</p>
            </div>
        </div>
    </div>

    <!-- Escaneos -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='escaneos.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-purple mb-3">
                    <i class="mdi mdi-barcode-scan text-purple"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Escaneos</h5>
                <p class="text-muted small mb-0">Escaneo de Maletas</p>
            </div>
        </div>
    </div>

    <!-- Incidencias -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card module-card shadow-sm border-0" onclick="window.location.href='tickets.php';">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <div class="icon-wrapper bg-soft-warning mb-3">
                    <i class="mdi mdi-alert-circle-outline text-warning"></i>
                </div>
                <h5 class="card-title font-weight-bold text-dark mb-1">Incidencias</h5>
                <p class="text-muted small mb-0">Reporte problemas detectados</p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos modernos para los módulos */
    .module-card {
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        background: #ffffff;
        overflow: hidden;
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .module-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: transparent;
        transition: background 0.3s ease;
    }

    .module-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1) !important;
    }

    .icon-wrapper {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .module-card:hover .icon-wrapper {
        transform: scale(1.15) rotate(5deg);
    }

    .icon-wrapper i {
        font-size: 32px;
    }

    /* Colores suaves de fondo para iconos */
    .bg-soft-success {
        background-color: rgba(76, 175, 80, 0.12);
    }

    .bg-soft-danger {
        background-color: rgba(244, 67, 54, 0.12);
    }

    .bg-soft-primary {
        background-color: rgba(33, 150, 243, 0.12);
    }

    .bg-soft-warning {
        background-color: rgba(255, 152, 0, 0.12);
    }

    .bg-soft-purple {
        background-color: rgba(156, 39, 176, 0.12);
    }

    .bg-soft-info {
        background-color: rgba(0, 188, 212, 0.12);
    }

    .bg-soft-teal {
        background-color: rgba(13, 148, 136, 0.12);
    }

    /* Textos adaptados para visibilidad */
    .text-purple {
        color: #9C27B0 !important;
    }

    .text-teal {
        color: #0d9488 !important;
    }

    /* Letra y titulo */
    .text-dark {
        color: #333333 !important;
    }
</style>