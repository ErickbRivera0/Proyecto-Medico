<?php
session_start();

// Verificar autenticación y rol
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'medico') {
    header("Location: medico_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: medico_panel.php");
    exit();
}

require_once 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS expediente_consultas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        expediente_id INT NOT NULL,
        cita_id INT DEFAULT NULL,
        medico_id INT DEFAULT NULL,
        medico_nombre VARCHAR(255) DEFAULT NULL,
        motivo_consulta TEXT,
        diagnostico TEXT,
        tratamiento TEXT,
        observaciones TEXT,
        presion_arterial VARCHAR(20) DEFAULT NULL,
        temperatura VARCHAR(10) DEFAULT NULL,
        frecuencia_cardiaca VARCHAR(10) DEFAULT NULL,
        saturacion_oxigeno VARCHAR(10) DEFAULT NULL,
        fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_expediente_fecha (expediente_id, fecha_consulta),
        INDEX idx_cita (cita_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Si falla la creacion de la tabla, el flujo principal debe continuar
}

$cita_id = (int)$_POST['cita_id'];
$paciente_email = trim($_POST['paciente_email']);
$motivo_consulta = trim($_POST['motivo_consulta'] ?? '');
$diagnostico = trim($_POST['diagnostico']);
$tratamiento = trim($_POST['tratamiento']);
$observaciones = trim($_POST['observaciones']);
$presion_arterial = trim($_POST['presion_arterial'] ?? '');
$temperatura = trim($_POST['temperatura'] ?? '');
$frecuencia_cardiaca = trim($_POST['frecuencia_cardiaca'] ?? '');
$saturacion_oxigeno = trim($_POST['saturacion_oxigeno'] ?? '');

// Obtener nombre del medico para dejar trazabilidad en el expediente
$medico_nombre = $_SESSION['usuario'] ?? 'Medico';
$stmtMedico = $pdo->prepare("SELECT nombre FROM medicos WHERE id = ? LIMIT 1");
$stmtMedico->bindParam(1, $_SESSION['id']);
$stmtMedico->execute();
$medicoData = $stmtMedico->fetch();
if ($medicoData && !empty($medicoData['nombre'])) {
    $medico_nombre = $medicoData['nombre'];
}

$marca_atencion = date('Y-m-d H:i:s') . ' - Dr(a). ' . $medico_nombre;
$diagnostico_entry = $marca_atencion . "\n" . $diagnostico;
$tratamiento_entry = $marca_atencion . "\n" . $tratamiento;
$observaciones_entry = $marca_atencion . "\n" . $observaciones;

// Verificar que la cita pertenece al médico
$stmt = $pdo->prepare("SELECT medico_id FROM citas WHERE id = ?");
$stmt->bindParam(1, $cita_id);
$stmt->execute();
$result = $stmt->fetch();
if (!$result || $result['medico_id'] != $_SESSION['id']) {
    header("Location: medico_panel.php");
    exit();
}

// Verificar si existe expediente
$stmt = $pdo->prepare("SELECT id FROM expedientes WHERE paciente_email = ?");
$stmt->bindParam(1, $paciente_email);
$stmt->execute();
$existe = $stmt->fetch();

if ($existe) {
    // Actualizar expediente
    $sql = "UPDATE expedientes SET diagnostico = CONCAT_WS('\\n\\n', NULLIF(diagnostico, ''), ?), 
            tratamiento = CONCAT_WS('\\n\\n', NULLIF(tratamiento, ''), ?), 
            observaciones = CONCAT_WS('\\n\\n', NULLIF(observaciones, ''), ?) 
            WHERE paciente_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $diagnostico_entry);
    $stmt->bindParam(2, $tratamiento_entry);
    $stmt->bindParam(3, $observaciones_entry);
    $stmt->bindParam(4, $paciente_email);
} else {
    // Crear expediente básico
    $sql = "INSERT INTO expedientes (paciente_email, diagnostico, tratamiento, observaciones) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $paciente_email);
    $stmt->bindParam(2, $diagnostico_entry);
    $stmt->bindParam(3, $tratamiento_entry);
    $stmt->bindParam(4, $observaciones_entry);
}

if ($stmt->execute()) {
    // Cambiar estado de la cita a completada
    $stmt2 = $pdo->prepare("UPDATE citas SET estado = 'completada' WHERE id = ?");
    $stmt2->bindParam(1, $cita_id);
    $stmt2->execute();

    // Abrir PDF del expediente inmediatamente despues de guardar
    $stmtExp = $pdo->prepare("SELECT id FROM expedientes WHERE paciente_email = ? LIMIT 1");
    $stmtExp->bindParam(1, $paciente_email);
    $stmtExp->execute();
    $exp = $stmtExp->fetch();

    if ($exp && !empty($exp['id'])) {
        // Registrar la consulta como evento clinico independiente
        try {
            $stmtConsulta = $pdo->prepare("INSERT INTO expediente_consultas
                (expediente_id, cita_id, medico_id, medico_nombre, motivo_consulta, diagnostico, tratamiento, observaciones,
                 presion_arterial, temperatura, frecuencia_cardiaca, saturacion_oxigeno)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $expediente_id = (int)$exp['id'];
            $medico_id = (int)$_SESSION['id'];
            $stmtConsulta->bindParam(1, $expediente_id, PDO::PARAM_INT);
            $stmtConsulta->bindParam(2, $cita_id, PDO::PARAM_INT);
            $stmtConsulta->bindParam(3, $medico_id, PDO::PARAM_INT);
            $stmtConsulta->bindParam(4, $medico_nombre);
            $stmtConsulta->bindParam(5, $motivo_consulta);
            $stmtConsulta->bindParam(6, $diagnostico);
            $stmtConsulta->bindParam(7, $tratamiento);
            $stmtConsulta->bindParam(8, $observaciones);
            $stmtConsulta->bindParam(9, $presion_arterial);
            $stmtConsulta->bindParam(10, $temperatura);
            $stmtConsulta->bindParam(11, $frecuencia_cardiaca);
            $stmtConsulta->bindParam(12, $saturacion_oxigeno);
            $stmtConsulta->execute();
        } catch (Exception $e) {
            // Si no se puede guardar el historial estructurado, no romper el flujo principal
        }

        header("Location: generar-expediente-pdf.php?id=" . (int)$exp['id'] . "&cita_id=" . $cita_id . "&inline=1");
        exit();
    }

    header("Location: medico_panel.php?success=1");
} else {
    header("Location: atender_cita.php?id=$cita_id&error=1");
}
exit();
?>