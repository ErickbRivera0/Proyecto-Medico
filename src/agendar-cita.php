<?php
session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Sistema de Citas Médicas</title>
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
                <li><a href="agendar-cita.php" class="active"><i class="fas fa-calendar-plus"></i> Agendar Cita</a></li>
                <li><a href="mis-citas.php"><i class="fas fa-calendar-check"></i> Mis Citas</a></li>
                <li><a href="medicos.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="expedientes.php"><i class="fas fa-notes-medical"></i> Expedientes</a></li>
            </ul>
        </nav>

        <main>
            <section class="agendar-section">
                <h2><i class="fas fa-calendar-plus"></i> Agendar Nueva Cita</h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="procesar-cita.php" class="form-cita">
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-user"></i> Nombre Completo:</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez" 
                               value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                        <input type="email" id="email" name="email" required placeholder="tu@email.com"
                               value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" required placeholder="123456789">
                    </div>

                    <div class="form-group">
                        <label for="medico_id"><i class="fas fa-user-md"></i> Seleccionar Médico:</label>
                        <select id="medico_id" name="medico_id" required>
                            <option value="">-- Selecciona un médico --</option>
                            <?php
                            $sql = "SELECT id, nombre, especialidad FROM medicos ORDER BY nombre ASC";
                            $resultado = $pdo->query($sql);
                            while ($medico = $resultado->fetch()) {
                                echo "<option value='" . $medico['id'] . "'>" . htmlspecialchars($medico['nombre']) . " - " . htmlspecialchars($medico['especialidad']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha"><i class="fas fa-calendar-day"></i> Fecha de la Cita:</label>
                        <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="hora"><i class="fas fa-clock"></i> Hora de la Cita:</label>
                        <input type="time" id="hora" name="hora" required>
                    </div>

                    <div class="form-group">
                        <label for="motivo"><i class="fas fa-notes-medical"></i> Motivo de la Cita:</label>
                        <textarea id="motivo" name="motivo" required placeholder="Describe el motivo de tu visita"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Agendar Cita
                    </button>
                </form>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
