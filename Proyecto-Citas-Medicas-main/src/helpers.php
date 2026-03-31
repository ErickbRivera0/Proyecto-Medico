<?php
/**
 * Funciones auxiliares para el sistema de citas
 */

/**
 * Validar email
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitizar entrada de usuario
 */
function sanitizar($texto) {
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirigir a una página
 */
function redirigir($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Obtener valor seguro de arrays
 */
function obtener($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Formatear fecha en español
 */
function formato_fecha($fecha) {
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $timestamp = strtotime($fecha);
    $mes = $meses[date('n', $timestamp) - 1];
    $dia = date('d', $timestamp);
    $año = date('Y', $timestamp);
    
    return "$dia de $mes de $año";
}

/**
 * Generar token CSRF
 */
function generar_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validar_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log de actividades
 */
function registrar_log($mensaje) {
    $archivo = __DIR__ . '/logs/actividad.log';
    if (!is_dir(dirname($archivo))) {
        mkdir(dirname($archivo), 0755, true);
    }
    $fecha = date('Y-m-d H:i:s');
    file_put_contents($archivo, "[$fecha] $mensaje\n", FILE_APPEND);
}

?>
