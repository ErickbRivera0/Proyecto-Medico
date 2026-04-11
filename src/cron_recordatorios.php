<?php
/**
 * Script para enviar recordatorios de citas
 * Ejecutar con cron cada hora: 0 * * * * php /path/to/cron_recordatorios.php
 * 
 * Envía recordatorios 24 horas antes de la cita
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email_helper.php';

// Log para debug
$log = "=== Cron Recordatorios: " . date('Y-m-d H:i:s') . " ===\n";

try {
    // Encontrar citas que vencen en ~24 horas y aún no tienen recordatorio enviado
    $stmt = $pdo->prepare("
        SELECT c.id, c.paciente_email, c.paciente_nombre, c.fecha, c.hora, 
               m.nombre as medico_nombre, c.email_recordatorio_enviado
        FROM citas c
        LEFT JOIN medicos m ON c.medico_id = m.id
        WHERE c.estado IN ('confirmada', 'pendiente')
        AND c.email_recordatorio_enviado = 0
        AND CONCAT(c.fecha, ' ', c.hora) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
        AND CONCAT(c.fecha, ' ', c.hora) > NOW()
        LIMIT 50
    ");
    
    $stmt->execute();
    $citas_recordatorio = $stmt->fetchAll();
    
    $log .= "Citas encontradas para recordatorio: " . count($citas_recordatorio) . "\n";
    
    foreach ($citas_recordatorio as $cita) {
        $enviado = enviarEmailRecordatorioCita(
            $cita['paciente_email'],
            $cita['paciente_nombre'],
            $cita['medico_nombre'],
            $cita['fecha'],
            $cita['hora']
        );
        
        if ($enviado) {
            // Marcar como enviado
            $stmt_update = $pdo->prepare("UPDATE citas SET email_recordatorio_enviado = 1 WHERE id = ?");
            $stmt_update->execute([$cita['id']]);
            $log .= "✓ Recordatorio enviado a: " . $cita['paciente_email'] . "\n";
        } else {
            $log .= "✗ Error enviando recordatorio a: " . $cita['paciente_email'] . "\n";
        }
    }
    
    // Enviar alertas sobre citas sin asistencia (pasadas y en estado confirmada)
    $stmt2 = $pdo->prepare("
        SELECT c.id, c.paciente_email, c.paciente_nombre, c.fecha, c.hora,
               m.nombre as medico_nombre, m.email as medico_email
        FROM citas c
        LEFT JOIN medicos m ON c.medico_id = m.id
        WHERE c.estado = 'confirmada'
        AND c.asistencia = 'sin_confirmar'
        AND CONCAT(c.fecha, ' ', c.hora) < NOW()
        LIMIT 10
    ");
    
    $stmt2->execute();
    $citas_no_asistidas = $stmt2->fetchAll();
    
    $log .= "Citas no asistidas: " . count($citas_no_asistidas) . "\n";
    
    foreach ($citas_no_asistidas as $cita) {
        // Cambiar a cancelada
        $stmt_cancel = $pdo->prepare("
            UPDATE citas 
            SET estado = 'cancelada', asistencia = 'no_asistio'
            WHERE id = ?
        ");
        $stmt_cancel->execute([$cita['id']]);
        
        // Notificar al médico
        if (!empty($cita['medico_email'])) {
            enviarEmailCancelacionCita(
                $cita['medico_email'],
                $cita['medico_nombre'],
                $cita['paciente_nombre'],
                $cita['fecha'],
                $cita['hora'],
                "Paciente no asistió a la cita"
            );
        }
        
        $log .= "✓ Cita marcada como no asistida: " . $cita['id'] . "\n";
    }
    
    // También ejecutar la función de actualización de estados automáticos
    actualizarEstadosCitas($pdo);
    $log .= "✓ Actualización automática de estados completada\n";
    
} catch (Exception $e) {
    $log .= "ERROR: " . $e->getMessage() . "\n";
}

// Guardar log
$log_file = __DIR__ . '/logs/cron_recordatorios.log';
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
file_put_contents($log_file, $log, FILE_APPEND);

// Si se ejecuta desde CLI, mostrar log
if (php_sapi_name() === 'cli') {
    echo $log;
}

?>
