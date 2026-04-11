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
    ORDER BY FIELD(c.estado, 'pendiente', 'confirmada', 'completada'), c.fecha ASC, c.hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $medico_id);
$stmt->bindParam(2, $today);
$stmt->execute();
$citas = $stmt->fetchAll();
$citas_proximas = array_slice($citas, 0, 6);

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
    LIMIT 6");
$stmtUltimos->execute();
$expedientes_recientes = $stmtUltimos->fetchAll();

$proxima_cita = $citas ? ($citas[0] ?? null) : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - Sistema de Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.medico-dashboard {
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(2, 132, 199, 0.10), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
        }

        .medico-layout {
            display: grid;
            gap: 24px;
        }

        .hero-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0ea5e9 100%);
            border-radius: 28px;
            color: #fff;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
        }

        .hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 45%);
            pointer-events: none;
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            align-items: stretch;
        }

        .hero-copy h2 {
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.05;
            margin: 0 0 10px 0;
            color: #fff;
        }

        .hero-copy p {
            color: rgba(255,255,255,0.9);
            margin: 0 0 18px 0;
            max-width: 62ch;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .hero-card {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 20px;
            padding: 16px;
            backdrop-filter: blur(10px);
        }

        .hero-card .label {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .hero-card .value {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .metric-card {
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(226,232,240,0.9);
            border-radius: 22px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        }

        .metric-card .metric-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
            color: #fff;
        }

        .metric-card .metric-label {
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-card .metric-value {
            font-size: 2rem;
            font-weight: 900;
            color: #0f172a;
            margin-top: 6px;
        }

        .metric-card .metric-note {
            color: #64748b;
            margin-top: 6px;
            font-size: 0.92rem;
        }

        .section-card {
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(226,232,240,0.9);
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .section-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.15rem;
        }

        .section-head p {
            margin: 6px 0 0;
            color: #64748b;
        }

        .appointment-list {
            display: grid;
            gap: 14px;
        }

        .appointment-item {
            display: grid;
            grid-template-columns: 110px 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }

        .appointment-date {
            text-align: center;
            background: #eff6ff;
            border-radius: 16px;
            padding: 12px 10px;
        }

        .appointment-date .day {
            display: block;
            font-size: 1.4rem;
            font-weight: 900;
            color: #1d4ed8;
            line-height: 1;
        }

        .appointment-date .month {
            display: block;
            margin-top: 4px;
            font-size: 0.82rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .appointment-title {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .appointment-meta {
            color: #64748b;
            font-size: 0.92rem;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .appointment-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .appointment-badge.pending { background: #fef3c7; color: #92400e; }
        .appointment-badge.completed { background: #dcfce7; color: #166534; }
        .appointment-badge.cancelled { background: #fee2e2; color: #991b1b; }

        .quick-links {
            display: grid;
            gap: 12px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .quick-link i {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .quick-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .expediente-card-list {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .soft-muted {
            color: #64748b;
        }

        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .hero-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .appointment-item { grid-template-columns: 1fr; }
            .section-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body class="medico-dashboard">
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
            <div class="medico-layout">
                <section class="hero-panel">
                    <div class="hero-grid">
                        <div class="hero-copy">
                            <span class="soft-badge" style="background: rgba(255,255,255,0.14); color:#fff; margin-bottom: 14px;">
                                <i class="fas fa-user-md"></i> Panel médico
                            </span>
                            <h2>Dr. <?php echo htmlspecialchars($medico['nombre']); ?></h2>
                            <p><?php echo htmlspecialchars($medico['especialidad']); ?> · Gestión clínica diaria, atención de citas y expedientes en un solo lugar.</p>
                            <div class="hero-actions">
                                <a href="expedientes.php" class="btn btn-light btn-small"><i class="fas fa-notes-medical"></i> Ver expedientes</a>
                                <a href="logout.php" class="btn btn-outline btn-small" style="border-color: rgba(255,255,255,0.45); color:#fff;"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
                            </div>
                        </div>

                        <div class="hero-meta">
                            <div class="hero-card">
                                <span class="label">Cita siguiente</span>
                                <div class="value"><?php echo $proxima_cita ? substr((string)$proxima_cita['hora'], 0, 5) : '--:--'; ?></div>
                                <div class="soft-muted"><?php echo $proxima_cita ? htmlspecialchars($proxima_cita['paciente_nombre'] ?: $proxima_cita['paciente_email']) : 'Sin citas próximas'; ?></div>
                            </div>
                            <div class="hero-card">
                                <span class="label">Agenda</span>
                                <div class="value"><?php echo date('d/m'); ?></div>
                                <div class="soft-muted">Jornada actual</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dashboard-grid">
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="metric-label">Citas hoy</div>
                        <div class="metric-value"><?php echo (int)$stats['citas_hoy']; ?></div>
                        <div class="metric-note">Programadas para atender</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="metric-label">Pendientes</div>
                        <div class="metric-value"><?php echo (int)$stats['citas_pendientes']; ?></div>
                        <div class="metric-note">Esperando atención</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="metric-label">Completadas</div>
                        <div class="metric-value"><?php echo (int)$stats['citas_completadas']; ?></div>
                        <div class="metric-note">Consultas cerradas</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-folder-medical"></i></div>
                        <div class="metric-label">Total citas</div>
                        <div class="metric-value"><?php echo (int)$stats['citas_totales']; ?></div>
                        <div class="metric-note">Histórico acumulado</div>
                    </div>
                </section>

                <section class="section-card">
                    <div class="section-head">
                        <div>
                            <h3>Acciones rápidas</h3>
                            <p>Accesos directos a lo que más usas en consulta</p>
                        </div>
                    </div>
                    <div class="quick-links">
                        <a href="expedientes.php" class="quick-link"><i class="fas fa-notes-medical"></i><div><strong>Expedientes clínicos</strong><div class="soft-muted">Revisar y editar historias</div></div></a>
                        <a href="medico_panel.php" class="quick-link"><i class="fas fa-clipboard-list"></i><div><strong>Agenda clínica</strong><div class="soft-muted">Ver citas activas</div></div></a>
                        <a href="logout.php" class="quick-link"><i class="fas fa-right-from-bracket"></i><div><strong>Salir</strong><div class="soft-muted">Cerrar sesión del médico</div></div></a>
                    </div>
                </section>

                <section class="section-card">
                    <div class="section-head">
                        <div>
                            <h3>Próximas citas</h3>
                            <p>Ordenadas por prioridad clínica y horario</p>
                        </div>
                        <span class="soft-badge"><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y'); ?></span>
                    </div>

                    <?php if (count($citas_proximas) === 0): ?>
                        <div class="empty-state">No tienes citas programadas.</div>
                    <?php else: ?>
                        <div class="appointment-list">
                            <?php foreach ($citas_proximas as $cita): ?>
                                <?php
                                    $estadoClass = $cita['estado'] === 'completada' ? 'completed' : ($cita['estado'] === 'cancelada' ? 'cancelled' : 'pending');
                                    $fecha = strtotime($cita['fecha']);
                                ?>
                                <article class="appointment-item">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', $fecha); ?></span>
                                        <span class="month"><?php echo date('M', $fecha); ?></span>
                                    </div>
                                    <div>
                                        <div class="appointment-title"><?php echo htmlspecialchars($cita['paciente_nombre'] ?: $cita['paciente_email']); ?></div>
                                        <div class="appointment-meta">
                                            <span><i class="fas fa-clock"></i> <?php echo substr((string)$cita['hora'], 0, 5); ?></span>
                                            <span><i class="fas fa-comment-medical"></i> <?php echo htmlspecialchars($cita['motivo']); ?></span>
                                        </div>
                                    </div>
                                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                                        <span class="appointment-badge <?php echo $estadoClass; ?>"><?php echo ucfirst($cita['estado']); ?></span>
                                        <?php if ($cita['estado'] != 'completada'): ?>
                                            <a href="atender_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-primary btn-small">Atender</a>
                                        <?php else: ?>
                                            <span class="soft-muted">Completada</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="section-card">
                    <div class="section-head">
                        <div>
                            <h3>Expedientes recientes</h3>
                            <p>Últimos registros creados en el sistema</p>
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
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>