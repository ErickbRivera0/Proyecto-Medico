<?php
/**
 * Script de migración de collation — ejecutar UNA SOLA VEZ.
 *
 * Convierte todas las tablas existentes a utf8mb4_unicode_ci para eliminar
 * el error "Mezcla ilegal de intercalaciones" en las consultas JOIN.
 *
 * Uso: php src/init-db-collation.php
 *   o acceder vía HTTP (protegido por la comprobación de CLI o token).
 */

// Protección básica: sólo ejecutable desde CLI o con token de seguridad
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
    $token = getenv('COLLATION_MIGRATION_TOKEN') ?: '';
    $provided = $_GET['token'] ?? '';
    if (empty($token) || !hash_equals($token, $provided)) {
        http_response_code(403);
        die("Acceso denegado. Proporciona el token correcto en ?token=...\n");
    }
}

$db_host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$db_port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
$db_user     = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$db_name     = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'citas_medicas';

$target_charset   = 'utf8mb4';
$target_collation = 'utf8mb4_unicode_ci';

function log_msg(string $msg): void {
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg;
    error_log($line);
    echo $line . "\n";
}

try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 1. Cambiar el collation por defecto de la base de datos
    $pdo->exec("ALTER DATABASE `$db_name` CHARACTER SET $target_charset COLLATE $target_collation");
    log_msg("Base de datos '$db_name' actualizada a $target_charset / $target_collation.");

    // 2. Obtener todas las tablas de la base de datos
    $stmt  = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        log_msg("No se encontraron tablas en '$db_name'. Nada que migrar.");
    } else {
        foreach ($tables as $table) {
            try {
                $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET $target_charset COLLATE $target_collation";
                $pdo->exec($sql);
                log_msg("Tabla '$table' convertida a $target_charset / $target_collation.");
            } catch (PDOException $e) {
                log_msg("ERROR al convertir tabla '$table': " . $e->getMessage());
            }
        }
    }

    log_msg("Migración de collation completada.");

} catch (PDOException $e) {
    log_msg("ERROR de conexión: " . $e->getMessage());
    exit(1);
}
