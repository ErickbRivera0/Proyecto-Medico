<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['email'];

// Obtener citas del usuario
$stmt = $pdo->prepare("SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                        FROM citas c 
                        JOIN medicos m ON c.medico_id = m.id 
                        WHERE c.paciente_email = ? 
                        ORDER BY c.fecha DESC, c.hora DESC");
$stmt->bindParam(1, $user_email);
$stmt->execute();
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
                
                <?php if ($citas->rowCount() == 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No tienes citas registradas. 
                        <a href="agendar-cita.php">Agenda tu primera cita aquí</a>
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
                                            <a href="cancelar-cita.php?id=<?php echo $cita['id']; ?>" 
                                               class="btn btn-danger btn-small" 
                                               onclick="return confirm('¿Estás seguro de cancelar esta cita?')">
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
