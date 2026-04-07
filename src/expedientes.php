<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener expedientes
$stmt = $pdo->prepare("SELECT id, paciente_email, nombre, fecha_registro FROM expedientes ORDER BY fecha_registro DESC");
$stmt->execute();
$expedientes = $stmt;
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
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="agendar-cita.php"><i class="fas fa-calendar-plus"></i> Agendar Cita</a></li>
                <li><a href="mis-citas.php"><i class="fas fa-calendar-check"></i> Mis Citas</a></li>
                <li><a href="medicos.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="expedientes.php" class="active"><i class="fas fa-notes-medical"></i> Expedientes</a></li>
            </ul>
        </nav>

        <main>
            <section class="expedientes-section">
                <h2>Lista de Expedientes</h2>
                <p><a href="nuevo-expediente.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Expediente</a></p>

                <?php if ($expedientes->rowCount() == 0): ?>
                    <div class="alert alert-info">No hay expedientes registrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($exp = $expedientes->fetch()): ?>
                                <tr>
                                    <td><?php echo $exp['id']; ?></td>
                                    <td><?php echo htmlspecialchars($exp['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($exp['paciente_email']); ?></td>
                                    <td><?php echo $exp['fecha_registro']; ?></td>
                                    <td>
                                        <a href="expediente_ver.php?id=<?php echo $exp['id']; ?>" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i> Ver</a>
                                        <a href="generar-expediente-pdf.php?id=<?php echo $exp['id']; ?>" class="btn btn-success btn-small"><i class="fas fa-file-pdf"></i> PDF</a>
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
