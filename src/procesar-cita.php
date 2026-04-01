<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener datos del formulario
    $nombre   = $_POST['nombre'] ?? '';
    $email    = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $medico   = $_POST['medico_id'] ?? '';
    $fecha    = $_POST['fecha'] ?? '';
    $hora     = $_POST['hora'] ?? '';
    $motivo   = $_POST['motivo'] ?? '';

    // Validación básica
    if (empty($nombre) || empty($email) || empty($telefono) || empty($medico) || empty($fecha) || empty($hora)) {
        header("Location: agendar-cita.php?mensaje=❌ Todos los campos son obligatorios");
        exit();
    }

    try {
        // 🔥 Evitar citas duplicadas (mismo médico, fecha y hora)
        $check = $conn->prepare("SELECT id FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ?");
        $check->bind_param("iss", $medico, $fecha, $hora);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            header("Location: agendar-cita.php?mensaje=⚠️ Ese horario ya está ocupado");
            exit();
        }

        // Insertar cita
        $stmt = $conn->prepare("INSERT INTO citas 
            (paciente_nombre, paciente_email, paciente_telefono, medico_id, fecha, hora, motivo) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssisss", $nombre, $email, $telefono, $medico, $fecha, $hora, $motivo);
        $stmt->execute();

        header("Location: mis-citas.php?mensaje=✅ Cita agendada correctamente");
        exit();

    } catch (Exception $e) {
        header("Location: agendar-cita.php?mensaje=❌ Error al guardar cita");
        exit();
    }
}
?>
