<?php
/**
 * Script para inicializar la base de datos
 * Se ejecuta automáticamente en Railway
 */

$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'citas_medicas';

// Intentar conectar sin la BD primero
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8mb4", $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

    // Forzar collation uniforme en la sesión
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Crear la BD si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Cambiar a la BD
    $pdo->exec("USE `$db_name`");
    
    // Crear tablas si no existen
    $sql = file_get_contents(__DIR__ . '/config/init.sql');
    
    // Ejecutar el SQL (dividir por ; y ejecutar línea a línea)
    $statements = array_filter(array_map('trim', explode(';', $sql)));    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                // Ignorar errores de tablas que ya existen
                if (strpos($e->getMessage(), 'already exists') === false) {
                    error_log("Error ejecutando: $statement - " . $e->getMessage());
                }
            }
        }
    }
    
    error_log("✓ Base de datos inicializada correctamente");
    
} catch (PDOException $e) {
    error_log("✗ Error conectando a la BD: " . $e->getMessage());
    // No fallar completamente, permitir que la app arranque
    // La conexión puede fallar al principio en Railway
}
?>
