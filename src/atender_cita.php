<?php
session_start();

// Verificar autenticación y rol
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'medico') {
    header("Location: medico_login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: medico_panel.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Cita.php';

$cita_model = new Cita($pdo);
$cita_id = (int)$_GET['id'];
$cita = $cita_model->obtener_por_id($cita_id);

// Verificar que la cita pertenece al médico
if (!$cita || $cita['medico_id'] != $_SESSION['id']) {
    header("Location: medico_panel.php");
    exit();
}

// Obtener expediente del paciente
$expediente = null;
$stmt = $pdo->prepare("SELECT * FROM expedientes WHERE paciente_email = ? LIMIT 1");
$stmt->bindParam(1, $cita['paciente_email']);
$stmt->execute();
$expediente = $stmt->fetch();

// Asegurar campos adicionales
try {
    $pdo->exec("ALTER TABLE expedientes ADD COLUMN diagnostico TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si la columna ya existe
}

try {
    $pdo->exec("ALTER TABLE expedientes ADD COLUMN tratamiento TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si la columna ya existe
}

try {
    $pdo->exec("ALTER TABLE expedientes ADD COLUMN observaciones TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si la columna ya existe
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atender Cita - Sistema de Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <h1><i class="fas fa-user-md"></i> Atender Cita</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="medico_panel.php"><i class="fas fa-home"></i> Panel</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>

        <main>
            <section>
                <h2>Atender Cita</h2>
                <div class="cita-info">
                    <h3>Información de la Cita</h3>
                    <p><strong>Paciente:</strong> <?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($cita['paciente_email']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($cita['paciente_telefono']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?> a las <?php echo $cita['hora']; ?></p>
                    <p><strong>Motivo:</strong> <?php echo htmlspecialchars($cita['motivo']); ?></p>
                </div>

                <?php if ($expediente): ?>
                <div class="expediente-info">
                    <h3>Historial del Expediente</h3>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($expediente['nombre']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($expediente['telefono']); ?></p>
                    <p><strong>Fecha de Nacimiento:</strong> <?php echo $expediente['fecha_nacimiento'] ? date('d/m/Y', strtotime($expediente['fecha_nacimiento'])) : 'No especificada'; ?></p>
                    <p><strong>Sexo:</strong> <?php echo $expediente['sexo'] == 'M' ? 'Masculino' : ($expediente['sexo'] == 'F' ? 'Femenino' : 'Otro'); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($expediente['direccion'] ?: 'No especificada'); ?></p>
                    <p><strong>Alergias:</strong> <?php echo htmlspecialchars($expediente['alergias'] ?: 'Ninguna'); ?></p>
                    <p><strong>Antecedentes:</strong> <?php echo htmlspecialchars($expediente['antecedentes'] ?: 'Ninguno'); ?></p>
                    <p><strong>Medicamentos Actuales:</strong> <?php echo htmlspecialchars($expediente['medicamentos_actuales'] ?: 'Ninguno'); ?></p>
                    <p><strong>Peso:</strong> <?php echo htmlspecialchars($expediente['peso'] ?: 'No especificado'); ?> kg</p>
                    <p><strong>Altura:</strong> <?php echo htmlspecialchars($expediente['altura'] ?: 'No especificada'); ?> cm</p>
                    <p><strong>Notas:</strong> <?php echo nl2br(htmlspecialchars($expediente['notas'] ?: 'Ninguna')); ?></p>
                    <p><strong>Diagnóstico Anterior:</strong> <?php echo nl2br(htmlspecialchars($expediente['diagnostico'] ?: 'Ninguno')); ?></p>
                    <p><strong>Tratamiento Anterior:</strong> <?php echo nl2br(htmlspecialchars($expediente['tratamiento'] ?: 'Ninguno')); ?></p>
                    <p><strong>Observaciones Anteriores:</strong> <?php echo nl2br(htmlspecialchars($expediente['observaciones'] ?: 'Ninguna')); ?></p>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No se encontró expediente para este paciente. Se creará uno nuevo.</div>
                <?php endif; ?>

                <h3>Agregar Registro Médico</h3>
                <form action="guardar_expediente.php" method="POST">
                    <input type="hidden" name="cita_id" value="<?php echo $cita_id; ?>">
                    <input type="hidden" name="paciente_email" value="<?php echo htmlspecialchars($cita['paciente_email']); ?>">

                    <div class="form-group">
                        <label for="diagnostico">Diagnóstico:</label>
                        <textarea id="diagnostico" name="diagnostico" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tratamiento">Tratamiento:</label>
                        <textarea id="tratamiento" name="tratamiento" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar y Marcar como Atendida</button>
                </form>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>