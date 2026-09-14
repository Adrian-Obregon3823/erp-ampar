# 🐳 Docker - Proyecto AMPAR

Este proyecto usa Docker para facilitar el despliegue en cualquier servidor.

## 📋 Requisitos

- Docker Engine 20.10+
- Docker Compose 2.0+
- Base de datos Firebird instalada en el servidor host (fuera del contenedor)

## 🚀 Inicio Rápido

### 1. Configurar Variables de Entorno

Copia el archivo de ejemplo y ajusta los valores:

```bash
cp .env.example .env
```

Edita el archivo `.env` y configura:

- **DB_PATH**: Ruta completa a tu base de datos Firebird en el servidor host
  - Windows: `C:/ruta/completa/AMPARJUL2.FDB`
  - Linux: `/ruta/completa/AMPARJUL2.FDB`
- **DB_HOST**: Usa `host.docker.internal` para acceder al host desde Docker
- Credenciales de la base de datos y correo

### 2. Construir la Imagen

```bash
docker-compose build
```

### 3. Levantar el Contenedor

```bash
docker-compose up -d
```

### 4. Verificar el Estado

```bash
docker-compose ps
docker-compose logs -f
```

## 🌐 Acceso

Una vez levantado, la aplicación estará disponible en:

```
http://localhost:8080
```

## 🔧 Comandos Útiles

### Detener el contenedor
```bash
docker-compose stop
```

### Iniciar el contenedor detenido
```bash
docker-compose start
```

### Reiniciar el contenedor
```bash
docker-compose restart
```

### Ver logs en tiempo real
```bash
docker-compose logs -f
```

### Acceder al contenedor
```bash
docker-compose exec php-apache bash
```

### Detener y eliminar contenedores
```bash
docker-compose down
```

### Reconstruir imagen (después de cambios en Dockerfile)
```bash
docker-compose up -d --build
```

## 📁 Estructura de Archivos Docker

```
amparv3/
├── Dockerfile              # Definición de la imagen PHP 8.3 con Firebird
├── docker-compose.yml      # Orquestación del contenedor
├── .env                    # Variables de entorno (NO subir a Git)
├── .env.example            # Plantilla de variables de entorno
└── .dockerignore          # Archivos ignorados en el build
```

## 🔒 Base de Datos Firebird

### Configuración del Host

Este proyecto está configurado para conectarse a una base de datos Firebird **fuera del contenedor**:

1. La base de datos debe estar instalada y corriendo en el servidor host
2. El contenedor accede al host usando `host.docker.internal`
3. Asegúrate de que el puerto de Firebird (3050) esté abierto en el host
4. La ruta en `DB_PATH` debe ser la ruta real en el host, no en el contenedor

### Windows

Si usas Docker Desktop en Windows, `host.docker.internal` ya está configurado automáticamente.

### Linux

En Linux, Docker Compose añade automáticamente el host via `extra_hosts`.

## ⚙️ Configuración PHP

El contenedor incluye:

- PHP 8.3 con Apache
- Extensiones: pdo_firebird, interbase, gd, zip
- Composer
- Configuración personalizada:
  - memory_limit: 256M
  - upload_max_filesize: 50M
  - post_max_size: 50M
  - max_execution_time: 300s

## 📝 Notas Importantes

1. **El archivo `.env` NO debe subirse a Git** - contiene información sensible
2. La base de datos debe estar en el host, no en el contenedor
3. Los archivos del proyecto se montan como volumen, los cambios son inmediatos
4. Para producción, considera usar un archivo `.env` separado con valores seguros

## 🐛 Solución de Problemas

### No se puede conectar a la base de datos

- Verifica que Firebird esté corriendo en el host
- Verifica la ruta en `DB_PATH` (debe ser la ruta del host)
- Verifica que el puerto 3050 esté abierto
- Revisa los logs: `docker-compose logs -f`

### Permisos de archivos

Si tienes problemas de permisos:

```bash
docker-compose exec php-apache chown -R www-data:www-data /var/www/html
```

### Ver errores PHP

Los logs de Apache están disponibles:

```bash
docker-compose exec php-apache tail -f /var/log/apache2/error.log
```

## 📦 Actualización

Para actualizar la imagen después de cambios en el Dockerfile:

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## 👥 Soporte

Para problemas o preguntas, contacta al equipo de desarrollo.

---

**Desarrollado por**: MONICA SOFIA RODRIGUEZ GARCIA  
**Empresa**: EVOTEK  
**Año**: 2025
