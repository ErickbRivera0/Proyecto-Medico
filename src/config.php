<?php
// Archivo de configuración principal
define('BASE_URL', 'http://localhost');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root123');
define('DB_NAME', getenv('DB_NAME') ?: 'citas_medicas');
try {
// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer charset
$conn->set_charset("utf8");
} catch (mysqli_sql_exception $e) {
    // Log del error (en producción no mostrar detalles)
    error_log("Error de conexión: " . $e->getMessage());
    die("❌ Error de conexión con la base de datos. Por favor, intenta más tarde.");
}

?>
