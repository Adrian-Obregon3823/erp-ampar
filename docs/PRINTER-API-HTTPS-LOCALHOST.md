# 🖨️ Solución: API de Impresión Local en HTTPS

## ⚠️ Problema: Mixed Content Block

Cuando tu sitio usa **HTTPS** (https://erp.ampardemexico.com) e intenta conectarse a una API local en **HTTP** (http://localhost:8090), el navegador bloquea la conexión por seguridad (Mixed Content).

### ❌ Por qué el Proxy PHP NO funciona

```
Cliente (Navegador)
    ↓ HTTPS
Servidor Web (PHP)
    ↓ HTTP
localhost:8090 ← ⚠️ localhost DEL SERVIDOR, no del cliente
```

El proxy PHP se conecta al **localhost del servidor**, no del cliente. Por eso falla con error 503.

---

## ✅ Soluciones (de mejor a peor)

### 1. ⭐ Usar HTTPS en la API Local (Recomendado)

Instala un certificado SSL autofirmado en tu API local:

```bash
# Generar certificado SSL autofirmado
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout printer.key -out printer.crt \
  -subj "/CN=localhost"

# Configurar API para usar HTTPS en puerto 8443
```

**Configurar PrinterClient:**
```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8443',
    protocol: 'https',  // ✅ HTTPS
    debug: true
});
```

**Ventaja:** Funciona en todos los navegadores sin configuración adicional.

---

### 2. 🟡 Permitir Mixed Content en el navegador

#### Chrome / Edge (Automático)
✅ Chrome y Edge **permiten automáticamente** Mixed Content para `localhost`.

**Solo recargar con Ctrl+F5** para limpiar caché.

#### Firefox
1. Ir a `about:config`
2. Buscar: `security.mixed_content.block_active_content`
3. Cambiar a `false`
4. Reiniciar navegador

**Desventaja:** Afecta la seguridad global del navegador.

---

### 3. 🟠 Permitir Mixed Content para tu sitio (Chrome)

1. Hacer clic en el **candado** en la barra de direcciones
2. **Configuración del sitio**
3. Buscar **"Contenido no seguro"**
4. Cambiar a **"Permitir"**

**Ventaja:** Solo afecta tu sitio específico.

---

### 4. 🔴 Habilitar Mixed Content temporalmente (Dev)

En la **barra de direcciones** cuando aparece el candado con advertencia:

1. Clic en el candado 🔒
2. **"Cargar scripts no seguros"**
3. Recargar página

**Desventaja:** Hay que hacerlo cada vez que se recarga.

---

## 🧪 Cómo Probar

### 1. Verificar que la API esté corriendo

```bash
# Probar con curl desde el cliente
curl http://localhost:8090/health
# Debe devolver: OK
```

### 2. Abrir DevTools (F12)

```javascript
// En la consola del navegador:
const printer = createPrinterClient({ debug: true });
await printer.healthCheck();

// Debes ver:
// [PrinterClient] Health check exitoso: {success: true, message: "OK"}
```

### 3. Verificar errores comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `ERR_CONNECTION_REFUSED` | API no está corriendo | Iniciar la API local |
| `Mixed Content blocked` | HTTPS → HTTP bloqueado | Usar solución 1, 2 o 3 |
| `503 Service Unavailable` | Proxy intentando conectarse | Deshabilitar `useProxy: false` |
| `Failed to fetch` | Firewall/antivirus | Permitir conexión en puerto 8090 |

---

## 📋 Configuración Recomendada

### Para Desarrollo (localhost)

```javascript
// gui/tu-pagina.php
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',  // Chrome/Edge lo permiten
    timeout: 10000,
    debug: true,       // ⭐ Ver logs en consola
    useProxy: false    // ⚠️ NO usar proxy con localhost
});
```

### Para Producción (HTTPS)

**Opción A: API con SSL**
```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8443',
    protocol: 'https',  // ✅ Con certificado SSL
    useProxy: false
});
```

**Opción B: API remota (otro servidor)**
```javascript
const printer = createPrinterClient({
    host: '192.168.1.100',  // IP del servidor de impresión
    port: '8090',
    protocol: 'http',
    useProxy: true  // ✅ Ahora el proxy SÍ funciona
});
```

---

## 🐛 Troubleshooting

### Error: "No se pudo conectar ni directamente ni vía proxy"

```javascript
// ❌ MAL: useProxy = true con localhost
const printer = createPrinterClient({
    host: 'localhost',
    useProxy: true  // ⚠️ El proxy no puede conectarse al localhost del cliente
});

// ✅ BIEN: useProxy = false con localhost
const printer = createPrinterClient({
    host: 'localhost',
    useProxy: false  // ✅ Conexión directa del navegador
});
```

### Error persiste después de cambios

1. **Limpiar caché:**
   - Chrome: `Ctrl + Shift + Delete` → Limpiar caché
   - O recargar: `Ctrl + F5`

2. **Verificar archivo cargado:**
   ```javascript
   // En DevTools → Sources → js/printer-client.js
   // Busca: useProxy: false
   ```

3. **Probar en incógnito:**
   - `Ctrl + Shift + N` (Chrome)
   - Sin extensiones ni caché

---

## 📊 Comparación de Soluciones

| Solución | Seguridad | Facilidad | Producción |
|----------|-----------|-----------|-----------|
| HTTPS API | ⭐⭐⭐⭐⭐ | 🔧🔧 | ✅ Sí |
| Mixed Content Chrome | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⚠️ Solo Chrome/Edge |
| Mixed Content Firefox | ⭐⭐ | 🔧🔧🔧 | ❌ No |
| Proxy PHP (remoto) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ✅ Sí (solo IP remota) |

---

## 🎯 Recomendación Final

**Para tu caso (localhost de cada cliente):**

1. ✅ **Usar Chrome/Edge** (permiten localhost automáticamente)
2. ✅ **Deshabilitar proxy** (`useProxy: false`)
3. ✅ **Debug activado** para ver errores reales
4. ⚠️ **NO usar proxy** porque el API está en localhost del cliente

```javascript
// Configuración final recomendada
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',
    debug: true,
    useProxy: false  // ⚠️ Crítico para localhost
});
```

---

## 📚 Referencias

- [MDN: Mixed Content](https://developer.mozilla.org/en-US/docs/Web/Security/Mixed_content)
- [Chrome: Mixed Content](https://web.dev/what-is-mixed-content/)
- [OpenSSL Self-Signed Certificate](https://www.openssl.org/docs/manmaster/man1/req.html)

---

**Fecha:** Febrero 2026  
**Status:** ✅ Documentado  
**Próxima revisión:** Confirmar que Chrome/Edge funcionan sin configuración adicional
