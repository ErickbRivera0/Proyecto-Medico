<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Solo el medico puede gestionar expedientes clinicos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'medico') {
    header("Location: index.php?error=solo_medico_expediente");
    exit();
}

// Obtener expedientes
$stmt = $pdo->prepare("SELECT id,  nombre, fecha_registro FROM expedientes ORDER BY fecha_registro DESC");
$stmt->execute();
$expedientes = $stmt;

$stmtCount = $pdo->prepare("SELECT COUNT(*) AS total FROM expedientes");
$stmtCount->execute();
$totalExpedientes = (int)($stmtCount->fetch()['total'] ?? 0);

$stmtRecent = $pdo->prepare("SELECT id,  nombre, fecha_registro FROM expedientes ORDER BY fecha_registro DESC LIMIT 3");
$stmtRecent->execute();
$expedientesRecientes = $stmtRecent->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expedientes - Sistema de Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <h1><i class="fas fa-notes-medical"></i> Expedientes</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="medico_panel.php"><i class="fas fa-house-medical"></i> Panel médico</a></li>
                <li><a href="expedientes.php" class="active"><i class="fas fa-notes-medical"></i> Expedientes</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </nav>

        <main>
            <section class="page-hero">
                <div class="section-heading" style="margin-bottom:10px;">
                    <div>
                        <h2>Expedientes clínicos</h2>
                        <p>Listado ordenado por fecha de ingreso, con acceso exclusivo para médicos.</p>
                    </div>
                    <span class="soft-badge"><i class="fas fa-folder-medical"></i> <?php echo $totalExpedientes; ?> registros</span>
                </div>
                <p>Consulta, edición y PDF centralizados para mantener continuidad clínica desde el área médica.</p>
            </section>

            <section class="clinical-grid">
                <div class="clinical-metric">
                    <div class="metric-label">Total de expedientes</div>
                    <div class="metric-value"><?php echo $totalExpedientes; ?></div>
                    <div class="metric-subtitle">Historias clínicas registradas</div>
                </div>
                <div class="clinical-metric">
                    <div class="metric-label">Más recientes</div>
                    <div class="metric-value"><?php echo count($expedientesRecientes); ?></div>
                    <div class="metric-subtitle">Últimos registros visibles</div>
                </div>
                <div class="clinical-metric">
                    <div class="metric-label">Orden</div>
                    <div class="metric-value">DESC</div>
                    <div class="metric-subtitle">Más recientes primero</div>
                </div>
            </section>

            <section class="panel-section">
                <div class="section-heading">
                    <div>
                        <h3>Expedientes ingresados</h3>
                        <p class="patient-meta">Busca por nombre, correo o ID</p>
                    </div>
                    <a href="nuevo-expediente.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo expediente</a>
                </div>

                <div class="search-toolbar">
                    <input type="text" id="expedienteSearch" class="form-group input" placeholder="Escribe nombre, email o ID...">
                </div>

                <?php if ($expedientes->rowCount() == 0): ?>
                    <div class="empty-state">No hay expedientes registrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="compact-table" id="expedientesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Paciente</th>
                                    <th>Email</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($exp = $expedientes->fetch()): ?>
                                <tr>
                                    <td><span class="soft-badge">#<?php echo $exp['id']; ?></span></td>
                                    <td>
                                        <div class="patient-name"><?php echo htmlspecialchars($exp['nombre'] ?: 'Sin nombre'); ?></div>
                                        <div class="patient-meta">Expediente clínico</div>
                                    </td>
                                    <td><?php echo htmlspecialchars($exp['paciente_email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($exp['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="expediente_ver.php?id=<?php echo $exp['id']; ?>" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i> Ver</a>
                                            <a href="generar-expediente-pdf.php?id=<?php echo $exp['id']; ?>" class="btn btn-success btn-small"><i class="fas fa-file-pdf"></i> PDF</a>
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
                        <p class="patient-meta">Acceso rápido a los últimos ingresos</p>
                    </div>
                </div>

                <?php if (count($expedientesRecientes) === 0): ?>
                    <div class="empty-state">Todavía no hay expedientes recientes.</div>
                <?php else: ?>
                    <div class="expediente-card-list">
                        <?php foreach ($expedientesRecientes as $exp): ?>
                            <article class="expediente-card">
                                <div class="expediente-title"><?php echo htmlspecialchars($exp['nombre'] ?: 'Sin nombre'); ?></div>
                                <div class="expediente-subtitle"><?php echo htmlspecialchars($exp['paciente_email']); ?></div>
                                <div class="expediente-row"><span>ID</span><strong>#<?php echo $exp['id']; ?></strong></div>
                                <div class="expediente-row"><span>Fecha</span><strong><?php echo date('d/m/Y H:i', strtotime($exp['fecha_registro'])); ?></strong></div>
                                <div class="expediente-actions">
                                    <a href="expediente_ver.php?id=<?php echo $exp['id']; ?>" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i> Abrir</a>
                                    <a href="generar-expediente-pdf.php?id=<?php echo $exp['id']; ?>" class="btn btn-success btn-small"><i class="fas fa-file-pdf"></i> PDF</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <script>
            (function () {
                var search = document.getElementById('expedienteSearch');
                var table = document.getElementById('expedientesTable');
                if (!search || !table) return;

                search.addEventListener('input', function () {
                    var filter = search.value.toLowerCase().trim();
                    var rows = table.querySelectorAll('tbody tr');

                    rows.forEach(function (row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(filter) !== -1 ? '' : 'none';
                    });
                });
            })();
        </script>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
