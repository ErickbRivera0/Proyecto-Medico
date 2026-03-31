<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Citas Médicas</title>
    <link rel="stylesheet" href="assets/css/global.css">
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
                    <li><a href="agendar-cita.php" class="active">Agendar Cita</a></li>
                    <li><a href="mis-citas.php">Mis Citas</a></li>
                    <li><a href="medicos.php">Médicos</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="agendar-section">
                <h2>Agendar Nueva Cita</h2>
                
                <form method="POST" action="procesar-cita.php" class="form-cita">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo:</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez">
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required placeholder="tu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" required placeholder="123456789">
                    </div>

                    <div class="form-group">
                        <label for="medico_id">Seleccionar Médico:</label>
                        <select id="medico_id" name="medico_id" required>
                            <option value="">-- Selecciona un médico --</option>
                            <?php
                            require_once 'config.php';
                            $sql = "SELECT id, nombre, especialidad FROM medicos ORDER BY nombre ASC";
                            $resultado = $conn->query($sql);
                            while ($medico = $resultado->fetch_assoc()) {
                                echo "<option value='" . $medico['id'] . "'>" . htmlspecialchars($medico['nombre']) . " - " . htmlspecialchars($medico['especialidad']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha">Fecha de la Cita:</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>

                    <div class="form-group">
                        <label for="hora">Hora de la Cita:</label>
                        <input type="time" id="hora" name="hora" required>
                    </div>

                    <div class="form-group">
                        <label for="motivo">Motivo de la Cita:</label>
                        <textarea id="motivo" name="motivo" required placeholder="Describe el motivo de tu visita"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Agendar Cita</button>
                </form>
            </section>
        </main>

        <footer>
            <p>&copy; 2026 Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
