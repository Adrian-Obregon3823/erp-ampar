<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Clase para gestionar notificaciones internas (eventos – invitaciones)
*********************************************************************************
*/

class notificaciones
{

    /**
     * Inserta una notificación para un usuario.
     * Tipos sugeridos: 'ESPECIALISTA' | 'CHOFER'
     */
    static function crear($usuarioid, $eventoid, $tipo, $titulo, $mensaje)
    {
        $db = new FirebirdConnection();
        try {
            $db->execute(
                "INSERT INTO AMPAR_HIS_NOTIFICACIONES
                    (NOTIF_USUARIOID, NOTIF_EVENTOID, NOTIF_TIPO, NOTIF_TITULO, NOTIF_MENSAJE, NOTIF_LEIDA, NOTIF_FECHA)
                 VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)",
                [$usuarioid, $eventoid, $tipo, $titulo, $mensaje]
            );
            $db->commit();
        } catch (Throwable $e) {
            error_log("[notificaciones::crear] ERROR: " . $e->getMessage());
            try {
                $db->rollback();
            } catch (Throwable $e2) {
            }
        }
        $db->close();
    }

    /**
     * Devuelve las notificaciones no leídas del usuario.
     */
    static function getPendientes($usuarioid)
    {
        $db = new FirebirdConnection(true);
        $rows = [];
        try {
            $rows = $db->query(
                "SELECT
                    N.NOTIF_ID, N.NOTIF_USUARIOID, N.NOTIF_EVENTOID, N.NOTIF_TIPO,
                    N.NOTIF_TITULO, N.NOTIF_MENSAJE, N.NOTIF_LEIDA,
                    N.NOTIF_FECHA, N.NOTIF_RESPUESTA,
                    E.EVENTO_FOLIO
                 FROM AMPAR_HIS_NOTIFICACIONES N
                 LEFT JOIN AMPAR_HIS_EVENTOS E ON E.EVENTO_ID = N.NOTIF_EVENTOID
                 WHERE N.NOTIF_USUARIOID = ? AND N.NOTIF_LEIDA = 0
                 ORDER BY N.NOTIF_FECHA DESC",
                [$usuarioid]
            );
            if (!$rows) $rows = [];
            
            // ===== NOTIFICACIONES VIRTUALES PARA ADMINS =====
            $esAdmin = false;
            $sqlPerfiles = "
                SELECT P.PERFIL_NOMBRE 
                FROM AMPAR_CAT_USUARIOSPERFILES UP
                JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
                WHERE UP.USUARIOP_USUARIOID = $usuarioid
            ";
            $resPerfiles = $db->query($sqlPerfiles);
            if ($resPerfiles) {
                foreach ($resPerfiles as $p) {
                    $pUpper = strtoupper(trim($p['PERFIL_NOMBRE']));
                    if (strpos($pUpper, 'ADMIN') !== false) {
                        $esAdmin = true;
                    }
                    if (strpos($pUpper, 'ALMACEN') !== false) {
                        $esAlmacenista = true;
                    }
                }
            }
            
            if ($esAdmin) {
                // Entradas en status 8
                $entradas = $db->query("SELECT ES_ID, ES_FOLIO, ES_ALMACENID FROM AMPAR_HIS_ES WHERE ES_STATUS = 8 AND ES_TIPO = 'E'");
                if ($entradas) {
                    foreach ($entradas as $e) {
                        $rows[] = [
                            'NOTIF_ID' => 'VE_' . $e['ES_ID'],
                            'NOTIF_USUARIOID' => $usuarioid,
                            'NOTIF_EVENTOID' => $e['ES_ID'],
                            'NOTIF_TIPO' => 'VIRTUAL_ENTRADA',
                            'NOTIF_TITULO' => 'Entrada por Autorizar',
                            'NOTIF_MENSAJE' => 'La entrada ' . $e['ES_FOLIO'] . ' requiere revisión y autorización.',
                            'NOTIF_LEIDA' => 0,
                            'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                            'NOTIF_EXTRA' => $e['ES_ALMACENID']
                        ];
                    }
                }
                
                // Traspasos en status 8
                $traspasos = $db->query("SELECT TRASPASO_ID, TRASPASO_FOLIO FROM AMPAR_HIS_TRASPASO WHERE TRASPASO_STATUS = 8");
                if ($traspasos) {
                    foreach ($traspasos as $t) {
                        $rows[] = [
                            'NOTIF_ID' => 'VT_' . $t['TRASPASO_ID'],
                            'NOTIF_USUARIOID' => $usuarioid,
                            'NOTIF_EVENTOID' => $t['TRASPASO_ID'],
                            'NOTIF_TIPO' => 'VIRTUAL_TRASPASO',
                            'NOTIF_TITULO' => 'Traspaso por Autorizar',
                            'NOTIF_MENSAJE' => 'El traspaso ' . $t['TRASPASO_FOLIO'] . ' requiere revisión.',
                            'NOTIF_LEIDA' => 0,
                            'NOTIF_FECHA' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }

            if (isset($esAlmacenista) && $esAlmacenista) {
                if (!class_exists('actividades')) {
                    require_once __DIR__ . '/actividades.php';
                }
                $acts = new actividades();
                
                // 1. Escaneo Maletas
                $escM = $acts->getEscaneoStatus($usuarioid);
                if (!$escM['completado'] && (int)date('H') >= 15 && $escM['total_maletas'] > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_ESCM',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Escaneo Diario Pendiente',
                        'NOTIF_MENSAJE' => 'Tienes pendiente el escaneo diario de maletas (' . $escM['escaneos_hoy'] . '/' . $escM['total_maletas'] . ').',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'MALETAS'
                    ];
                }

                // 2. Escaneo Almacenes
                $escA = $acts->getEscaneoAlmacenesStatus($usuarioid);
                if (!$escA['completado'] && (int)date('H') >= 15 && $escA['total_almacenes'] > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_ESCA',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Escaneo Diario Pendiente',
                        'NOTIF_MENSAJE' => 'Tienes pendiente el escaneo diario de almacenes (' . $escA['escaneos_hoy'] . '/' . $escA['total_almacenes'] . ').',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'ALMACENES'
                    ];
                }

                // 3. Recepciones
                $recepMercancia = $acts->getRecepcionesPendientes();
                $recepTraspasos = $acts->getRecepcionesTraspasosPendientes($usuarioid);
                $recep = $recepMercancia + $recepTraspasos;
                if ($recep > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_REC',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Recepciones Pendientes',
                        'NOTIF_MENSAJE' => 'Hay ' . $recep . ' recepción(es) esperando entrada a almacén.',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'RECEPCIONES'
                    ];
                }

                // 4. Entradas
                $entr = $acts->getEntradasPendientesAlmacenista();
                if ($entr > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_ENT',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Entradas Pendientes',
                        'NOTIF_MENSAJE' => 'Tienes ' . $entr . ' entrada(s) pendiente(s) de procesar.',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'ENTRADAS'
                    ];
                }

                // 5. Salidas
                $sal = $acts->getSalidasPendientesAlmacenista();
                if ($sal > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_SAL',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Salidas Pendientes',
                        'NOTIF_MENSAJE' => 'Tienes ' . $sal . ' salida(s) pendiente(s) de procesar.',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'SALIDAS'
                    ];
                }

                // 6. Traspasos
                $tras = $acts->getTraspasosPendientesAlmacenista($usuarioid);
                if ($tras > 0) {
                    $rows[] = [
                        'NOTIF_ID' => 'VA_TRA',
                        'NOTIF_USUARIOID' => $usuarioid,
                        'NOTIF_EVENTOID' => 0,
                        'NOTIF_TIPO' => 'VIRTUAL_ALMACENISTA',
                        'NOTIF_TITULO' => 'Traspasos Pendientes',
                        'NOTIF_MENSAJE' => 'Tienes ' . $tras . ' traspaso(s) pendiente(s) de procesar.',
                        'NOTIF_LEIDA' => 0,
                        'NOTIF_FECHA' => date('Y-m-d H:i:s'),
                        'NOTIF_EXTRA' => 'TRASPASOS'
                    ];
                }
            }

            $db->close();
            return $rows;
        } catch (Throwable $e) {
            $db->close();
            return $rows;
        }
    }

    /**
     * Marca una notificación como leída (sin respuesta).
     */
    static function marcarLeida($notifid, $usuarioid)
    {
        $db = new FirebirdConnection();
        try {
            $db->execute(
                "UPDATE AMPAR_HIS_NOTIFICACIONES
                 SET NOTIF_LEIDA = 1
                 WHERE NOTIF_ID = ? AND NOTIF_USUARIOID = ?",
                [$notifid, $usuarioid]
            );
            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $e2) {
            }
        }
        $db->close();
    }

    /**
     * Responde a una invitación de evento (ACEPTADO / RECHAZADO).
     * Si acepta, actualiza el status del evento a Confirmado (19).
     */
    static function responder($notifid, $usuarioid, $respuesta, $eventoid, $tipo)
    {
        if (!in_array($respuesta, ['ACEPTADO', 'RECHAZADO'])) return false;

        $db = new FirebirdConnection();
        try {
            $db->execute(
                "UPDATE AMPAR_HIS_NOTIFICACIONES
                 SET NOTIF_LEIDA = 1, NOTIF_RESPUESTA = ?, NOTIF_FECHARESPUESTA = CURRENT_TIMESTAMP
                 WHERE NOTIF_ID = ? AND NOTIF_USUARIOID = ?",
                [$respuesta, $notifid, $usuarioid]
            );

            if ($respuesta === 'ACEPTADO') {
                if ($tipo === 'ESPECIALISTA') {
                    $db->execute(
                        "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ESPECIALISTAIDSTATUS = 19 WHERE EVENTO_ID = ?",
                        [$eventoid]
                    );
                } elseif ($tipo === 'CHOFER') {
                    $db->execute(
                        "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_CHOFERIDSTATUS = 19 WHERE EVENTO_ID = ?",
                        [$eventoid]
                    );
                }
            }

            $db->commit();
            $db->close();
            return true;
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $e2) {
            }
            $db->close();
            return false;
        }
    }
}
