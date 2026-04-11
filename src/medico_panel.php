<?php
session_start();

// Verificar autenticación y rol
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'medico') {
    header("Location: medico_login.php");
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/models/Cita.php';

$medico_model = new Medico($pdo);
$cita_model = new Cita($pdo);

$medico_id = $_SESSION['id'];
$medico = $medico_model->obtener_por_id($medico_id);

if (!$medico) {
    // Si no se encuentra el médico en la base de datos, cerrar sesión y regresar al login médico.
    session_unset();
    session_destroy();
    header("Location: medico_login.php");
    exit();
}

// Obtener citas del día y futuras
$today = date('Y-m-d');
$sql = "SELECT c.*, e.nombre as paciente_nombre FROM citas c 
        LEFT JOIN expedientes e ON c.paciente_email = e.paciente_email 
        WHERE c.medico_id = ? AND c.fecha >= ? AND c.estado != 'cancelada' 
        ORDER BY c.fecha ASC, c.hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $medico_id);
$stmt->bindParam(2, $today);
$stmt->execute();
$citas = $stmt;

$stmtStats = $pdo->prepare("SELECT
    SUM(CASE WHEN fecha = CURDATE() AND estado != 'cancelada' THEN 1 ELSE 0 END) AS citas_hoy,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS citas_pendientes,
    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) AS citas_completadas,
    COUNT(*) AS citas_totales
FROM citas
WHERE medico_id = ?");
$stmtStats->bindParam(1, $medico_id);
$stmtStats->execute();
$stats = $stmtStats->fetch() ?: ['citas_hoy' => 0, 'citas_pendientes' => 0, 'citas_completadas' => 0, 'citas_totales' => 0];

$stmtUltimos = $pdo->prepare("SELECT id, paciente_email, nombre, fecha_registro
    FROM expedientes
    ORDER BY fecha_registro DESC
    LIMIT 4");
$stmtUltimos->execute();
$expedientes_recientes = $stmtUltimos->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - Sistema de Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <h1><i class="fas fa-user-md"></i> Panel Médico</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="medico_panel.php" class="active"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>

        <main>
            <section class="page-hero">
                <div class="section-heading" style="margin-bottom:10px;">
                    <div>
                        <h2>Panel Médico</h2>
                        <p>Dr. <?php echo htmlspecialchars($medico['nombre']); ?> · <?php echo htmlspecialchars($medico['especialidad']); ?></p>
                    </div>
                    <span class="soft-badge"><i class="fas fa-stethoscope"></i> Atención clínica</span>
                </div>
                <p>Gestión diaria de citas, expedientes y atención médica con acceso restringido al personal médico.</p>
            </section>

            <section class="clinical-grid">
                <div class="clinical-metric">
                    <div class="metric-label">Citas hoy</div>
                    <div class="metric-value"><?php echo (int)$stats['citas_hoy']; ?></div>
                    <div class="metric-subtitle">Programadas para la jornada</div>
                </div>
                <div class="clinical-metric">
                    <div class="metric-label">Pendientes</div>
                    <div class="metric-value"><?php echo (int)$stats['citas_pendientes']; ?></div>
                    <div class="metric-subtitle">Por atender</div>
                </div>
                <div class="clinical-metric">
                    <div class="metric-label">Completadas</div>
                    <div class="metric-value"><?php echo (int)$stats['citas_completadas']; ?></div>
                    <div class="metric-subtitle">Atenciones cerradas</div>
                </div>
                <div class="clinical-metric">
                    <div class="metric-label">Total citas</div>
                    <div class="metric-value"><?php echo (int)$stats['citas_totales']; ?></div>
                    <div class="metric-subtitle">Histórico del médico</div>
                </div>
            </section>

            <section class="panel-section">
                <div class="section-heading">
                    <div>
                        <h3>Próximas citas</h3>
                        <p class="patient-meta">Ordenadas por fecha y hora</p>
                    </div>
                    <span class="soft-badge"><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y'); ?></span>
                </div>

                <?php if ($citas->rowCount() == 0): ?>
                    <div class="empty-state">No tienes citas programadas.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($cita = $citas->fetch()): ?>
                                <tr>
                                    <td>
                                        <div class="patient-name"><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></div>
                                        <div class="patient-meta"><?php echo date('l', strtotime($cita['fecha'])); ?></div>
                                    </td>
                                    <td><?php echo substr((string)$cita['hora'], 0, 5); ?></td>
                                    <td>
                                        <div class="patient-name"><?php echo htmlspecialchars($cita['paciente_nombre'] ?: $cita['paciente_email']); ?></div>
                                        <div class="patient-meta"><?php echo htmlspecialchars($cita['paciente_email']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($cita['motivo']); ?></td>
                                    <td>
                                        <?php $estadoClass = $cita['estado'] === 'completada' ? 'completed' : ($cita['estado'] === 'cancelada' ? 'cancelled' : 'pending'); ?>
                                        <span class="status-pill <?php echo $estadoClass; ?>"><?php echo ucfirst($cita['estado']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <?php if ($cita['estado'] != 'completada'): ?>
                                                <a href="atender_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-primary btn-small">Atender</a>
                                            <?php else: ?>
                                                <span class="status-pill completed">Completada</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel-section">
                <div class="section-heading">
                    <div>
                        <h3>Expedientes recientes</h3>
                        <p class="patient-meta">Últimos registros creados en el sistema</p>
                    </div>
                    <a href="expedientes.php" class="btn btn-outline btn-small">Ver todos</a>
                </div>

                <?php if (count($expedientes_recientes) === 0): ?>
                    <div class="empty-state">Aún no hay expedientes registrados.</div>
                <?php else: ?>
                    <div class="expediente-card-list">
                        <?php foreach ($expedientes_recientes as $expediente): ?>
                            <article class="expediente-card">
                                <div class="expediente-title"><?php echo htmlspecialchars($expediente['nombre'] ?: 'Sin nombre'); ?></div>
                                <div class="expediente-subtitle"><?php echo htmlspecialchars($expediente['paciente_email']); ?></div>
                                <div class="expediente-row"><span>ID</span><strong>#<?php echo $expediente['id']; ?></strong></div>
                                <div class="expediente-row"><span>Creado</span><strong><?php echo date('d/m/Y H:i', strtotime($expediente['fecha_registro'])); ?></strong></div>
                                <div class="expediente-actions">
                                    <a href="expediente_ver.php?id=<?php echo $expediente['id']; ?>" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i> Ver</a>
                                    <a href="generar-expediente-pdf.php?id=<?php echo $expediente['id']; ?>" class="btn btn-success btn-small"><i class="fas fa-file-pdf"></i> PDF</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>