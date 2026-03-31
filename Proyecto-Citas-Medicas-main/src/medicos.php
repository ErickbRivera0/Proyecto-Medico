<?php
require_once 'config.php';

// Obtener todos los médicos
$sql = "SELECT id, nombre, especialidad, telefono, email FROM medicos ORDER BY nombre ASC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos - Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <nav class="navbar">
                <div class="logo">
                    <h1>🏥 Citas Médicas</h1>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="agendar-cita.php">Agendar Cita</a></li>
                    <li><a href="mis-citas.php">Mis Citas</a></li>
                    <li><a href="medicos.php" class="active">Médicos</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="medicos-section">
                <h2>Nuestros Médicos</h2>
                <div class="medicos-grid">
                    <?php
                    if ($resultado->num_rows > 0) {
                        while ($medico = $resultado->fetch_assoc()) {
                            echo "
                            <div class='medico-card'>
                                <div class='medico-header'>
                                    <h3>" . htmlspecialchars($medico['nombre']) . "</h3>
                                    <p class='especialidad'>" . htmlspecialchars($medico['especialidad']) . "</p>
                                </div>
                                <div class='medico-info'>
                                    <p><strong>Teléfono:</strong> " . htmlspecialchars($medico['telefono']) . "</p>
                                    <p><strong>Email:</strong> " . htmlspecialchars($medico['email']) . "</p>
                                </div>
                                <a href='agendar-cita.php?medico_id=" . $medico['id'] . "' class='btn'>Agendar Cita</a>
                            </div>
                            ";
                        }
                    } else {
                        echo "<p>No hay médicos registrados.</p>";
                    }
                    ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2026 Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
