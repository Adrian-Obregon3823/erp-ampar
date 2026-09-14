/**
 * Cliente de Impresión Local
 * 
 * Maneja la comunicación directa desde el navegador del cliente
 * a su API de impresión local (localhost del cliente)
 * 
 * ⚠️ IMPORTANTE PARA HTTPS:
 * - El proxy PHP NO funciona para APIs en localhost del cliente
 * - El proxy solo sirve para APIs remotas (ej: http://192.168.1.100:8090)
 * - Para localhost, el navegador debe permitir Mixed Content:
 *   • Chrome/Edge: Permiten localhost automáticamente
 *   • Firefox: Configurar security.mixed_content.block_active_content = false
 *   • O usar certificado SSL en la API local (puerto 8443)
 */

class PrinterClient {
    constructor(config = {}) {
        this.config = {
            // API local del CLIENTE (su propia computadora)
            host: config.host || 'localhost',
            port: config.port || '8090',
            protocol: config.protocol || 'http', // o 'https' si tiene SSL
            timeout: config.timeout || 10000,
            useProxy: config.useProxy !== undefined ? config.useProxy : false, // NO usar proxy por defecto
            debug: config.debug || false
        };
        
        this.baseUrl = `${this.config.protocol}://${this.config.host}:${this.config.port}`;
        this.log('Inicializado con configuración:', this.config);
        
        // Advertencia si intenta usar proxy con localhost
        if (this.config.useProxy && this.config.host === 'localhost') {
            console.warn('[PrinterClient] ⚠️ ADVERTENCIA: El proxy PHP NO funciona con localhost del cliente. Solo para IPs remotas.');
        }
    }
    
    log(...args) {
        if (this.config.debug) {
            console.log('[PrinterClient]', ...args);
        }
    }
    
    /**
     * Detecta si estamos en HTTPS y hay riesgo de Mixed Content
     */
    hasMixedContentRisk() {
        return window.location.protocol === 'https:' && this.config.protocol === 'http';
    }
    
    /**
     * Health check - Verifica si la API local está disponible
     */
    async healthCheck() {
        this.log('Health check...');
        
        try {
            const response = await this._fetch('/health', {
                method: 'GET'
            });
            
            this.log('Health check exitoso:', response);
            return { success: true, data: response };
        } catch (error) {
            this.log('Health check falló:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Imprime un solo folio
     */
    async imprimirFolio(folio) {
        this.log('Imprimiendo folio:', folio);
        
        return await this._fetch('/imprimir', {
            method: 'POST',
            body: JSON.stringify({ folio: folio })
        });
    }
    
    /**
     * Imprime múltiples folios
     */
    async imprimirFolios(folios) {
        this.log('Imprimiendo folios:', folios);
        
        return await this._fetch('/imprimir-multiple', {
            method: 'POST',
            body: JSON.stringify({ folios: folios })
        });
    }
    
    /**
     * Método interno para hacer peticiones
     */
    async _fetch(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        
        const fetchOptions = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            signal: AbortSignal.timeout(this.config.timeout)
        };
        
        if (options.body) {
            fetchOptions.body = options.body;
        }
        
        try {
            this.log('Petición a:', url);
            
            // Intentar conexión directa al localhost del cliente
            const response = await fetch(url, fetchOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // ✅ Verificar Content-Type antes de parsear
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                // Respuesta es JSON
                data = await response.json();
            } else {
                // Respuesta es texto plano (ej: "OK" en /health)
                const text = await response.text();
                data = { success: true, message: text };
            }
            
            this.log('Respuesta recibida:', data);
            return data;
            
        } catch (error) {
            this.log('Error en petición directa:', error);
            
            // Solo usar proxy si es un error de RED (no de parseo)
            const isNetworkError = error.name === 'TypeError' || 
                                   error.name === 'AbortError' ||
                                   error.message.includes('Failed to fetch') ||
                                   error.message.includes('NetworkError');
            
            if (this.config.useProxy && isNetworkError && this.config.host !== 'localhost' && this.config.host !== '127.0.0.1') {
                this.log('Intentando con proxy PHP...');
                return await this._fetchViaProxy(endpoint, options);
            }
            
            throw error;
        }
    }
    
    /**
     * Fallback: Usar proxy PHP si la conexión directa falla
     */
    async _fetchViaProxy(endpoint, options = {}) {
        this.log('Usando proxy PHP como fallback');
        
        // Determinar qué endpoint del proxy usar
        let proxyUrl = '../ajax/proxy.imprimir.php';
        
        if (endpoint === '/health') {
            proxyUrl += '?action=health';
        }
        
        const fetchOptions = {
            method: options.method || 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (options.body && options.method !== 'GET') {
            fetchOptions.body = options.body;
        }
        
        try {
            const response = await fetch(proxyUrl, fetchOptions);
            
            if (!response.ok) {
                // ✅ Intentar parsear JSON de error, si falla usar statusText
                let errorMessage = `HTTP ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.error || errorData.details || response.statusText;
                } catch (e) {
                    errorMessage = await response.text() || response.statusText;
                }
                throw new Error(errorMessage);
            }
            
            // ✅ Parsear respuesta exitosa
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                data = { success: true, message: text };
            }
            
            this.log('Respuesta del proxy:', data);
            return data;
            
        } catch (error) {
            this.log('Error en proxy:', error);
            throw new Error('No se pudo conectar ni directamente ni vía proxy: ' + error.message);
        }
    }
}

/**
 * Configuración automática según el entorno
 * 
 * ⚠️ NOTA: useProxy está deshabilitado por defecto porque:
 * - Si la API está en localhost del CLIENTE, el proxy PHP no puede conectarse
 * - El proxy PHP se conecta al localhost del SERVIDOR, no del cliente
 * - Solo habilitar si la API está en una IP remota (ej: 192.168.1.100:8090)
 */
function createPrinterClient(customConfig = {}) {
    const isHTTPS = window.location.protocol === 'https:';
    
    // Configuración por defecto
    const defaultConfig = {
        host: 'localhost',
        port: '8090',
        protocol: 'http',  // Cambiar a 'https' si tu API local tiene SSL
        timeout: 10000,
        debug: false,  // Cambiar a true para debugging
        useProxy: false  // ⚠️ NO usar proxy con localhost (solo para IPs remotas)
    };
    
    return new PrinterClient({ ...defaultConfig, ...customConfig });
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.PrinterClient = PrinterClient;
    window.createPrinterClient = createPrinterClient;
}
