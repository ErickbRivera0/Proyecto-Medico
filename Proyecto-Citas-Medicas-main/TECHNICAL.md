# 🏗️ Documentación Técnica

## Arquitectura del Proyecto

```
┌─────────────────────────────────────────┐
│      CLIENTE (Navegador Web)            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│      Apache Server (Puerto 80)          │
│  ┌──────────────────────────────────┐   │
│  │   PHP 8.1                       │   │
│  │   Procesamiento de solicitudes  │   │
│  └──────────────────────────────────┘   │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│    MySQL Server (Puerto 3306)           │
│  ┌──────────────────────────────────┐   │
│  │   Base de Datos: citas_medicas  │   │
│  │   - Tabla: medicos              │   │
│  │   - Tabla: citas                │   │
│  └──────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

## 🔌 Flujo de Datos

### 1. Agendar una Cita
```
Usuario → Formulario (agendar-cita.php) → POST → procesar-cita.php → 
  ├─ Validar datos
  ├─ Conectar a BD
  ├─ INSERT en tabla citas
  └─ Redirigir a mis-citas.php
```

### 2. Ver Citas
```
Usuario → mis-citas.php → SELECT * FROM citas → Mostrar tabla con datos
```

### 3. Ver Médicos
```
Usuario → medicos.php → SELECT * FROM medicos → Mostrar tarjetas
```

## 📊 Esquema de Base de Datos

### Tabla: medicos
```sql
CREATE TABLE medicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Campos:**
- `id`: Identificador único
- `nombre`: Nombre completo del médico
- `especialidad`: Área médica (Cardiología, Dermatología, etc.)
- `telefono`: Contacto telefónico
- `email`: Correo electrónico
- `fecha_registro`: Cuándo se agregó el médico

### Tabla: citas
```sql
CREATE TABLE citas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paciente_nombre VARCHAR(255) NOT NULL,
    paciente_email VARCHAR(255) NOT NULL,
    paciente_telefono VARCHAR(20) NOT NULL,
    medico_id INT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo LONGTEXT,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada'),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medico_id) REFERENCES medicos(id)
);
```

**Campos:**
- `id`: Identificador único de la cita
- `paciente_nombre`: Nombre del paciente
- `paciente_email`: Email para notificaciones
- `paciente_telefono`: Teléfono de contacto
- `medico_id`: Referencia al médico (FK)
- `fecha`: Día de la cita
- `hora`: Hora de la cita
- `motivo`: Descripción del problema/consulta
- `estado`: Estado de la cita
- `fecha_registro`: Cuándo se agendó

## 🗂️ Estructura de Archivos

### Archivos Raíz
```
Dockerfile              # Configuración del contenedor PHP
docker-compose.yml     # Orquestación multi-contenedor
.env                   # Variables de entorno
.gitignore            # Archivos a ignorar en git
start.bat/start.sh    # Scripts de inicio
README.md             # Documentación principal
QUICK_START.md        # Guía rápida
```

### Carpeta: config/
```
init.sql              # Script de inicialización BD
apache.conf           # Configuración Apache (rewrite rules)
```

### Carpeta: src/
```
config.php            # Conexión a BD
helpers.php           # Funciones auxiliares
index.php             # Página de inicio
agendar-cita.php      # Formulario de agendamiento
procesar-cita.php     # Procesamiento de POST
mis-citas.php         # Listado de citas
medicos.php           # Listado de médicos
models/
  ├─ Cita.php         # Clase para gestionar citas
  └─ Medico.php       # Clase para gestionar médicos
controllers/          # Lógica de negocio (preparado para expansión)
views/               # Vistas reutilizables (preparado para expansión)
assets/
  ├─ css/
  │  └─ style.css     # Estilos principales
  └─ js/             # Scripts JavaScript
```

## 🔐 Seguridad

### Medidas implementadas
- ✅ Prepared Statements (prevención SQL Injection)
- ✅ Escapado de HTML (prevención XSS)
- ✅ Validación de entrada
- ✅ Conexión segura a BD

### Mejoras recomendadas
- [ ] Agregar autenticación de usuarios
- [ ] Implementar tokens CSRF
- [ ] Hash de contraseñas
- [ ] Validación en servidor (no solo cliente)
- [ ] Rate limiting
- [ ] Logs de auditoría
- [ ] HTTPS/SSL

## 📦 Dependencias

### Docker (Externo)
- `php:8.1-apache` - Imagen base PHP con Apache
- `mysql:8.0` - Base de datos MySQL
- `phpmyadmin:latest` - Panel de administración BD

### PHP (Interno)
- Extensiones:
  - `pdo` - Acceso a BD
  - `pdo_mysql` - Driver MySQL para PDO
  - `mysqli` - MySQLi (usado actualmente)

## 🚀 Mejoras Futuras

### Corto Plazo
- [ ] Agregar autenticación de usuarios/pacientes
- [ ] Sistema de cambio de contraseña
- [ ] Notificaciones por email
- [ ] Confirmación de citas por SMS
- [ ] Dashboard para médicos
- [ ] Búsqueda de citas avanzada

### Mediano Plazo
- [ ] Sistema de pagos (PayPal, Stripe)
- [ ] Historial médico de pacientes
- [ ] Recetas digitales
- [ ] Histogramas de ocupación
- [ ] API REST para aplicación móvil
- [ ] WebSocket para notificaciones en tiempo real

### Largo Plazo
- [ ] IA para predicción de citas
- [ ] Integración con sistemas ERP
- [ ] Telemedicina
- [ ] Expediente electrónico
- [ ] Aplicación móvil nativa

## 🧪 Testing

### Tests Unitarios (PHP)
```bash
# Instalar PHPUnit
composer require --dev phpunit/phpunit

# Ejecutar tests
./vendor/bin/phpunit tests/
```

### Tests de Integración
```bash
# Verificar conectividad de BD
docker exec citas-medicas-mysql mysql -u root -p root123 -e "SELECT 1"
```

## 📈 Performance

### Optimizaciones Implementadas
- ✅ Índices en BD (automático en ID y FK)
- ✅ CSS minificado
- ✅ Imágenes optimizadas
- ✅ Caché de navegador

### Recomendaciones
- [ ] CDN para assets estáticos
- [ ] Redis para cache
- [ ] Compresión GZIP
- [ ] Lazy loading de imágenes
- [ ] Minificación de JS

## 🔄 Proceso de Deployment

### Desarrollo
```bash
docker-compose up -d
docker-compose logs -f
```

### Producción
1. Cambiar credenciales en `.env`
2. Usar HTTPS
3. Habilitar certificados SSL
4. Backups automáticos de BD
5. Monitoreo de recursos
6. Rate limiting

## 📞 Soporte y Mantenimiento

### Logs
```bash
# Logs de Apache
docker exec citas-medicas-web tail -f /var/log/apache2/error.log

# Logs de MySQL
docker logs citas-medicas-mysql
```

### Backups
```bash
# Backup de BD
docker exec citas-medicas-mysql mysqldump -u root -p root123 citas_medicas > backup.sql

# Restaurar
docker exec -i citas-medicas-mysql mysql -u root -p root123 citas_medicas < backup.sql
```

---

**Documentación actualizada:** 25 de Enero, 2026
