# 🔒 Solución Mixed Content - HTTPS → HTTP Local

## 🎯 El Problema

Tu aplicación web usa **HTTPS** (SSL/TLS) con dominio seguro, pero necesita comunicarse con una **API local en HTTP** (impresora en `http://localhost:8090` o IP local).

Los navegadores modernos **bloquean** estas peticiones por seguridad:
```
Mixed Content: The page was loaded over HTTPS, but requested an insecure resource 'http://localhost:8090/imprimir'. 
This request has been blocked; the content must be served over HTTPS.
```

## ✅ La Solución: Proxy PHP

En lugar de hacer la petición directamente desde JavaScript (bloqueada), usamos un **proxy PHP intermedio**:

```
Frontend (HTTPS)  →  Proxy PHP (HTTPS)  →  API Local (HTTP)
     ✅                    ✅                    ✅
```

### Flujo de Funcionamiento

1. **Frontend** (JavaScript en HTTPS) llama al proxy PHP en tu servidor HTTPS
2. **Proxy PHP** hace la petición HTTP a la API local desde el servidor
3. **Proxy PHP** devuelve la respuesta al frontend por HTTPS

**¿Por qué funciona?** 
- El navegador solo ve comunicación HTTPS → HTTPS ✅
- La petición HTTP la hace el servidor PHP, no el navegador
- Los servidores no tienen restricciones de Mixed Content

## 📁 Archivos Involucrados

### 1. Configuración: `config/printer.config.php`

Define la IP/puerto de la API de impresión:

```php
<?php
return [
    'printer_api' => [
        'host' => 'localhost',  // O IP: '192.168.1.100'
        'port' => '8090',
        'timeout' => 10,
    ],
];
```

**Para múltiples ubicaciones:**
```php
'printer_locations' => [
    'almacen_1' => ['host' => 'localhost', 'port' => '8090'],
    'almacen_2' => ['host' => '192.168.1.101', 'port' => '8090'],
],
```

### 2. Proxy: `ajax/proxy.imprimir.php`

Intermediario que hace la petición HTTP por ti:

```php
<?php
// Recibe petición HTTPS del frontend
$data = json_decode(file_get_contents('php://input'), true);

// Hace petición HTTP a la API local
$ch = curl_init('http://localhost:8090/imprimir');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);

// Devuelve respuesta al frontend por HTTPS
echo $response;
```

### 3. Frontend: `includes/entradasalida.impresionetiquetas.php`

Llama al proxy (no directamente a la API):

```javascript
// ❌ NO HACER (bloqueado por Mixed Content)
fetch('http://localhost:8090/imprimir', {...})

// ✅ HACER (a través del proxy)
fetch('../ajax/proxy.imprimir.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({folio: '12345'})
})
```

## 🚀 Uso

### Impresión Simple

```javascript
fetch('../ajax/proxy.imprimir.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        folio: 'FOLIO-001'
    })
})
.then(res => res.json())
.then(data => console.log('Impreso!', data));
```

### Impresión Múltiple

```javascript
fetch('../ajax/proxy.imprimir.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        folios: ['FOLIO-001', 'FOLIO-002', 'FOLIO-003']
    })
})
.then(res => res.json())
.then(data => console.log('Impresos!', data));
```

### Health Check

```javascript
fetch('../ajax/proxy.imprimir.php?action=health')
    .then(res => res.json())
    .then(data => console.log('API disponible:', data));
```

## ⚙️ Configuración por Ubicación

Si tienes múltiples almacenes con diferentes servidores de impresión:

### 1. Actualizar `printer.config.php`

```php
'printer_locations' => [
    'almacen_principal' => [
        'host' => 'localhost',
        'port' => '8090'
    ],
    'almacen_norte' => [
        'host' => '192.168.1.101',
        'port' => '8090'
    ],
    'almacen_sur' => [
        'host' => '192.168.1.102',
        'port' => '8090'
    ],
],
```

### 2. Pasar ubicación en la petición

```javascript
fetch('../ajax/proxy.imprimir.php', {
    method: 'POST',
    body: JSON.stringify({
        folio: 'FOLIO-001',
        location: 'almacen_norte'  // Opcional
    })
})
```

### 3. Modificar proxy para usar ubicación

```php
// En proxy.imprimir.php
if (isset($data['location']) && isset($config['printer_locations'][$data['location']])) {
    $loc = $config['printer_locations'][$data['location']];
    $apiUrl = 'http://' . $loc['host'] . ':' . $loc['port'];
}
```

## 🔧 Troubleshooting

### Error: "No se pudo conectar al servidor"

**Causa:** La API local no está corriendo o la IP/puerto son incorrectos

