#!/bin/bash

# Script para gestionar backups de la base de datos

echo "╔════════════════════════════════════════════╗"
echo "║  Herramienta de Backup - Citas Médicas    ║"
echo "╚════════════════════════════════════════════╝"
echo ""

BACKUP_DIR="backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="citas_medicas"
DB_USER="root"
DB_PASSWORD="root123"
CONTAINER="citas-medicas-mysql"

# Crear directorio de backups si no existe
if [ ! -d "$BACKUP_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
    echo "✓ Carpeta 'backups' creada"
fi

# Función para hacer backup
backup_database() {
    echo ""
    echo "📦 Realizando backup..."
    BACKUP_FILE="$BACKUP_DIR/citas_medicas_$DATE.sql"
    
    docker exec $CONTAINER mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > "$BACKUP_FILE"
    
    if [ -f "$BACKUP_FILE" ]; then
        SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
        echo "✅ Backup completado: $BACKUP_FILE ($SIZE)"
    else
        echo "❌ Error al crear el backup"
        exit 1
    fi
}

# Función para restaurar backup
restore_database() {
    echo ""
    echo "📂 Backups disponibles:"
    ls -1 $BACKUP_DIR/ | nl
    echo ""
    read -p "Selecciona el número del backup a restaurar: " CHOICE
    
    BACKUP_FILE="$BACKUP_DIR/$(ls $BACKUP_DIR | sed -n "${CHOICE}p")"
    
    if [ -f "$BACKUP_FILE" ]; then
        echo "⏳ Restaurando desde $BACKUP_FILE..."
        docker exec -i $CONTAINER mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < "$BACKUP_FILE"
        echo "✅ Base de datos restaurada correctamente"
    else
        echo "❌ Archivo no encontrado"
        exit 1
    fi
}

# Función para listar backups
list_backups() {
    echo ""
    echo "📋 Backups disponibles:"
    if [ -d "$BACKUP_DIR" ] && [ "$(ls -A $BACKUP_DIR)" ]; then
        ls -lh $BACKUP_DIR/ | tail -n +2 | awk '{print $9, "(" $5 ")"}'
    else
        echo "No hay backups disponibles"
    fi
}

# Menú principal
echo "¿Qué deseas hacer?"
echo ""
echo "1) Hacer backup"
echo "2) Restaurar backup"
echo "3) Listar backups"
echo "4) Salir"
echo ""
read -p "Selecciona una opción (1-4): " OPCION

case $OPCION in
    1)
        backup_database
        ;;
    2)
        restore_database
        ;;
    3)
        list_backups
        ;;
    4)
        echo "¡Hasta luego!"
        exit 0
        ;;
    *)
        echo "❌ Opción inválida"
        exit 1
        ;;
esac

echo ""
