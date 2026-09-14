<?php
if (!function_exists('inv_dashboard_cache_ttl_seconds')) {
    function inv_dashboard_cache_ttl_seconds()
    {
        return 120;
    }
}

if (!function_exists('inv_dashboard_cache_key')) {
    function inv_dashboard_cache_key($periodoDias, $almacenId)
    {
        $periodoDias = (int)$periodoDias;
        $almacen = ($almacenId === 'global') ? 'global' : (string)((int)$almacenId);
        return 'inv_dashboard_v3_' . $periodoDias . '_' . $almacen;
    }
}

if (!function_exists('inv_dashboard_cache_get')) {
    function inv_dashboard_cache_get($cacheKey)
    {
        $now = time();

        if (function_exists('apcu_fetch')) {
            $apcuFetch = 'apcu_fetch';
            $ok = false;
            $cached = $apcuFetch($cacheKey, $ok);
            if ($ok && is_array($cached) && isset($cached['expiresAt'], $cached['data'])) {
                if ((int)$cached['expiresAt'] >= $now) {
                    return $cached['data'];
                }
                if (function_exists('apcu_delete')) {
                    $apcuDelete = 'apcu_delete';
                    $apcuDelete($cacheKey);
                }
            }
        }

        if (isset($_SESSION['ampar_dashboard_cache'][$cacheKey]) && is_array($_SESSION['ampar_dashboard_cache'][$cacheKey])) {
            $cached = $_SESSION['ampar_dashboard_cache'][$cacheKey];
            if (isset($cached['expiresAt'], $cached['data']) && (int)$cached['expiresAt'] >= $now) {
                return $cached['data'];
            }
            unset($_SESSION['ampar_dashboard_cache'][$cacheKey]);
        }

        return null;
    }
}

if (!function_exists('inv_dashboard_cache_set')) {
    function inv_dashboard_cache_set($cacheKey, $value)
    {
        $ttl = inv_dashboard_cache_ttl_seconds();
        $payload = [
            'expiresAt' => time() + $ttl,
            'data' => $value,
        ];

        if (function_exists('apcu_store')) {
            $apcuStore = 'apcu_store';
            $apcuStore($cacheKey, $payload, $ttl);
        }

        if (!isset($_SESSION['ampar_dashboard_cache']) || !is_array($_SESSION['ampar_dashboard_cache'])) {
            $_SESSION['ampar_dashboard_cache'] = [];
        }
        $_SESSION['ampar_dashboard_cache'][$cacheKey] = $payload;
    }
}

if (!function_exists('inv_dashboard_cache_delete')) {
    function inv_dashboard_cache_delete($cacheKey = null)
    {
        // Si no se proporciona key, elimina TODO el cache del dashboard
        if ($cacheKey === null) {
            // Para APCu, intentamos limpiar todos los keys del dashboard
            // APCu no soporta wildcards en delete, así que lo hacemos manualmente si es necesario
            if (function_exists('apcu_exists') && function_exists('apcu_delete')) {
                $keys = apcu_cache_info();
                if (is_array($keys) && isset($keys['cache_list'])) {
                    foreach ($keys['cache_list'] as $item) {
                        if (isset($item['key']) && strpos($item['key'], 'inv_dashboard_v') === 0) {
                            @apcu_delete($item['key']);
                        }
                    }
                }
            }

            // Limpiar sesión
            if (isset($_SESSION['ampar_dashboard_cache']) && is_array($_SESSION['ampar_dashboard_cache'])) {
                $_SESSION['ampar_dashboard_cache'] = [];
            }
        } else {
            // Elimina una key específica
            if (function_exists('apcu_delete')) {
                @apcu_delete($cacheKey);
            }
            if (isset($_SESSION['ampar_dashboard_cache'][$cacheKey])) {
                unset($_SESSION['ampar_dashboard_cache'][$cacheKey]);
            }
        }
    }
}

