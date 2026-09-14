# Configuración de Firebird para Docker

## 📌 Configuración Importante

Este proyecto usa **Firebird** como base de datos, que se ejecuta **fuera del contenedor Docker** (en el servidor host).

## 🔧 Requisitos del Host

### 1. Firebird debe estar instalado y corriendo en el host

**Windows:**
```powershell
# Verificar si Firebird está corriendo
Get-Service | Where-Object {$_.Name -like "*Firebird*"}

# O buscar el proceso
Get-Process | Where-Object {$_.Name -like "*fb*"}
```

**Linux:**
```bash
# Verificar si Firebird está corriendo
systemctl status firebird3.0
# o
ps aux | grep firebird
```

### 2. Puerto de Firebird

Por defecto, Firebird usa el puerto **3050**. Asegúrate de que este puerto esté:
- Abierto en el firewall
- No bloqueado por Windows Defender u otro antivirus
- Accesible desde localhost

**Windows - Verificar puerto:**
```powershell
netstat -an | findstr :3050
```

**Linux - Verificar puerto:**
```bash
netstat -tuln | grep 3050
# o
ss -tuln | grep 3050
```

### 3. Configuración de `firebird.conf`

Asegúrate de que Firebird esté configurado para aceptar conexiones TCP/IP.

**Ubicación del archivo:**
- Windows: `C:\Program Files\Firebird\Firebird_X_X\firebird.conf`
- Linux: `/etc/firebird/3.0/firebird.conf` o `/opt/firebird/firebird.conf`

**Configuraciones importantes:**
```
# Debe ser SuperServer o SuperClassic
ServerMode = Super

# Debe permitir conexiones remotas
RemoteServicePort = 3050

# Autenticación
AuthServer = Srp, Legacy_Auth
AuthClient = Srp, Legacy_Auth, Legacy_UserManager

# Para desarrollo, puedes permitir conexiones de cualquier lugar
# Para producción, restringe a IPs específicas
```

### 4. Permisos de la base de datos

**Windows:**
- El archivo `.FDB` debe tener permisos de lectura/escritura
- El usuario del servicio Firebird debe tener acceso al archivo

**Linux:**
```bash
# Cambiar propietario
sudo chown firebird:firebird /ruta/a/base.fdb

# Permisos
sudo chmod 660 /ruta/a/base.fdb
```

## 🐳 Configuración en Docker

### Variables de Entorno (.env)

```env
# Host de Firebird desde Docker
DB_HOST=host.docker.internal

# Ruta COMPLETA en el HOST (no en el contenedor)
DB_PATH=C:/laragon/www/amparv3/bd/AMPARJUL2.FDB

# Credenciales (por defecto en Firebird)
DB_USERNAME=SYSDBA
DB_PASSWORD=masterkey
```

### Formatos de ruta DB_PATH:

**Windows:**
```
# Con barras normales (recomendado)
DB_PATH=C:/laragon/www/amparv3/bd/AMPARJUL2.FDB

# Con barras invertidas escapadas
DB_PATH=C:\\laragon\\www\\amparv3\\bd\\AMPARJUL2.FDB
```

**Linux:**
```
DB_PATH=/var/lib/firebird/data/AMPARJUL2.FDB
```

## 🧪 Probar Conexión

### Desde el Contenedor

```bash
# Acceder al contenedor
docker-compose exec php-apache bash

# Instalar netcat si no está
apt-get update && apt-get install -y netcat-traditional

# Probar conexión al puerto de Firebird
nc -zv host.docker.internal 3050

# Salir
exit
```

### Desde PHP

Crea un archivo `test-firebird.php` en la raíz:

```php
<?php
require_once 'config/variables.php';

echo "Intentando conectar a Firebird...\n";
echo "Host: " . $GLOBALS['host'] . "\n";
echo "Database: " . $GLOBALS['dbname'] . "\n";

try {
    $dbh = ibase_connect(
        $GLOBALS['host'] . ':' . $GLOBALS['dbname'],
        $GLOBALS['username'],
        $GLOBALS['password'],
        'UTF8'
    );
    
    if ($dbh) {
        echo "✓ Conexión exitosa!\n";
        ibase_close($dbh);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
```

Ejecuta desde el contenedor:
```bash
docker-compose exec php-apache php test-firebird.php
```

## 🚨 Solución de Problemas Comunes

### Error: "Unable to complete network request"

**Causa:** Firebird no está corriendo o no es accesible

**Solución:**
1. Verifica que Firebird esté corriendo en el host
2. Verifica que el puerto 3050 esté abierto
3. En Windows, verifica Windows Defender/Firewall

### Error: "I/O error for file"

**Causa:** La ruta del archivo `.FDB` es incorrecta o no tiene permisos

**Solución:**
1. Verifica la ruta en `DB_PATH`
2. Asegúrate de usar la ruta del HOST, no del contenedor
3. Verifica permisos del archivo `.FDB`

### Error: "Your user name and password are not defined"

**Causa:** Credenciales incorrectas

**Solución:**
1. Verifica `DB_USERNAME` y `DB_PASSWORD` en `.env`
2. Por defecto son: SYSDBA / masterkey
3. Si cambiaste las credenciales, actualiza el `.env`

### Error: "Connection refused"

**Causa:** Docker no puede acceder al host

**Solución:**
1. Verifica que usas `host.docker.internal` en `DB_HOST`
2. En Linux, verifica que el `extra_hosts` esté configurado en docker-compose.yml
3. Prueba también con la IP local del host (ej: 192.168.1.100)

## 📝 Notas Adicionales

### ¿Por qué host.docker.internal?

Docker crea una red aislada para los contenedores. `host.docker.internal` es un nombre especial que Docker resuelve a la IP del host, permitiendo que el contenedor acceda a servicios en el host.

### Alternativa: Usar IP del Host

Si `host.docker.internal` no funciona, puedes usar la IP local:

**Windows:**
```powershell
ipconfig
# Busca "IPv4 Address" de tu adaptador principal
```

**Linux:**
```bash
hostname -I
# o
ip addr show
```

Luego actualiza `DB_HOST` en `.env`:
```env
DB_HOST=192.168.1.100  # Tu IP local
```

### Performance

Para mejor performance en desarrollo:
- Usa Firebird en modo SuperServer
- Ajusta el cache en `firebird.conf`
- Considera usar un disco SSD para la base de datos

---

**¿Necesitas ayuda?** Revisa los logs del contenedor:
```bash
docker-compose logs -f
```
