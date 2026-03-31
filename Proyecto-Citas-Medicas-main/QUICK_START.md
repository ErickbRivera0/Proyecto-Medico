# 🚀 Guía Rápida de Inicio

## ¿Qué tienes?

Una base completa **profesional** de Sistema de Citas Médicas con Docker, PHP, MySQL y Apache.

## 📦 Lo que incluye

✅ **Aplicación Web completa** en PHP  
✅ **Base de datos** MySQL con médicos y citas  
✅ **Interfaz responsiva** moderna y atractiva  
✅ **Docker Compose** totalmente configurado  
✅ **phpMyAdmin** para gestionar la BD  
✅ **Scripts de inicio** para Windows y Linux  

## ⚡ Inicio en 3 pasos

### Paso 1: Abrir terminal en la carpeta del proyecto

```powershell
# Windows
cd "C:\Users\Erick\OneDrive - Universidad Tecnológica de Honduras\Escritorio\Proyecto Citas Medicas"
```

### Paso 2: Ejecutar script de inicio

**Windows:**
```powershell
.\start.bat
```

**Linux/Mac:**
```bash
bash start.sh
```

O manualmente con Docker:
```bash
docker-compose up -d
```

### Paso 3: Acceder a la aplicación

- **Sitio Web**: [http://localhost](http://localhost)
- **Base de Datos**: [http://localhost:8080](http://localhost:8080) (phpMyAdmin)

## 📋 Estructura de carpetas

```
├── src/                          # Código PHP de la aplicación
│   ├── index.php                # Página de inicio
│   ├── agendar-cita.php         # Formulario de citas
│   ├── mis-citas.php            # Listado de citas
│   ├── medicos.php              # Listado de médicos
│   ├── assets/
│   │   └── css/style.css        # Estilos CSS
│   └── config.php               # Conexión BD
│
├── config/
│   ├── init.sql                 # Script de BD
│   └── apache.conf              # Config Apache
│
├── Dockerfile                   # Config PHP-Apache
├── docker-compose.yml           # Orquestación Docker
├── README.md                    # Documentación completa
└── start.bat/start.sh          # Scripts de inicio
```

## 🔧 Comandos útiles

```bash
# Ver estado de contenedores
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f

# Detener contenedores
docker-compose down

# Reiniciar
docker-compose restart

# Acceder a terminal PHP
docker exec -it citas-medicas-web bash

# Acceder a MySQL
docker exec -it citas-medicas-mysql mysql -u root -p root123
```

## 💾 Credenciales

**MySQL:**
- Usuario: `root`
- Contraseña: `root123`
- Puerto: `3306`

**Base de Datos:** `citas_medicas`

## 🎨 Características de la aplicación

✨ **Inicio:** Panel bienvenida con acceso rápido  
✨ **Médicos:** Listado con especialidades y datos contacto  
✨ **Agendar:** Formulario para reservar citas  
✨ **Mis Citas:** Visualizar, editar y eliminar citas  
✨ **Responsive:** Se adapta a cualquier dispositivo  
✨ **Moderno:** Diseño gradiente y profesional  

## 🐳 Servicios Docker

| Servicio | Puerto | Estado |
|----------|--------|--------|
| PHP-Apache | 80 | Activo ✅ |
| MySQL | 3306 | Activo ✅ |
| phpMyAdmin | 8080 | Activo ✅ |

## ⚠️ Notas importantes

1. **Primera ejecución**: Espera 30-60 segundos a que se inicialicen los servicios
2. **Base de datos**: Se crea automáticamente con 5 médicos de ejemplo
3. **Puerto 80**: Asegúrate que no esté en uso (Apache, Nginx, etc.)
4. **Cambiar credenciales**: En producción, actualiza `.env` con nuevas credenciales

## 📞 ¿Problemas?

### Puerto 80 en uso
```bash
# Ver qué usa el puerto
netstat -ano | findstr :80

# Cambiar en docker-compose.yml:
# ports:
#   - "8000:80"  # Luego acceder a http://localhost:8000
```

### No se conecta a BD
```bash
# Verificar logs
docker-compose logs mysql

# Reiniciar
docker-compose down
docker-compose up -d
```

### Limpiar todo y empezar de nuevo
```bash
docker-compose down -v
docker-compose up -d
```

## 🎯 Próximos pasos

1. Personalizar datos de médicos en `config/init.sql`
2. Modificar estilos en `src/assets/css/style.css`
3. Agregar lógica de negocio en `src/controllers/`
4. Implementar autenticación de usuarios
5. Configurar envío de emails

---

**¡Listo para empezar! 🚀**
