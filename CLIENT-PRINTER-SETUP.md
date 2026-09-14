# 🖨️ Cliente de Impresión Local - HTTPS → HTTP desde Navegador

## 🎯 Cambio de Arquitectura

Ahora la petición se hace **desde el navegador del cliente** directamente a **su propia API local** (localhost del cliente), no al servidor.

### Antes (Proxy):
```
Cliente → Servidor (Proxy PHP) → API Local del Servidor
```

### Ahora (Directo):
```
Cliente → API Local del Cliente (localhost del cliente)
```

Cada usuario imprime en **su propia impresora local**.

---

## ⚠️ El Problema HTTPS → HTTP

Cuando tu web está en HTTPS y intentas conectar a `http://localhost`, los navegadores bloquean la petición por **Mixed Content**, incluso para localhost.

## ✅ Soluciones Implementadas

### Solución 1: Conexión Directa + Fallback (Recomendado)

El código intenta:
1. **Primero**: Conexión directa a `http://localhost:8090` del cliente
2. **Si falla**: Fallback automático al proxy PHP

```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',
    useProxy: true  // Fallback automático si falla
});
```

### Solución 2: HTTPS en API Local (Mejor para Producción)

Instala certificado SSL en la API local de cada cliente:

```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'https',  // API local con SSL
    useProxy: false
});
```

**Cómo generar certificado SSL local:**

```powershell
# Opción 1: mkcert (Recomendado)
# Instalar mkcert: https://github.com/FiloSottile/mkcert
mkcert -install
mkcert localhost 127.0.0.1 ::1

# Usar los certificados en tu API de impresión
# Ejemplo con Node.js:
const https = require('https');
const fs = require('fs');

const options = {
  key: fs.readFileSync('localhost-key.pem'),
  cert: fs.readFileSync('localhost.pem')
};

https.createServer(options, app).listen(8090);
```

### Solución 3: Permitir Mixed Content en Navegador (Solo Desarrollo)

#### Chrome/Edge:
```
chrome://flags/#unsafely-treat-insecure-origin-as-secure
```
Agregar: `http://localhost:8090`

#### Firefox:
1. Abrir `about:config`
2. Buscar: `security.mixed_content.block_active_content`
3. Cambiar a `false` (no recomendado para producción)

---

## 📁 Archivos Creados

### 1. Cliente JavaScript: `js/printer-client.js`

Clase que maneja la comunicación con la API local:

```javascript
const printer = createPrinterClient({
    host: 'localhost',     // Localhost del CLIENTE
    port: '8090',
    protocol: 'http',      // o 'https' si tienes SSL
    useProxy: true,        // Fallback automático
    debug: true            // Logs en consola
});

// Usar el cliente
await printer.imprimirFolio('FOLIO-001');
await printer.imprimirFolios(['FOL-1', 'FOL-2', 'FOL-3']);
```

---

## 🚀 Uso Rápido

Solo necesitas incluir el script y configurar:

```html
<!-- Incluir el cliente -->
<script src="../js/printer-client.js"></script>

<script>
// Crear instancia (se crea automáticamente)
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',
    useProxy: true  // ✅ Importante para HTTPS
});

// Imprimir
async function imprimir() {
    try {
        await printer.imprimirFolio('FOLIO-12345');
        alert('¡Impreso!');
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
```

---

## 🎯 Estrategia por Entorno

### Desarrollo Local (HTTP)
```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',
    useProxy: false,  // No necesario
    debug: true
});
```

### Producción HTTPS - Sin SSL en API Local
```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'http',
    useProxy: true,     // ✅ Fallback al proxy
    debug: false
});
```

### Producción HTTPS - Con SSL en API Local
```javascript
const printer = createPrinterClient({
    host: 'localhost',
    port: '8090',
    protocol: 'https',  // ✅ Directo con SSL
    useProxy: false,
    debug: false
});
```

---

## 🔧 Troubleshooting

### ❌ "Failed to fetch" en HTTP

La API local no está corriendo:
```powershell
netstat -an | findstr :8090
```

### ❌ "Mixed Content blocked" en HTTPS

**Solución automática**: El cliente usa el proxy automáticamente si `useProxy: true`

**Solución permanente**: Instala SSL en tu API local (ver Solución 2)

### ❌ Funciona en HTTP pero no en HTTPS

Esto es normal. Configura `useProxy: true`:
```javascript
const printer = createPrinterClient({
    useProxy: window.location.protocol === 'https:'  // Auto-detect
});
```

---

## ✅ Ventajas

1. ✅ Cada cliente imprime en **su propia impresora**
2. ✅ Intenta conexión **directa primero** (más rápido)
3. ✅ **Fallback automático** al proxy si falla
4. ✅ Funciona en **HTTP y HTTPS**
5. ✅ Código simple y limpio

---

## 📊 Flujo Completo

```
Usuario clic "Imprimir"
    ↓
Intenta: http://localhost:8090 (directo)
    ↓
├─ HTTP: ✅ Conecta directo
│         → Imprime en impresora local
│
└─ HTTPS: ❌ Bloqueado por Mixed Content
          ↓
          useProxy: true?
          ↓
          ✅ Usa proxy PHP automáticamente
          → PHP conecta a API local
          → Devuelve respuesta
          → Imprime
```

---

## 🎉 Configuración Actual

Tu código ya está configurado con:
- ✅ Cliente JavaScript incluido
- ✅ Fallback automático activado
- ✅ Logs de debug activados
- ✅ Manejo de errores mejorado

**Solo necesitas ajustar el puerto si usas uno diferente a 8090.**