**Solución:**
1. Verifica que la API esté corriendo: `netstat -an | findstr :8090`
2. Verifica la IP en `printer.config.php`
3. Prueba con: `curl http://localhost:8090/health`

### Error: "Timeout"

**Causa:** La API está lenta o no responde

**Solución:**
1. Aumenta el timeout en `printer.config.php`: `'timeout' => 30`
2. Verifica logs de la API local
3. Verifica que no haya firewall bloqueando

### Error: "Mixed Content" todavía aparece

**Causa:** Estás llamando directamente a la API HTTP, no al proxy

**Solución:**
Asegúrate de llamar al proxy:
```javascript
// ✅ Correcto
fetch('../ajax/proxy.imprimir.php', {...})

// ❌ Incorrecto
fetch('http://localhost:8090/imprimir', {...})
```

### La API local funciona, pero el proxy no

**Causa:** Problema de red o permisos en el servidor

**Solución:**
1. Activa debug en `printer.config.php`: `'debug' => true`
2. Revisa logs de PHP: `tail -f /var/log/apache2/error.log`
3. Verifica que cURL esté instalado: `php -m | grep curl`

### Error: "CORS"

**Causa:** Problemas de CORS entre dominios

**Solución:**
El proxy ya incluye headers CORS:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
```

Si necesitas restringir, cambia `*` por tu dominio:
```php
header('Access-Control-Allow-Origin: https://tudominio.com');
```

## 🔒 Seguridad

### Validación de Origen

Para evitar que otros sitios usen tu proxy:

```php
// En proxy.imprimir.php, al inicio
$allowedOrigins = [
    'https://tudominio.com',
    'https://www.tudominio.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!in_array($origin, $allowedOrigins)) {
    http_response_code(403);
    echo json_encode(['error' => 'Origen no autorizado']);
    exit;
}
```

### Autenticación

Si necesitas autenticación adicional:

```php
// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}
```

### Rate Limiting

Para evitar abuso:

```php
// Limitar a 10 peticiones por minuto
$ip = $_SERVER['REMOTE_ADDR'];
$cacheKey = "print_limit_$ip";
$count = apcu_fetch($cacheKey) ?: 0;

if ($count > 10) {
    http_response_code(429);
    echo json_encode(['error' => 'Demasiadas peticiones']);
    exit;
}

apcu_store($cacheKey, $count + 1, 60);
```

## 📊 Monitoreo

### Logs de Debug

Activa debug en `printer.config.php`:
```php
'debug' => true,
```

Verás logs en `error.log`:
```
[PROXY IMPRIMIR] Request recibido: {"folio":"FOLIO-001"}
[PROXY IMPRIMIR] Haciendo petición a: http://localhost:8090/imprimir
[PROXY IMPRIMIR] Respuesta recibida: {"http_code":200}
```

### Dashboard de Monitoreo (Opcional)

Crea `ajax/proxy.stats.php`:

```php
<?php
// Contador de peticiones
$statsFile = __DIR__ . '/../cache/proxy_stats.json';
$stats = json_decode(file_get_contents($statsFile), true) ?: [
    'total' => 0,
    'success' => 0,
    'errors' => 0,
    'last_print' => null
];

echo json_encode($stats);
```

## 🌐 Alternativas (No Recomendadas)

### 1. Certificado SSL en la API Local

**Pros:** Elimina el problema
**Contras:** 
- Complejo de configurar
- Requiere certificado válido
- Los navegadores suelen no confiar en certificados locales

### 2. Excepciones en Navegador

**Pros:** Rápido para desarrollo
**Contras:**
- No funciona en producción
- Cada usuario debe configurar su navegador
- Inseguro

### 3. WebSocket Seguro (WSS)

**Pros:** Bidireccional
**Contras:**
- Más complejo que HTTP
- Requiere servidor WebSocket con SSL

## ✅ Mejores Prácticas

1. **Siempre usa el proxy** en producción con HTTPS
2. **Configura timeouts apropiados** según tu red
3. **Maneja errores claramente** para los usuarios
4. **Activa logs en desarrollo**, desactiva en producción
5. **Valida todos los inputs** antes de enviar a la API
6. **Implementa rate limiting** para evitar abuso
7. **Documenta las IPs** de cada ubicación

## 📚 Referencias

- [MDN: Mixed Content](https://developer.mozilla.org/en-US/docs/Web/Security/Mixed_content)
- [OWASP: HTTPS Best Practices](https://owasp.org/www-project-web-security-testing-guide/)
- [PHP cURL Documentation](https://www.php.net/manual/en/book.curl.php)

---

**✅ Configuración implementada correctamente**  
Tu aplicación ahora puede usar HTTPS mientras se comunica con APIs locales HTTP de forma segura.
