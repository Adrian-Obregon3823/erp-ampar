#!/bin/bash

# ========================================
# Script de inicio para Docker - AMPAR
# ========================================
# Uso: ./docker-start.sh

echo "🐳 AMPAR - Docker Setup"
echo "========================"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Verificar si Docker está corriendo
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker no está corriendo. Por favor inicia Docker.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker está corriendo${NC}"

# Verificar si existe .env
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠ Archivo .env no encontrado${NC}"
    
    if [ -f ".env.example" ]; then
        echo -e "${YELLOW}📋 Copiando .env.example a .env...${NC}"
        cp .env.example .env
        echo -e "${GREEN}✓ Archivo .env creado. Por favor edítalo con tus configuraciones.${NC}"
        echo ""
        echo -e "${YELLOW}Importante: Ajusta las siguientes variables en .env:${NC}"
        echo -e "${YELLOW}  - DB_PATH: Ruta a tu base de datos Firebird${NC}"
        echo -e "${YELLOW}  - DB_HOST: Generalmente 'host.docker.internal'${NC}"
        echo -e "${YELLOW}  - Credenciales de la base de datos${NC}"
        echo ""
        
        read -p "¿Deseas continuar con los valores por defecto? (s/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            echo -e "${YELLOW}Edita el archivo .env y ejecuta este script nuevamente.${NC}"
            exit 0
        fi
    else
        echo -e "${RED}✗ No se encontró .env.example. Crea un archivo .env manualmente.${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${CYAN}🔨 Construyendo imagen Docker...${NC}"
docker-compose build

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Imagen construida exitosamente${NC}"
    echo ""
    echo -e "${CYAN}🚀 Levantando contenedor...${NC}"
    docker-compose up -d
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Contenedor levantado exitosamente${NC}"
        echo ""
        echo -e "${CYAN}📊 Estado del contenedor:${NC}"
        docker-compose ps
        echo ""
        echo -e "${GREEN}🌐 Aplicación disponible en: http://localhost:8080${NC}"
        echo ""
        echo -e "${CYAN}📝 Comandos útiles:${NC}"
        echo "  Ver logs:      docker-compose logs -f"
        echo "  Detener:       docker-compose stop"
        echo "  Reiniciar:     docker-compose restart"
        echo "  Eliminar:      docker-compose down"
    else
        echo -e "${RED}✗ Error al levantar el contenedor${NC}"
        echo -e "${YELLOW}Revisa los logs con: docker-compose logs${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Error al construir la imagen${NC}"
    exit 1
fi
