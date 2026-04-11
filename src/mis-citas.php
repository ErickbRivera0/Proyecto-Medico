<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['email'];

// Parámetros de filtro
$filtro_estado = trim($_GET['estado'] ?? '');
$filtro_medico = trim($_GET['medico'] ?? '');
$filtro_fecha_desde = trim($_GET['fecha_desde'] ?? '');
$filtro_fecha_hasta = trim($_GET['fecha_hasta'] ?? '');

// Construir query dinámicamente
$sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
        FROM citas c 
        JOIN medicos m ON c.medico_id = m.id 
        WHERE c.paciente_email = ?";
$params = [$user_email];

if (!empty($filtro_estado)) {
    $sql .= " AND c.estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($filtro_medico)) {
    $sql .= " AND LOWER(m.nombre) LIKE ?";
    $params[] = '%' . strtolower($filtro_medico) . '%';
}

if (!empty($filtro_fecha_desde)) {
    $sql .= " AND c.fecha >= ?";
    $params[] = $filtro_fecha_desde;
}

if (!empty($filtro_fecha_hasta)) {
    $sql .= " AND c.fecha <= ?";
    $params[] = $filtro_fecha_hasta;
}

$sql .= " ORDER BY c.fecha DESC, c.hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - Sistema de Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <h1><i class="fas fa-calendar-check"></i> Citas Médicas</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="agendar-cita.php"><i class="fas fa-calendar-plus"></i> Agendar Cita</a></li>
                <li><a href="mis-citas.php" class="active"><i class="fas fa-calendar-check"></i> Mis Citas</a></li>
                <li><a href="medicos.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="expedientes.php"><i class="fas fa-notes-medical"></i> Expedientes</a></li>
            </ul>
        </nav>

        <main>
            <section class="citas-tabla">
                <h2><i class="fas fa-calendar-alt"></i> Mis Citas Médicas</h2>
                
                <!-- Filtros -->
                <div style="background: white; border-radius: 8px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <form method="GET" action="mis-citas.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: flex-end;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a;"><i class="fas fa-filter"></i> Estado:</label>
                            <select name="estado" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <option value="">Todos</option>
                                <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="confirmada" <?php echo $filtro_estado === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="completada" <?php echo $filtro_estado === 'completada' ? 'selected' : ''; ?>>Completada</option>
                                <option value="cancelada" <?php echo $filtro_estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a;"><i class="fas fa-user-md"></i> Médico:</label>
                            <input type="text" name="medico" placeholder="Buscar médico..." value="<?php echo htmlspecialchars($filtro_medico); ?>" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a;"><i class="fas fa-calendar"></i> Desde:</label>
                            <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a;"><i class="fas fa-calendar"></i> Hasta:</label>
                            <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>" style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" style="flex: 1; background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%); color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="mis-citas.php" style="background: #e5e7eb; color: #0f172a; padding: 8px 12px; border-radius: 6px; text-decoration: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if ($citas->rowCount() == 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No tienes citas que coincidan con los filtros. 
                        <a href="agendar-cita.php">Agenda una nueva cita aquí</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Médico</th>
                                    <th>Especialidad</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($cita = $citas->fetch()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><?php echo $cita['hora']; ?></td>
                                    <td><?php echo htmlspecialchars($cita['medico_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['especialidad']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['motivo']); ?></td>
                                    <td>
                                        <span class="estado <?php echo strtolower($cita['estado']); ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($cita['estado'] == 'pendiente' && strtotime($cita['fecha']) >= strtotime(date('Y-m-d'))): ?>
                                            <a href="cancelar_cita.php?id=<?php echo $cita['id']; ?>" 
                                               class="btn btn-danger btn-small">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                            $citaPath = __DIR__ . '/exports/cita_' . $cita['id'] . '.pdf';
                                            $citaLink = file_exists($citaPath) ? 'exports/cita_' . $cita['id'] . '.pdf' : '';
                                        ?>

                                        <?php if ($citaLink): ?>
                                            <a href="<?php echo $citaLink; ?>" class="btn btn-success btn-small" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Cita (PDF)
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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
