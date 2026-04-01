@echo off
REM Script de inicio para Sistema de Citas Médicas con Docker (Windows)

echo =========================================
echo 🏥 Sistema de Citas Médicas - Docker
echo =========================================
echo.

REM Verificar si Docker está instalado
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker no está instalado
    pause
    exit /b 1
)

REM Verificar si Docker Compose está instalado
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker Compose no está instalado
    pause
    exit /b 1
)

echo ✅ Docker detectado
echo ✅ Docker Compose detectado
echo.

REM Iniciar contenedores
echo 🚀 Iniciando contenedores...
docker-compose up -d

REM Esperar a que los servicios se inicien
echo.
echo ⏳ Esperando a que los servicios se inicien...
timeout /t 10 /nobreak

REM Verificar estado
echo.
echo 📊 Estado de los contenedores:
docker-compose ps

echo.
echo =========================================
echo ✅ ¡Sistema inicializado correctamente!
echo =========================================
echo.
echo 🌐 Accesos disponibles:
echo    - Aplicación: http://localhost
echo    - phpMyAdmin: http://localhost:8080
echo.
echo 💾 Credenciales MySQL:
echo    - Usuario: root
echo    - Contraseña: root123
echo    - Base de datos: citas_medicas
echo.
echo Para detener: docker-compose down
echo Para ver logs: docker-compose logs -f
echo.
pause
