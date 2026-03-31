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

} catch (mysqli_sql_exception $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>