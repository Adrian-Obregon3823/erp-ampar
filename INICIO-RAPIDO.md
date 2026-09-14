# 🚀 Inicio Rápido - AMPAR con Docker

## ⚡ Pasos Rápidos

### 1️⃣ Configurar Variables
```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar .env con tus valores
notepad .env   # Windows
nano .env      # Linux
```

**Importante:** Ajusta `DB_PATH` con la ruta completa a tu base de datos en el HOST.

### 2️⃣ Iniciar Docker

**Windows (PowerShell):**
```powershell
.\docker-start.ps1
```

**Linux/Mac:**
```bash
chmod +x docker-start.sh
./docker-start.sh
```

**O manualmente:**
```bash
docker-compose build
docker-compose up -d
```

### 3️⃣ Verificar

Accede a: **http://localhost:8080**

---

## 🧪 Probar Conexión a Firebird

Desde el contenedor:
```bash
docker-compose exec php-apache php test-firebird-connection.php
```

---

## 📝 Comandos Básicos

```bash
# Ver logs en tiempo real
docker-compose logs -f

# Detener
docker-compose stop

# Iniciar (si ya fue construido)
docker-compose start

# Reiniciar
docker-compose restart

# Detener y eliminar
docker-compose down

# Reconstruir
docker-compose up -d --build
```

---

## ❓ ¿Problemas?

1. **No conecta a Firebird:** Lee [FIREBIRD-CONFIG.md](FIREBIRD-CONFIG.md)
2. **Documentación completa:** Lee [DOCKER-README.md](DOCKER-README.md)
3. **Ver logs:** `docker-compose logs -f`

---

## 📋 Checklist Previo

- [ ] Firebird corriendo en el host
- [ ] Puerto 3050 abierto
- [ ] Archivo `.env` configurado
- [ ] Ruta `DB_PATH` correcta (ruta del HOST)
- [ ] Docker Desktop/Engine corriendo

---

**¿Todo listo?** Ejecuta `docker-start.ps1` (Windows) o `docker-start.sh` (Linux) y ¡a trabajar! 🎉
