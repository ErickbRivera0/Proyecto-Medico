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

$cita_id = (int)$_POST['cita_id'];
$paciente_email = trim($_POST['paciente_email']);
$diagnostico = trim($_POST['diagnostico']);
$tratamiento = trim($_POST['tratamiento']);
$observaciones = trim($_POST['observaciones']);

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
    $sql = "UPDATE expedientes SET diagnostico = CONCAT(IFNULL(diagnostico, ''), '\n\n', NOW(), ' - ', ?), 
            tratamiento = CONCAT(IFNULL(tratamiento, ''), '\n\n', NOW(), ' - ', ?), 
            observaciones = CONCAT(IFNULL(observaciones, ''), '\n\n', NOW(), ' - ', ?) 
            WHERE paciente_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $diagnostico);
    $stmt->bindParam(2, $tratamiento);
    $stmt->bindParam(3, $observaciones);
    $stmt->bindParam(4, $paciente_email);
} else {
    // Crear expediente básico
    $sql = "INSERT INTO expedientes (paciente_email, diagnostico, tratamiento, observaciones) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $paciente_email);
    $stmt->bindParam(2, $diagnostico);
    $stmt->bindParam(3, $tratamiento);
    $stmt->bindParam(4, $observaciones);
}

if ($stmt->execute()) {
    // Cambiar estado de la cita a completada
    $stmt2 = $pdo->prepare("UPDATE citas SET estado = 'completada' WHERE id = ?");
    $stmt2->bindParam(1, $cita_id);
    $stmt2->execute();

    header("Location: medico_panel.php?success=1");
} else {
    header("Location: atender_cita.php?id=$cita_id&error=1");
}
exit();
?>