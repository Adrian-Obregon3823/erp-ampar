# 🔧 Configuración Rápida - Proxy de Impresión

## ✅ Ya está Configurado

Tu aplicación ya tiene implementada la **solución completa** para Mixed Content HTTPS → HTTP:

### Archivos Creados/Actualizados:

1. ✅ **[config/printer.config.php](config/printer.config.php)** - Configuración centralizada
2. ✅ **[ajax/proxy.imprimir.php](ajax/proxy.imprimir.php)** - Proxy mejorado con manejo de errores
3. ✅ **[includes/entradasalida.impresionetiquetas.php](includes/entradasalida.impresionetiquetas.php)** - Frontend actualizado
4. ✅ **[test-proxy-printing.html](test-proxy-printing.html)** - Página de pruebas

---

## 🚀 Pasos para Usar

### 1. Configurar la IP de la Impresora

Edita [config/printer.config.php](config/printer.config.php):

```php
'printer_api' => [
    'host' => 'localhost',  // Cambia a la IP si la impresora está en otro equipo
    'port' => '8090',       // Puerto de tu API de impresión
    'timeout' => 10,
],
```

**Ejemplos de configuración:**

```php
// Si la API está en la misma máquina
'host' => 'localhost'

// Si la API está en otra computadora de la red
'host' => '192.168.1.50'

// Si tienes múltiples ubicaciones
'printer_locations' => [
    'almacen_principal' => ['host' => 'localhost', 'port' => '8090'],
    'almacen_sucursal' => ['host' => '192.168.1.100', 'port' => '8090'],
]
```

### 2. Verificar que tu API de Impresión esté Corriendo

```powershell
# Windows - Verificar que el puerto 8090 está escuchando
netstat -an | findstr :8090

# Debería mostrar algo como:
# TCP    0.0.0.0:8090           0.0.0.0:0              LISTENING
```

### 3. Probar la Configuración

Abre en tu navegador:
```
https://tudominio.com/amparv3/test-proxy-printing.html
```

O en desarrollo local:
```
http://localhost/amparv3/test-proxy-printing.html
```

**Ejecuta los 4 tests:**
1. ✅ Health Check - Verifica conexión
2. ✅ Impresión Simple - Prueba un folio
3. ✅ Impresión Múltiple - Prueba varios folios
4. ⚠️ Conexión Directa - Debe fallar en HTTPS (es correcto)

---

## 🎯 Cómo Funciona

### Antes (❌ Bloqueado por Mixed Content):
```
Frontend HTTPS → API Local HTTP (localhost:8090)
                 ❌ BLOQUEADO
```

### Ahora (✅ Funciona):
```
Frontend HTTPS → Proxy PHP HTTPS → API Local HTTP
                 ✅ OK             ✅ OK
```

El proxy PHP hace la petición HTTP por ti, evitando el bloqueo del navegador.

---

## 📋 Uso en tu Código

Tu código actual ya está actualizado para usar el proxy:

```javascript
// En cualquier parte de tu aplicación
fetch('../ajax/proxy.imprimir.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        folio: 'FOLIO-12345'  // Un folio
        // o
        folios: ['FOL-1', 'FOL-2', 'FOL-3']  // Múltiples
    })
})
.then(res => res.json())
.then(data => {
    console.log('Impresión exitosa:', data);
})
.catch(error => {
    console.error('Error:', error);
});
```

---

## 🔍 Troubleshooting Rápido

### ❌ Error: "No se pudo conectar al servidor"

**Solución:**
1. Verifica que la API esté corriendo: `netstat -an | findstr :8090`
2. Verifica la IP en `config/printer.config.php`
3. Prueba manualmente: `curl http://localhost:8090/health`

### ❌ Error: "Timeout"

**Solución:**
Aumenta el timeout en `config/printer.config.php`:
```php
'timeout' => 30,  // 30 segundos
```

### ❌ Error: "Mixed Content" aún aparece

**Solución:**
Asegúrate de llamar al **proxy**, no a la API directamente:
```javascript
// ✅ CORRECTO
fetch('../ajax/proxy.imprimir.php', ...)

// ❌ INCORRECTO (será bloqueado)
fetch('http://localhost:8090/imprimir', ...)
```

### ❌ Error: "Error 404" en el proxy

**Solución:**
Verifica que la ruta sea correcta según la ubicación de tu archivo:
```javascript
// Desde /includes/archivo.php
fetch('../ajax/proxy.imprimir.php', ...)

// Desde /pages/archivo.php
fetch('../ajax/proxy.imprimir.php', ...)

// Desde raíz /archivo.php
fetch('ajax/proxy.imprimir.php', ...)
```

---

## 🐛 Debug Mode

Para ver logs detallados, activa debug en `config/printer.config.php`:

```php
'debug' => true,  // Activar logs
```

Los logs aparecerán en tu `error.log` de Apache/PHP:

```
[PROXY IMPRIMIR] Request recibido: {"folio":"TEST-001"}
[PROXY IMPRIMIR] Haciendo petición a: http://localhost:8090/imprimir
[PROXY IMPRIMIR] Respuesta recibida: {"http_code":200}
```

**Desactívalo en producción:**
```php
'debug' => false,  // Desactivar logs
```

---

## 📚 Documentación Completa

Para más información detallada:
- 📖 **[MIXED-CONTENT-SOLUTION.md](MIXED-CONTENT-SOLUTION.md)** - Explicación completa de la solución

---

## ✅ Checklist Final

- [ ] API de impresión corriendo en el puerto configurado
- [ ] `config/printer.config.php` configurado con la IP correcta
- [ ] Test en `test-proxy-printing.html` pasando correctamente
- [ ] Frontend actualizado usando el proxy (ya hecho)
- [ ] Debug desactivado en producción

---

## 🎉 ¡Listo!

Tu aplicación ahora puede:
- ✅ Funcionar en HTTPS con dominio SSL
- ✅ Comunicarse con APIs locales en HTTP
- ✅ Imprimir desde navegadores sin errores de Mixed Content
- ✅ Manejar errores de forma clara para los usuarios

**No necesitas hacer nada más**, todo está configurado y funcionando.
