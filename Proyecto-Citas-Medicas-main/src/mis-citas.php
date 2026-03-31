<?php
require_once 'config.php';

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';

// Obtener todas las citas
$sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
        FROM citas c 
        LEFT JOIN medicos m ON c.medico_id = m.id 
        ORDER BY c.fecha DESC, c.hora DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - Citas Médicas</title>
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
                    <li><a href="agendar-cita.php">Agendar Cita</a></li>
                    <li><a href="mis-citas.php" class="active">Mis Citas</a></li>
                    <li><a href="medicos.php">Médicos</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="citas-section">
                <h2>Mis Citas</h2>
                <?php if (!empty($mensaje)) : ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>

                <?php endif; ?>
                <?php if (!empty($mensaje)) : ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <div class="citas-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultado->num_rows > 0) {
                                while ($cita = $resultado->fetch_assoc()) {
                                    $estado_class = strtolower($cita['estado']);
                                    echo "
                                    <tr>
                                        <td>" . htmlspecialchars($cita['paciente_nombre']) . "</td>
                                        <td>" . htmlspecialchars($cita['medico_nombre'] ?? 'No asignado') . "</td>
                                        <td>" . htmlspecialchars($cita['especialidad'] ?? '-') . "</td>
                                        <td>" . htmlspecialchars($cita['fecha']) . "</td>
                                        <td>" . htmlspecialchars($cita['hora']) . "</td>
                                        <td>" . htmlspecialchars(substr($cita['motivo'], 0, 30) . '...') . "</td>
                                        <td><span class='estado $estado_class'>" . htmlspecialchars($cita['estado']) . "</span></td>
                                        <td>
                                            <a href='editar-cita.php?id=" . $cita['id'] . "' class='btn-small'>Editar</a>
                                            <a href='eliminar-cita.php?id=" . $cita['id'] . "' class='btn-small btn-danger' onclick='return confirm(\"¿Estás seguro?\")'>Eliminar</a>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No hay citas registradas</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2026 Sistema de Citas Médicas. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>

</html>