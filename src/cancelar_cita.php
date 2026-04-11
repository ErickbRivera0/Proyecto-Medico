<?php
session_start();
require_once 'config.php';
require_once 'email_helper.php';

// Verificar que sea paciente autenticado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['email'])) {
    header("Location: login.php?error=no_autenticado");
    exit();
}

// Obtener ID de cita
$cita_id = (int)($_GET['id'] ?? $_POST['cita_id'] ?? 0);

if ($cita_id <= 0) {
    header("Location: mis-citas.php?error=cita_no_valida");
    exit();
}

// Obtener datos de la cita
try {
    $stmt = $pdo->prepare("
        SELECT c.*, m.nombre as medico_nombre, m.email as medico_email
        FROM citas c
        LEFT JOIN medicos m ON c.medico_id = m.id
        WHERE c.id = ? AND c.paciente_email = ?
    ");
    $stmt->execute([$cita_id, $_SESSION['email']]);
    $cita = $stmt->fetch();
    
    if (!$cita) {
        header("Location: mis-citas.php?error=cita_no_encontrada");
        exit();
    }
} catch (Exception $e) {
    header("Location: mis-citas.php?error=error_consulta");
    exit();
}

// Procesar cancelación
$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo_cancelacion = trim($_POST['motivo_cancelacion'] ?? 'Sin especificar');
    
    try {
        // Validar que la cita no esté ya cancelada
        if ($cita['estado'] === 'cancelada') {
            $mensaje_error = "Esta cita ya fue cancelada anteriormente";
        } elseif ($cita['estado'] === 'completada') {
            $mensaje_error = "No se puede cancelar una cita ya completada";
        } else {
            // Actualizar estado
            $stmt = $pdo->prepare("
                UPDATE citas 
                SET estado = 'cancelada', 
                    cancelada_por = 'paciente',
                    motivo_cancelacion = ?
                WHERE id = ? AND paciente_email = ?
            ");
            
            if ($stmt->execute([$motivo_cancelacion, $cita_id, $_SESSION['email']])) {
                // Enviar email al médico informando la cancelación
                if (!empty($cita['medico_email'])) {
                    enviarEmailCancelacionCita(
                        $cita['medico_email'],
                        $cita['medico_nombre'],
                        $_SESSION['usuario'],
                        $cita['fecha'],
                        $cita['hora'],
                        "Paciente: " . $motivo_cancelacion
                    );
                }
                
                // Enviar confirmación al paciente
                enviarEmailCancelacionCita(
                    $_SESSION['email'],
                    $_SESSION['usuario'],
                    $cita['medico_nombre'],
                    $cita['fecha'],
                    $cita['hora'],
                    $motivo_cancelacion
                );
                
                $mensaje_exito = "✅ Cita cancelada exitosamente. Se ha notificado al médico.";
                
                // Redirigir después de 2 segundos
                header("Refresh: 2; URL=mis-citas.php");
            }
        }
    } catch (Exception $e) {
        $mensaje_error = "❌ Error al cancelar cita: " . $e->getMessage();
        error_log("Error cancelando cita: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Cita</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container { max-width: 600px; margin: 40px auto; }
        .cancel-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .alert-warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .cita-info {
            background: #f3f4f6;
            border-left: 4px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .cita-info p {
            margin: 8px 0;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0f172a;
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-cancel {
            background: #ef4444;
            color: white;
        }
        .btn-cancel:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .btn-volver {
            background: #e5e7eb;
            color: #0f172a;
        }
        .btn-volver:hover {
            background: #d1d5db;
        }
        .success-message {
            display: none;
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cancel-card">
            <h1 style="color: #ef4444; margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i> Cancelar Cita
            </h1>
            
            <?php if (!empty($mensaje_exito)): ?>
            <div class="success-message" style="display: block;">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje_exito; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($mensaje_error)): ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $mensaje_error; ?>
            </div>
            <?php endif; ?>
            
            <div class="alert-warning">
                <i class="fas fa-info-circle"></i>
                <strong>⚠️ Advertencia:</strong> Al cancelar esta cita, se notificará al médico. Esta acción no se puede deshacer.
            </div>
            
            <div class="cita-info">
                <p><strong><i class="fas fa-user-md"></i> Médico:</strong> <?php echo htmlspecialchars($cita['medico_nombre'] ?? 'Sin asignar'); ?></p>
                <p><strong><i class="fas fa-calendar"></i> Fecha:</strong> <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></p>
                <p><strong><i class="fas fa-clock"></i> Hora:</strong> <?php echo $cita['hora']; ?></p>
                <p><strong><i class="fas fa-stethoscope"></i> Motivo:</strong> <?php echo htmlspecialchars($cita['motivo']); ?></p>
                <p><strong><i class="fas fa-info-circle"></i> Estado:</strong> 
                    <span style="background: #fef3c7; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                        <?php echo ucfirst($cita['estado']); ?>
                    </span>
                </p>
            </div>
            
            <form method="POST" action="cancelar_cita.php">
                <input type="hidden" name="cita_id" value="<?php echo $cita_id; ?>">
                
                <div class="form-group">
                    <label for="motivo_cancelacion">
                        <i class="fas fa-comment"></i> Motivo de la cancelación (opcional)
                    </label>
                    <textarea 
                        id="motivo_cancelacion" 
                        name="motivo_cancelacion" 
                        rows="4" 
                        placeholder="Cuéntanos por qué cancelas tu cita. Esto nos ayuda a mejorar nuestros servicios."
                    ></textarea>
                </div>
                
                <div class="btn-group">
                    <a href="mis-citas.php" class="btn btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-cancel" onclick="return confirm('¿Estás seguro de cancelar esta cita?')">
                        <i class="fas fa-trash"></i> Cancelar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
