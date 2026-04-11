<?php
/**
 * Helper de validaciones y seguridad
 */

/**
 * Validar fortaleza de contraseña
 * Requiere: 8+ caracteres, mayúscula, minúscula, número, carácter especial
 */
function validarContraseña($password) {
    $errores = [];
    
    if (strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = "La contraseña debe contener al menos una mayúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = "La contraseña debe contener al menos una minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = "La contraseña debe contener al menos un número";
    }
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/', $password)) {
        $errores[] = "La contraseña debe contener al menos un carácter especial (!@#$%^&*)";
    }
    
    return [
        'valido' => count($errores) === 0,
        'errores' => $errores
    ];
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar teléfono (Honduras)
 */
function validarTelefono($telefono) {
    // Formato: +504 9999 9999 o 99999999
    $patron = '/^(\+504|0)?[2-9]\d{7}$/';
    return preg_match($patron, preg_replace('/\s+/', '', $telefono));
}

/**
 * Validar edad (mínimo 18 años)
 */
function validarEdad($fecha_nacimiento) {
    $fecha_nac = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y;
    
    return [
        'valido' => $edad >= 18,
        'edad' => $edad,
        'mensaje' => $edad < 18 ? "Debes ser mayor de 18 años para usar el sistema" : ""
    ];
}

/**
 * Validar que la fecha de cita sea en el futuro
 */
function validarFechaCita($fecha, $hora) {
    $datetime_cita = new DateTime($fecha . ' ' . $hora);
    $ahora = new DateTime();
    $ahora_15min = clone $ahora;
    $ahora_15min->add(new DateInterval('PT15M'));
    
    return [
        'valido' => $datetime_cita >= $ahora_15min,
        'mensaje' => $datetime_cita < $ahora_15min 
            ? "La cita debe ser al menos 15 minutos en el futuro" 
            : ""
    ];
}

/**
 * Validar que la hora esté dentro del horario del médico
 */
function validarHoraDelMedico($pdo, $medico_id, $hora, $fecha) {
    try {
        // Obtener horarios del médico
        $stmt = $pdo->prepare("SELECT hora_inicio, hora_fin, dias_trabajo FROM medicos WHERE id = ? AND activo = 1");
        $stmt->execute([$medico_id]);
        $medico = $stmt->fetch();
        
        if (!$medico) {
            return ['valido' => false, 'mensaje' => 'Médico no encontrado o inactivo'];
        }
        
        // Validar hora
        $hora_inicio = new DateTime('1970-01-01 ' . $medico['hora_inicio']);
        $hora_fin = new DateTime('1970-01-01 ' . $medico['hora_fin']);
        $hora_cita = new DateTime('1970-01-01 ' . $hora);
        
        if ($hora_cita < $hora_inicio || $hora_cita > $hora_fin) {
            return [
                'valido' => false,
                'mensaje' => "El médico atiende de " . $medico['hora_inicio'] . " a " . $medico['hora_fin']
            ];
        }
        
        // Validar día de la semana
        $dias = array_map('trim', explode(',', $medico['dias_trabajo']));
        $dia_semana = date('l', strtotime($fecha));
        
        $mapa_dias = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miercoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sabado',
            'Sunday' => 'Domingo'
        ];
        
        $dia_nombre = $mapa_dias[$dia_semana] ?? $dia_semana;
        
        if (!in_array($dia_nombre, $dias)) {
            return [
                'valido' => false,
                'mensaje' => "El médico no atiende los " . strtolower($dia_nombre) . "s"
            ];
        }
        
        return ['valido' => true, 'mensaje' => ''];
        
    } catch (Exception $e) {
        error_log("Error validando horario: " . $e->getMessage());
        return ['valido' => false, 'mensaje' => 'Error en validación'];
    }
}

/**
 * Prevenir fuerza bruta: bloquear usuario después de X intentos
 */
function registrarIntentofallido($pdo, $email) {
    try {
        $stmt = $pdo->prepare("SELECT intentos_fallidos, bloqueado_hasta FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            return ['bloqueado' => false];
        }
        
        // Si está bloqueado, verificar si pasó la hora
        if ($usuario['bloqueado_hasta']) {
            $bloqueado_hasta = new DateTime($usuario['bloqueado_hasta']);
            $ahora = new DateTime();
            
            if ($ahora < $bloqueado_hasta) {
                return [
                    'bloqueado' => true,
                    'minutos_restantes' => ceil($ahora->diff($bloqueado_hasta)->s / 60)
                ];
            }
            
            // Desbloquear después de 30 minutos
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = ?");
            $stmt->execute([$email]);
        }
        
        // Incrementar intentos
        $nuevos_intentos = ($usuario['intentos_fallidos'] ?? 0) + 1;
        $bloqueado_hasta = null;
        
        if ($nuevos_intentos >= 5) {
            $fecha_bloqueo = new DateTime();
            $fecha_bloqueo->add(new DateInterval('PT30M')); // 30 minutos
            $bloqueado_hasta = $fecha_bloqueo->format('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE email = ?");
            $stmt->execute([$nuevos_intentos, $bloqueado_hasta, $email]);
            
            return [
                'bloqueado' => true,
                'minutos_restantes' => 30
            ];
        }
        
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE email = ?");
        $stmt->execute([$nuevos_intentos, $email]);
        
        return ['bloqueado' => false];
        
    } catch (Exception $e) {
        error_log("Error registrando intento fallido: " . $e->getMessage());
        return ['bloqueado' => false];
    }
}

/**
 * Limpiar intentos fallidos al login exitoso
 */
function limpiarIntentosFallidos($pdo, $email) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = ?");
        $stmt->execute([$email]);
    } catch (Exception $e) {
        error_log("Error limpiando intentos: " . $e->getMessage());
    }
}

/**
 * Sanitizar entrada HTML
 */
function sanitizar($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Generar token seguro para verificación
 */
function generarToken() {
    return bin2hex(random_bytes(32));
}

?>
