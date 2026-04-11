<?php
// Archivo de configuración principal

// Detectar BASE_URL automáticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host);

define('DB_HOST', getenv('MYSQLHOST') ?: '127.0.0.1');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'citas_medicas');
//define('BASE_URL', 'http://localhost/Proyecto-Citas-Medicas-main/src');
try {
    // Crear conexión PDO (forzando TCP mediante host/port)
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    // Configurar PDO para que lance excepciones en errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar el modo de fetch por defecto
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Forzar collation uniforme en la sesión para evitar errores de mezcla de intercalaciones
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Log del error (en producción no mostrar detalles)
    error_log("Error de conexión: " . $e->getMessage());
    die("❌ Error de conexión con la base de datos. Por favor, intenta más tarde.");
}

/**
 * Actualiza automáticamente el estado de las citas:
 * 1. Pendiente → Confirmada (después de 15 minutos)
 * 2. Confirmada → Cancelada (si la fecha/hora ya pasó sin marcarse como completada)
 */
function actualizarEstadosCitas($pdo) {
    try {
        // 1. Confirmar citas pendientes después de 15 minutos
        $pdo->exec("
            UPDATE citas 
            SET estado = 'confirmada'
            WHERE estado = 'pendiente' 
            AND fecha_registro < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        
        // 2. Cancelar citas confirmadas cuya fecha/hora ya pasó
        $pdo->exec("
            UPDATE citas 
            SET estado = 'cancelada'
            WHERE estado = 'confirmada' 
            AND CONCAT(fecha, ' ', hora) < NOW()
        ");
    } catch (Exception $e) {
        // Silenciosamente ignorar errores de actualización automática
        error_log("Error en actualización de citas: " . $e->getMessage());
    }
}

// Ejecutar actualización automática en cada carga
if (isset($pdo)) {
    actualizarEstadosCitas($pdo);
}

?>
