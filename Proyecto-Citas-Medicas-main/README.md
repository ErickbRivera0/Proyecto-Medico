# Sistema de Citas Médicas - Docker

## 📋 Descripción

Sistema completo para gestión de citas médicas en un consultorio, desarrollado en **PHP** con **Docker**, **MySQL** y **Apache**.

## 🚀 Características

- ✅ Gestión de citas médicas
- ✅ Base de datos MySQL con 5 médicos de ejemplo
- ✅ Interfaz responsiva moderna
- ✅ Formularios para agendar citas
- ✅ Panel de visualización de citas
- ✅ Gestión de médicos
- ✅ Panel phpMyAdmin para administración de BD
- ✅ Totalmente containerizado con Docker

## 📦 Estructura del Proyecto

```
Proyecto Citas Medicas/
├── Dockerfile                 # Configuración PHP-Apache
├── docker-compose.yml         # Orquestación de servicios
├── README.md                  # Este archivo
├── config/
│   ├── init.sql              # Script de inicialización BD
│   └── apache.conf           # Configuración Apache
├── src/
│   ├── index.php             # Página de inicio
│   ├── agendar-cita.php      # Formulario para agendar
│   ├── procesar-cita.php     # Procesamiento de cita
│   ├── mis-citas.php         # Listado de citas
│   ├── medicos.php           # Listado de médicos
│   ├── config.php            # Configuración BD
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css     # Estilos
│   │   └── js/               # Scripts JavaScript
│   ├── controllers/          # Controladores (MVC)
│   ├── models/              # Modelos (MVC)
│   └── views/               # Vistas (MVC)
```

## 🐳 Servicios Docker

| Servicio   | Puerto | Usuario | Contraseña |
| ---------- | ------ | ------- | ---------- |
| PHP-Apache | 80     | -       | -          |
| MySQL      | 3306   | root    | root123    |
| phpMyAdmin | 8080   | root    | root123    |

## 🔧 Requisitos

- Docker
- Docker Compose
- (Opcional) Git

## 📥 Instalación

### 1. Clonar o descargar el proyecto

```bash
git clone <url-del-repo>
cd "Proyecto Citas Medicas"
```

### 2. Iniciar los contenedores

```bash
docker-compose up -d
```

Espera a que los servicios se inicien completamente (30-60 segundos).

### 3. Verificar que todo está funcionando

```bash
docker-compose ps
```

Deberías ver los 3 contenedores en estado `Up`.

## 🌐 Acceso a la Aplicación

- **Aplicación Principal**: http://localhost
- **phpMyAdmin**: http://localhost:8080
  - Usuario: `root`
  - Contraseña: `root123`

## 💾 Base de Datos

### Credenciales

- **Host**: mysql (desde dentro de Docker) o localhost (desde fuera)
- **Puerto**: 3306
- **Usuario**: root
- **Contraseña**: root123
- **Base de datos**: citas_medicas

### Tablas

**medicos**: Información de los médicos

```sql
- id (INT, PK)
- nombre (VARCHAR)
- especialidad (VARCHAR)
- telefono (VARCHAR)
- email (VARCHAR)
- fecha_registro (TIMESTAMP)
```

**citas**: Registro de citas

```sql
- id (INT, PK)
- paciente_nombre (VARCHAR)
- paciente_email (VARCHAR)
- paciente_telefono (VARCHAR)
- medico_id (INT, FK)
- fecha (DATE)
- hora (TIME)
- motivo (LONGTEXT)
- estado (ENUM)
- fecha_registro (TIMESTAMP)
```

## 📝 Uso de la Aplicación

### 1. Ver Médicos

- Navega a "Médicos"
- Visualiza la lista completa de médicos y sus especialidades

### 2. Agendar Cita

- Haz clic en "Agendar Cita"
- Completa el formulario con tus datos
- Selecciona fecha y hora
- Envía el formulario

### 3. Ver Mis Citas

- Navega a "Mis Citas"
- Visualiza todas las citas agendadas
- Puedes editar o eliminar citas

## 🛠️ Comandos Útiles

```bash
# Ver logs
docker-compose logs -f web

# Detener contenedores
docker-compose down

# Reiniciar servicios
docker-compose restart

# Limpiar volúmenes (⚠️ borrará datos)
docker-compose down -v

# Acceder a la terminal PHP
docker exec -it citas-medicas-web bash

# Acceder a MySQL
docker exec -it citas-medicas-mysql mysql -u root -p
```

## 🔐 Seguridad

- ⚠️ **IMPORTANTE**: Cambiar credenciales en producción
- Usar variables de entorno en `.env`
- Implementar validación en servidor
- Usar prepared statements para prevenir SQL injection

## 📚 Próximos Pasos

- [ ] Agregar autenticación de usuarios
- [ ] Implementar notificaciones por email
- [ ] Agregar sistema de pagos
- [ ] Crear dashboard para médicos
- [ ] Implementar SMS para recordatorios
- [ ] Agregar historial de pacientes

## 🤝 Contribuir

Las contribuciones son bienvenidas. Para cambios importantes:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia MIT - ver el archivo LICENSE para más detalles.

## 📞 Soporte

Para preguntas o problemas, contacta a: soporte@citas-medicas.com

---

**Última actualización**: 27 de Enero, 2026