if (!function_exists('inv_get_dashboard_inventario_data')) {
    function inv_get_dashboard_inventario_data($db, $periodoDias, $almacenId)
    {
        $periodoDias = (int)$periodoDias;
        if ($periodoDias <= 0) {
            $periodoDias = 90;
        }

        if ($almacenId === null || $almacenId === '' || $almacenId === 'global') {
            $almacenId = 'global';
        } else {
            $almacenId = (string)((int)$almacenId);
        }

        $cacheKey = inv_dashboard_cache_key($periodoDias, $almacenId);
        $cachedViewModel = inv_dashboard_cache_get($cacheKey);
        if (is_array($cachedViewModel)) {
            return $cachedViewModel;
        }

        $dashboardInventario = new dashboardinventario();
        $dashboardData = $dashboardInventario->getDashboardData($db, (int)$periodoDias, 8, $almacenId);

        $periodoDias = (int)($dashboardData['periodoDias'] ?? 90);
        $almacenId = $dashboardData['almacenId'] ?? 'global';
        $articulosPorCaducidad = $dashboardInventario->getArticulosPorCaducidad($db, $almacenId);

        $stockRegistrado = (int)($dashboardData['stockRegistrado'] ?? 0);
        $stockFisico = (int)($dashboardData['stockFisico'] ?? 0);
        $stockTransito = (int)($dashboardData['stockTransito'] ?? 0);
        $solicitudesPeriodo = (int)($dashboardData['solicitudesPeriodo'] ?? 0);
        $solicitudesSinStock = (int)($dashboardData['solicitudesSinStock'] ?? 0);
        $unidadesObsoletas = (int)($dashboardData['unidadesObsoletas'] ?? 0);
        $articulosObsoletos = (int)($dashboardData['articulosObsoletos'] ?? 0);
        $totalArticulos = (int)($dashboardData['totalArticulos'] ?? 0);

        $etapa6m = (int)($dashboardData['etapa6m'] ?? 0);
        $etapa3m = (int)($dashboardData['etapa3m'] ?? 0);
        $etapa2m = (int)($dashboardData['etapa2m'] ?? 0);
        $etapa1s = (int)($dashboardData['etapa1s'] ?? 0);
        $caducado = (int)($dashboardData['caducado'] ?? 0);

        $tasaRotacion = (float)($dashboardData['tasaRotacion'] ?? 0);
        $diasInventario = (float)($dashboardData['diasInventario'] ?? 0);
        $precisionRegistro = (float)($dashboardData['precisionRegistro'] ?? 0);
        $roturaStock = (float)($dashboardData['roturaStock'] ?? 0);
        $topCaducidad = isset($dashboardData['topCaducidad']) && is_array($dashboardData['topCaducidad']) ? array_values($dashboardData['topCaducidad']) : [];
        $chartData = $dashboardData['chartData'] ?? [];

        $topCaducidadArticulos = count($topCaducidad);
        $topCaducidadUnidadesDia = 0;
        foreach ($topCaducidad as $rowTop) {
            $topCaducidadUnidadesDia += (int)($rowTop['CANTIDAD'] ?? 0);
        }

        $flujoEtapas = [
            [
                'etapa' => '6 meses',
                'key' => '6m',
                'conteo' => $etapa6m,
                'accion' => 'Producto entra en proceso',
                'prioridad' => 'Monitoreo',
                'clase' => 'stage-monitor',
                'expandible' => true,
            ],
            [
                'etapa' => '3 meses',
                'key' => '3m',
                'conteo' => $etapa3m,
                'accion' => 'Retorno a almacen y toma de decision: carta canje o venta',
                'prioridad' => 'Atencion',
                'clase' => 'stage-warning',
                'expandible' => true,
            ],
            [
                'etapa' => '2 meses',
                'key' => '2m',
                'conteo' => $etapa2m,
                'accion' => 'Estatus de carta canje y/o mensaje de estatus de articulo',
                'prioridad' => 'Urgente',
                'clase' => 'stage-alert',
                'expandible' => true,
            ],
            [
                'etapa' => '1 semana',
                'key' => '1s',
                'conteo' => $etapa1s,
                'accion' => 'Recolecta de articulo y/o registro de cierre de proceso',
                'prioridad' => 'Critico',
                'clase' => 'stage-critical',
                'expandible' => true,
            ],
            [
                'etapa' => 'Caducado',
                'key' => 'caducado',
                'conteo' => $caducado,
                'accion' => 'Seguimiento inmediato y disposicion final',
                'prioridad' => 'Accion inmediata',
                'clase' => 'stage-expired',
                'expandible' => true,
            ],
        ];

        $flujoTotal = 0;
        foreach ($flujoEtapas as $et) {
            $flujoTotal += (int)$et['conteo'];
        }

        $dashboardInitialPayload = [
            'periodoDias' => $periodoDias,
            'almacenId' => $almacenId,
            'chartData' => $chartData,
            'kpis' => [
                'tasaRotacion' => $tasaRotacion,
                'diasInventario' => $diasInventario,
                'precisionRegistro' => $precisionRegistro,
                'stockFisico' => $stockFisico,
                'stockRegistrado' => $stockRegistrado,
                'stockTransito' => $stockTransito,
                'unidadesObsoletas' => $unidadesObsoletas,
                'articulosObsoletos' => $articulosObsoletos,
                'roturaStock' => $roturaStock,
                'solicitudesSinStock' => $solicitudesSinStock,
                'solicitudesPeriodo' => $solicitudesPeriodo,
                'totalArticulos' => $totalArticulos,
            ],
            'flujo' => [
                '6m' => $etapa6m,
                '3m' => $etapa3m,
                '2m' => $etapa2m,
                '1s' => $etapa1s,
                'caducado' => $caducado,
            ],
            'articulosPorCaducidad' => is_array($articulosPorCaducidad) ? $articulosPorCaducidad : [],
            'topCaducidad' => $topCaducidad,
            'topCaducidadArticulos' => $topCaducidadArticulos,
            'topCaducidadUnidadesDia' => $topCaducidadUnidadesDia,
        ];

        $viewModel = [
            'periodoDias' => $periodoDias,
            'almacenId' => $almacenId,
            'stockRegistrado' => $stockRegistrado,
            'stockFisico' => $stockFisico,
            'stockTransito' => $stockTransito,
            'solicitudesPeriodo' => $solicitudesPeriodo,
            'solicitudesSinStock' => $solicitudesSinStock,
            'unidadesObsoletas' => $unidadesObsoletas,
            'articulosObsoletos' => $articulosObsoletos,
            'etapa6m' => $etapa6m,
            'etapa3m' => $etapa3m,
            'etapa2m' => $etapa2m,
            'etapa1s' => $etapa1s,
            'caducado' => $caducado,
            'tasaRotacion' => $tasaRotacion,
            'diasInventario' => $diasInventario,
            'precisionRegistro' => $precisionRegistro,
            'roturaStock' => $roturaStock,
            'topCaducidad' => $topCaducidad,
            'topCaducidadArticulos' => $topCaducidadArticulos,
            'topCaducidadUnidadesDia' => $topCaducidadUnidadesDia,
            'chartData' => $chartData,
            'articulosPorCaducidad' => is_array($articulosPorCaducidad) ? $articulosPorCaducidad : [],
            'flujoEtapas' => $flujoEtapas,
            'flujoTotal' => $flujoTotal,
            'dashboardInitialPayload' => $dashboardInitialPayload,
        ];

        inv_dashboard_cache_set($cacheKey, $viewModel);

        return $viewModel;
    }
}
