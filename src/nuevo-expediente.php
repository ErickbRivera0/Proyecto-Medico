<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'medico') {
    header('Location: index.php?error=solo_medico_expediente');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = !empty(trim($_POST['fecha_nacimiento'] ?? '')) ? trim($_POST['fecha_nacimiento']) : null;
    $sexo = $_POST['sexo'] ?? 'O';
    $direccion = !empty(trim($_POST['direccion'] ?? '')) ? trim($_POST['direccion']) : null;
    $alergias = !empty(trim($_POST['alergias'] ?? '')) ? trim($_POST['alergias']) : null;
    $antecedentes = !empty(trim($_POST['antecedentes'] ?? '')) ? trim($_POST['antecedentes']) : null;
    $medicamentos = !empty(trim($_POST['medicamentos_actuales'] ?? '')) ? trim($_POST['medicamentos_actuales']) : null;
    $peso = !empty(trim($_POST['peso'] ?? '')) ? (float)trim($_POST['peso']) : null;
    $altura = !empty(trim($_POST['altura'] ?? '')) ? (float)trim($_POST['altura']) : null;
    $notas = !empty(trim($_POST['notas'] ?? '')) ? trim($_POST['notas']) : null;

    if (empty($nombre)) {
        $error = 'El nombre es obligatorio ';
    } else {
        // Insertar o actualizar si existe
        $check = $pdo->prepare('SELECT id FROM expedientes WHERE nombre = ? LIMIT 1');
        $check->bindParam(1, $nombre);
        $check->execute();
        $res = $check;

        if ($res && $res->rowCount() > 0) {
            $row = $res->fetch();
            $id = $row['id'];
            $up = $pdo->prepare('UPDATE expedientes SET nombre = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?, direccion = ?, alergias = ?, antecedentes = ?, medicamentos_actuales = ?, peso = ?, altura = ?, notas = ? WHERE id = ?');
            $up->bindParam(1, $nombre);
            $up->bindParam(2, $telefono);
            $up->bindParam(3, $fecha_nacimiento);
            $up->bindParam(4, $sexo);
            $up->bindParam(5, $direccion);
            $up->bindParam(6, $alergias);
            $up->bindParam(7, $antecedentes);
            $up->bindParam(8, $medicamentos);
            $up->bindParam(9, $peso);
            $up->bindParam(10, $altura);
            $up->bindParam(11, $notas);
            $up->bindParam(12, $id, PDO::PARAM_INT);
            $up->execute();
            $success = 'Expediente actualizado';
        } else {
            $ins = $pdo->prepare('INSERT INTO expedientes (nombre, telefono, fecha_nacimiento, sexo, direccion, alergias, antecedentes, medicamentos_actuales, peso, altura, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->bindParam(1, $nombre);
            $ins->bindParam(2, $telefono);
            $ins->bindParam(3, $fecha_nacimiento);
            $ins->bindParam(4, $sexo);
            $ins->bindParam(5, $direccion);
            $ins->bindParam(6, $alergias);
            $ins->bindParam(7, $antecedentes);
            $ins->bindParam(8, $medicamentos);
            $ins->bindParam(9, $peso);
            $ins->bindParam(10, $altura);
            $ins->bindParam(11, $notas);
            $ins->execute();
            $success = 'Expediente creado';
        }
        header('Location: expedientes.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Expediente</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo"><h1>Nuevo Expediente</h1></div>
            <ul class="nav-menu">
                <li><a href="expedientes.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
            </ul>
        </nav>

        <main>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST" action="nuevo-expediente.php">
                
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono">
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento">
                </div>
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo"><option value="O">Otro</option><option value="M">Masculino</option><option value="F">Femenino</option></select>
                </div>
                <div class="form-group">
                    <label>Alergias</label>
                    <textarea name="alergias"></textarea>
                </div>
                <div class="form-group">
                    <label>Antecedentes</label>
                    <textarea name="antecedentes"></textarea>
                </div>
                <div class="form-group">
                    <label>Medicamentos actuales</label>
                    <textarea name="medicamentos_actuales"></textarea>
                </div>
                <div class="form-group">
                    <label>Peso</label>
                    <input type="text" name="peso">
                </div>
                <div class="form-group">
                    <label>Altura</label>
                    <input type="text" name="altura">
                </div>
                <div class="form-group">
                    <label>Notas</label>
                    <textarea name="notas"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Guardar Expediente</button>
            </form>
        </main>
    </div>
</body>
</html>
