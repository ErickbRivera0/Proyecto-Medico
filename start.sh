#!/bin/bash

# Script de inicio para Sistema de Citas Médicas con Docker

echo "========================================="
echo "🏥 Sistema de Citas Médicas - Docker"
echo "========================================="
echo ""

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado"
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado"
    exit 1
fi

echo "✅ Docker detectado"
echo "✅ Docker Compose detectado"
echo ""

# Iniciar contenedores
echo "🚀 Iniciando contenedores..."
docker-compose up -d

# Esperar a que los servicios se inicien
echo ""
echo "⏳ Esperando a que los servicios se inicien..."
sleep 10

# Verificar estado
echo ""
echo "📊 Estado de los contenedores:"
docker-compose ps

echo ""
echo "========================================="
echo "✅ ¡Sistema inicializado correctamente!"
echo "========================================="
echo ""
echo "🌐 Accesos disponibles:"
echo "   - Aplicación: http://localhost"
echo "   - phpMyAdmin: http://localhost:8080"
echo ""
echo "💾 Credenciales MySQL:"
echo "   - Usuario: root"
echo "   - Contraseña: root123"
echo "   - Base de datos: citas_medicas"
echo ""
echo "Para detener: docker-compose down"
echo "Para ver logs: docker-compose logs -f"
echo ""
