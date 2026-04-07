<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener médicos
$medicos = $pdo->query("SELECT * FROM medicos ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos - Sistema de Citas Médicas</title>
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
                <li><a href="mis-citas.php"><i class="fas fa-calendar-check"></i> Mis Citas</a></li>
                <li><a href="medicos.php" class="active"><i class="fas fa-user-md"></i> Médicos</a></li>
            </ul>
        </nav>

        <main>
            <section class="medicos-section">
                <h2><i class="fas fa-stethoscope"></i> Nuestros Médicos Especialistas</h2>
                <p class="text-center mb-4">Contamos con profesionales altamente calificados para tu atención médica</p>
                
                <div class="medicos-grid">
                    <?php while($medico = $medicos->fetch()): ?>
                    <div class="medico-card">
                        <div class="medico-header">
                            <h3><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($medico['nombre']); ?></h3>
                            <p class="especialidad"><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($medico['especialidad']); ?></p>
                        </div>
                        <div class="medico-info">
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($medico['telefono']); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($medico['email']); ?></p>
                        </div>
                        <a href="agendar-cita.php?medico_id=<?php echo $medico['id']; ?>" class="btn btn-primary btn-small">
                            <i class="fas fa-calendar-plus"></i> Agendar Cita
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
