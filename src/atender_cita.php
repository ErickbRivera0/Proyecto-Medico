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

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS expediente_consultas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        expediente_id INT NOT NULL,
        cita_id INT DEFAULT NULL,
        medico_id INT DEFAULT NULL,
        medico_nombre VARCHAR(255) DEFAULT NULL,
        motivo_consulta TEXT,
        diagnostico TEXT,
        tratamiento TEXT,
        observaciones TEXT,
        presion_arterial VARCHAR(20) DEFAULT NULL,
        temperatura VARCHAR(10) DEFAULT NULL,
        frecuencia_cardiaca VARCHAR(10) DEFAULT NULL,
        saturacion_oxigeno VARCHAR(10) DEFAULT NULL,
        fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expediente_fecha (expediente_id, fecha_consulta),
        INDEX idx_cita (cita_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Ignorar si la tabla ya existe o no puede crearse en este entorno
}

$consultas_recientes = [];
if ($expediente && !empty($expediente['id'])) {
    $stmtConsultas = $pdo->prepare("SELECT fecha_consulta, medico_nombre, diagnostico, tratamiento,
                                           presion_arterial, temperatura, frecuencia_cardiaca, saturacion_oxigeno
                                    FROM expediente_consultas
                                    WHERE expediente_id = ?
                                    ORDER BY fecha_consulta DESC
                                    LIMIT 5");
    $stmtConsultas->bindParam(1, $expediente['id'], PDO::PARAM_INT);
    $stmtConsultas->execute();
    $consultas_recientes = $stmtConsultas->fetchAll();
}

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

                <div class="expediente-info" style="margin-top: 1rem;">
                    <h3>Consultas Recientes</h3>
                    <?php if (count($consultas_recientes) === 0): ?>
                        <p>No hay consultas registradas en historial estructurado.</p>
                    <?php else: ?>
                        <?php foreach ($consultas_recientes as $consulta): ?>
                            <div style="padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:10px;">
                                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])); ?></p>
                                <p><strong>Médico:</strong> <?php echo htmlspecialchars($consulta['medico_nombre'] ?: 'No especificado'); ?></p>
                                <p><strong>Signos vitales:</strong>
                                    PA <?php echo htmlspecialchars($consulta['presion_arterial'] ?: '-'); ?>,
                                    Temp <?php echo htmlspecialchars($consulta['temperatura'] ?: '-'); ?>,
                                    FC <?php echo htmlspecialchars($consulta['frecuencia_cardiaca'] ?: '-'); ?>,
                                    SpO2 <?php echo htmlspecialchars($consulta['saturacion_oxigeno'] ?: '-'); ?>
                                </p>
                                <p><strong>Diagnóstico:</strong> <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?: '')); ?></p>
                                <p><strong>Tratamiento:</strong> <?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?: '')); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No se encontró expediente para este paciente. Se creará uno nuevo.</div>
                <?php endif; ?>

                <h3>Agregar Registro Médico</h3>
                <form action="guardar_expediente.php" method="POST">
                    <input type="hidden" name="cita_id" value="<?php echo $cita_id; ?>">
                    <input type="hidden" name="paciente_email" value="<?php echo htmlspecialchars($cita['paciente_email']); ?>">

                    <div class="form-group">
                        <label for="motivo_consulta">Motivo de consulta:</label>
                        <textarea id="motivo_consulta" name="motivo_consulta" rows="2"><?php echo htmlspecialchars($cita['motivo']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="presion_arterial">Presión arterial:</label>
                        <input type="text" id="presion_arterial" name="presion_arterial" placeholder="Ej. 120/80 mmHg">
                    </div>
                    <div class="form-group">
                        <label for="temperatura">Temperatura:</label>
                        <input type="text" id="temperatura" name="temperatura" placeholder="Ej. 36.8 °C">
                    </div>
                    <div class="form-group">
                        <label for="frecuencia_cardiaca">Frecuencia cardiaca:</label>
                        <input type="text" id="frecuencia_cardiaca" name="frecuencia_cardiaca" placeholder="Ej. 76 lpm">
                    </div>
                    <div class="form-group">
                        <label for="saturacion_oxigeno">Saturación de oxígeno:</label>
                        <input type="text" id="saturacion_oxigeno" name="saturacion_oxigeno" placeholder="Ej. 98%">
                    </div>

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