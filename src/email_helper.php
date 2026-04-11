<?php
/**
 * Helper para enviar emails de notificaciones
 * Soporta: confirmación de citas, recordatorios, cancelaciones
 */

// Configuración de email (cambiar según tu servidor)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@citasmedicas.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Sistema de Citas Médicas');

/**
 * Enviar email de confirmación de cita
 */
function enviarEmailConfirmacionCita($email_paciente, $nombre_paciente, $medico_nombre, $fecha, $hora, $motivo) {
    $asunto = "✅ Cita Confirmada - Sistema de Citas Médicas";
    
    $mensaje = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: #e8f4f8; border-left: 4px solid #0284c7; padding: 15px; margin: 15px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Cita Confirmada!</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>$nombre_paciente</strong>,</p>
                    <p>Tu cita ha sido confirmada exitosamente. A continuación encontrarás los detalles:</p>
                    
                    <div class='info-box'>
                        <p><strong>Médico:</strong> $medico_nombre</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($fecha)) . "</p>
                        <p><strong>Hora:</strong> $hora</p>
                        <p><strong>Motivo:</strong> $motivo</p>
                    </div>
                    
                    <p>Por favor, llega 10 minutos antes de tu cita. Si no puedes asistir, comunícate con nosotros lo antes posible.</p>
                    
                    <p>Gracias por confiar en nuestros servicios.</p>
                    
                    <p>Saludos cordiales,<br><strong>Sistema de Citas Médicas</strong></p>
                    
                    <div class='footer'>
                        <p>Este es un email automático, no respondas a este mensaje.</p>
                    </div>
                </div>
            </div>
        </body>
    </html>";
    
    return enviarEmail($email_paciente, $asunto, $mensaje, 'confirmacion');
}

/**
 * Enviar recordatorio de cita (24 horas antes)
 */
function enviarEmailRecordatorioCita($email_paciente, $nombre_paciente, $medico_nombre, $fecha, $hora) {
    $asunto = "⏰ Recordatorio de tu Cita Médica";
    
    $mensaje = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: #f59e0b; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Recordatorio de Cita</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>$nombre_paciente</strong>,</p>
                    <p>Te recordamos que tienes una cita programada mañana:</p>
                    
                    <div class='info-box'>
                        <p><strong>Médico:</strong> $medico_nombre</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($fecha)) . "</p>
                        <p><strong>Hora:</strong> $hora</p>
                    </div>
                    
                    <p>Por favor, llega 10 minutos antes. Si necesitas cancelar, avísanos cuanto antes.</p>
                    
                    <p>¡Esperamos verte pronto!</p>
                    
                    <div class='footer'>
                        <p>Este es un email automático, no respondas a este mensaje.</p>
                    </div>
                </div>
            </div>
        </body>
    </html>";
    
    return enviarEmail($email_paciente, $asunto, $mensaje, 'recordatorio');
}

/**
 * Enviar notificación de cancelación
 */
function enviarEmailCancelacionCita($email_paciente, $nombre_paciente, $medico_nombre, $fecha, $hora, $motivo_cancelacion = '') {
    $asunto = "❌ Cita Cancelada - Sistema de Citas Médicas";
    
    $motivo_texto = $motivo_cancelacion ? "<p><strong>Motivo:</strong> $motivo_cancelacion</p>" : '';
    
    $mensaje = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: #ef4444; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Cita Cancelada</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>$nombre_paciente</strong>,</p>
                    <p>Tu cita ha sido cancelada:</p>
                    
                    <div class='info-box'>
                        <p><strong>Médico:</strong> $medico_nombre</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($fecha)) . "</p>
                        <p><strong>Hora:</strong> $hora</p>
                        $motivo_texto
                    </div>
                    
                    <p>Si deseas agendar una nueva cita, por favor accede a nuestro sistema.</p>
                    
                    <div class='footer'>
                        <p>Este es un email automático, no respondas a este mensaje.</p>
                    </div>
                </div>
            </div>
        </body>
    </html>";
    
    return enviarEmail($email_paciente, $asunto, $mensaje, 'cancelacion');
}

/**
 * Notificar al médico sobre nueva cita
 */
function enviarEmailNuevaCitaMedico($email_medico, $nombre_medico, $paciente_nombre, $paciente_email, $fecha, $hora, $motivo) {
    $asunto = "📋 Nueva Cita Agendada";
    
    $mensaje = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: #10b981; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nueva Cita Agendada</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>$nombre_medico</strong>,</p>
                    <p>Tienes una nueva cita agendada:</p>
                    
                    <div class='info-box'>
                        <p><strong>Paciente:</strong> $paciente_nombre</p>
                        <p><strong>Email:</strong> $paciente_email</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($fecha)) . "</p>
                        <p><strong>Hora:</strong> $hora</p>
                        <p><strong>Motivo:</strong> $motivo</p>
                    </div>
                    
                    <div class='footer'>
                        <p>Este es un email automático, no respondas a este mensaje.</p>
                    </div>
                </div>
            </div>
        </body>
    </html>";
    
    return enviarEmail($email_medico, $asunto, $mensaje, 'nueva_cita_medico');
}

/**
 * Función genérica para enviar emails
 * Usa mail() por defecto (más compatible que SMTP en servidores compartidos)
 */
function enviarEmail($destinatario, $asunto, $mensaje_html, $tipo = 'general') {
    global $pdo;
    
    // Headers para HTML
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    
    // Intentar enviar
    $email_enviado = false;
    $error_mensaje = '';
    
    try {
        $email_enviado = @mail($destinatario, $asunto, $mensaje_html, $headers);
        
        if (!$email_enviado) {
            $error_mensaje = 'Error al enviar email con mail()';
        }
    } catch (Exception $e) {
        $error_mensaje = $e->getMessage();
    }
    
    // Registrar intento en BD
    if (isset($pdo)) {
        try {
            $gid = (isset($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : null;
            
            $stmt = $pdo->prepare("INSERT INTO email_notificaciones 
                (tipo, cita_id, destinatario, asunto, enviado, error_mensaje, fecha_exito)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $fecha_exito = $email_enviado ? date('Y-m-d H:i:s') : null;
            
            $stmt->execute([
                $tipo,
                $gid,
                $destinatario,
                $asunto,
                $email_enviado ? 1 : 0,
                $email_enviado ? null : $error_mensaje,
                $fecha_exito
            ]);
        } catch (Exception $e) {
            error_log("Error registrando email: " . $e->getMessage());
        }
    }
    
    return $email_enviado;
}

?>
