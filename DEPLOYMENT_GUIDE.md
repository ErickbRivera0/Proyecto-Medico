# 🚀 Guía de Deployment en Railway

## Paso 1: Preparar tu repositorio

```bash
git add .
git commit -m "Configurar para Railway"
git push origin main
```

## Paso 2: Crear proyecto en Railway

1. Ve a [railway.app](https://railway.app)
2. Click en **"New Project"**
3. Selecciona **"Deploy from GitHub"**
4. Conecta tu cuenta de GitHub y selecciona tu repositorio

## Paso 3: Agregar servicio MySQL

1. En el dashboard del proyecto, click en **"+ Add Service"**
2. Selecciona **"MySQL"** (o Database > MySQL)
3. Railway creará la BD automáticamente

## Paso 4: Conectar variables de entorno

En tu servicio PHP, ve a **"Variables"** y agrega:

```
DB_HOST         = {{MYSQL_HOSTNAME}}
DB_USER         = {{MYSQL_USER}}
DB_PASSWORD     = {{MYSQL_PASSWORD}}
DB_NAME         = {{MYSQL_DATABASE}}
APP_ENV         = production
APP_DEBUG       = false
PORT            = 8080
```

> **Nota:** Las variables con `{{ }}` se reemplazan automáticamente desde el servicio MySQL

## Paso 5: Configurar el puerto

Railway usa el puerto **8080** por defecto. Verifica en `config.php`:

```php
$port = getenv('PORT') ?: 8080;
// Asegúrate de que la app escuche en este puerto
```

## Paso 6: Deployar

1. El despliegue comienza automáticamente cuando haces push a GitHub
2. Ve a **"Deployments"** para ver el progreso
3. Cuando esté listo, verás la URL: `nombre-proyecto.up.railway.app`

## Verificar que está funcionando

```bash
# Visita en el navegador:
https://nombre-proyecto.up.railway.app

# Revisa logs:
# Click en el servicio > "Logs"
```

## Solucionar problemas

### Error: "Cannot connect to database"

- Verifica que las variables de entorno estén correctas
- Espera 1-2 minutos para que MySQL esté listo
- Revisa los logs del servicio MySQL

### Error: "Connection refused"

- Asegúrate de que DB_HOST sea `{{MYSQL_HOSTNAME}}` (no localhost)
- Verifica que el servicio MySQL esté corriendo

### Error: "Tables don't exist"

- El script `init-db.php` debería crearlas automáticamente
- Si no, conecta directamente a MySQL y ejecuta `config/init.sql`

## Conectar con MySQL directamente (opcional)

```bash
# Obtener credenciales de Railway
# En Variables > "Generate Database URL"

# Luego conectar con:
mysql -h HOST -u USER -p DATABASE
```

## Redeploy

Para forzar un redeploy sin cambios:
1. Ve a **"Deployments"**
2. Click en los **3 puntos** del último deployment
3. Selecciona **"Redeploy"**

---

¿Problemas? Revisa los logs en Railway > Tu servicio > Logs
