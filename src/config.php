<?php
// Archivo de configuración principal

// Detectar BASE_URL automáticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host);

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root123');
define('DB_NAME', getenv('DB_NAME') ?: 'citas_medicas');
//define('BASE_URL', 'http://localhost/Proyecto-Citas-Medicas-main/src');
try {
    // Crear conexión PDO (forzando TCP mediante host/port)
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    // Configurar PDO para que lance excepciones en errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar el modo de fetch por defecto
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log del error (en producción no mostrar detalles)
    error_log("Error de conexión: " . $e->getMessage());
    die("❌ Error de conexión con la base de datos. Por favor, intenta más tarde.");
}

?>
