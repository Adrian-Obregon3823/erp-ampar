<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Clase para enviar notificaciones WhatsApp via API Baileys (http://127.0.0.1:3000)
*********************************************************************************
*/

class whatsapp
{
    const API_URL = 'http://127.0.0.1:3000';

    /**
     * Envía un mensaje de texto por WhatsApp.
     * Si la API no está disponible o el teléfono es inválido, falla silenciosamente.
     *
     * @param string $telefono  Número en cualquier formato (se normaliza a 521XXXXXXXXXX)
     * @param string $mensaje   Texto del mensaje
     * @return bool             true si se envió, false si hubo error
     */
    public static function enviar($telefono, $mensaje)
    {
        $tel = self::normalizarTelefono($telefono);
        if (!$tel) return false;

        try {
            $payload = json_encode(['to' => $tel, 'message' => $mensaje]);
            $ch = curl_init(self::API_URL . '/send-message');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,  // 5s máximo, no bloquea el flujo
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $res = json_decode($response, true);
                return isset($res['success']) && $res['success'] === true;
            }
            error_log("[WhatsApp] Error HTTP $httpCode al enviar a $tel");
            return false;
        } catch (Throwable $e) {
            error_log("[WhatsApp] Excepción: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía mensajes a múltiples teléfonos.
     *
     * @param array  $telefonos  Array de strings con números de teléfono
     * @param string $mensaje    Texto del mensaje
     */
    public static function enviarMultiple(array $telefonos, $mensaje)
    {
        foreach ($telefonos as $tel) {
            if (!empty(trim($tel))) {
                self::enviar($tel, $mensaje);
            }
        }
    }

    /**
     * Obtiene el teléfono de un usuario por su ID.
     *
     * @param int $usuarioid
     * @return string|null  Teléfono o null si no tiene
     */
    public static function getTelefonoUsuario($usuarioid)
    {
        try {
            $db = new FirebirdConnection(true);
            $res = $db->query(
                "SELECT USUARIO_TELEFONO FROM AMPAR_CAT_USUARIOS WHERE USUARIO_ID = ?",
                [$usuarioid]
            );
            $db->close();
            if (!empty($res) && !empty(trim($res[0]['USUARIO_TELEFONO'] ?? ''))) {
                return trim($res[0]['USUARIO_TELEFONO']);
            }
        } catch (Throwable $e) {
            error_log("[WhatsApp] Error obteniendo teléfono de usuario $usuarioid: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Obtiene los teléfonos de todos los usuarios ADMIN que tengan teléfono registrado.
     *
     * @return array  Array de strings con teléfonos
     */
    public static function getTelefonosAdmins()
    {
        $telefonos = [];
        try {
            $db = new FirebirdConnection(true);
            $res = $db->query(
                "SELECT DISTINCT U.USUARIO_TELEFONO
                 FROM AMPAR_CAT_USUARIOS U
                 INNER JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID
                 INNER JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
                 WHERE UPPER(P.PERFIL_NOMBRE) CONTAINING 'ADMIN'
                   AND U.USUARIO_TELEFONO IS NOT NULL
                   AND U.USUARIO_TELEFONO <> ''
                   AND U.USUARIO_ACTIVO = 1"
            );
            $db->close();
            if ($res) {
                foreach ($res as $row) {
                    if (!empty(trim($row['USUARIO_TELEFONO'] ?? ''))) {
                        $telefonos[] = trim($row['USUARIO_TELEFONO']);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("[WhatsApp] Error obteniendo teléfonos de admins: " . $e->getMessage());
        }
        return $telefonos;
    }

    /**
     * Normaliza un número de teléfono al formato 521XXXXXXXXXX (México).
     * Acepta: 10 dígitos, +52..., 52..., 521...
     *
     * @param string $telefono
     * @return string|null  Número normalizado o null si es inválido
     */
    private static function normalizarTelefono($telefono)
    {
        if (empty($telefono)) return null;

        // Quitar todo lo que no sea dígito
        $digits = preg_replace('/\D/', '', $telefono);

        if (empty($digits)) return null;

        // Ya viene con 521 + 10 dígitos = 13 dígitos (formato correcto para Baileys)
        if (strlen($digits) === 13 && substr($digits, 0, 3) === '521') {
            return $digits; // Ya está bien: 521XXXXXXXXXX
        }
        // Viene con 52 + 10 dígitos = 12 dígitos → agregar el 1
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '52') {
            return '521' . substr($digits, 2); // 52XXXXXXXXXX → 521XXXXXXXXXX
        }
        // Solo 10 dígitos (número local México) → agregar 521
        if (strlen($digits) === 10) {
            return '521' . $digits;
        }

        error_log("[WhatsApp] Número inválido: $telefono");
        return null;
    }
}
