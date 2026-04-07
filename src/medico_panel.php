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
            <section class="dashboard">
                <h2>Bienvenido, Dr. <?php echo htmlspecialchars($medico['nombre']); ?></h2>
                <p>Especialidad: <?php echo htmlspecialchars($medico['especialidad']); ?></p>

                <h3>Mis Citas</h3>
                <?php if ($citas->rowCount() == 0): ?>
                    <div class="alert alert-info">No tienes citas programadas.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
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
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><?php echo $cita['hora']; ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_nombre'] ?: $cita['paciente_email']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['motivo']); ?></td>
                                    <td><?php echo ucfirst($cita['estado']); ?></td>
                                    <td>
                                        <?php if ($cita['estado'] != 'completada'): ?>
                                            <a href="atender_cita.php?id=<?php echo $cita['id']; ?>" class="btn btn-primary btn-small">Atender</a>
                                        <?php else: ?>
                                            Completada
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