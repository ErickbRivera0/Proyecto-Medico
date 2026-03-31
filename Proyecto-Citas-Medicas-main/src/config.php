<?php
// Configuración básica
define('BASE_URL', 'http://localhost/Proyecto-Citas-Medicas-main/src');

// Datos de conexión (XAMPP)
$host = "localhost";
$user = "root";
$pass = ""; // ⚠️ VACÍO en XAMPP
$db   = "citas-medicas";

// Activar reporte de errores MySQLi (MUY útil)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Crear conexión
    $conn = new mysqli($host, $user, $pass, $db);

    // Charset
    $conn->set_charset("utf8");

    // Configurar zona horaria
    date_default_timezone_set('America/Mexico_City');
    
    // Configurar sesión segura
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
        session_start();
    }

} catch (mysqli_sql_exception $e) {
    // Log del error (en producción no mostrar detalles)
    error_log("Error de conexión: " . $e->getMessage());
    die("❌ Error de conexión con la base de datos. Por favor, intenta más tarde.");
}
?>