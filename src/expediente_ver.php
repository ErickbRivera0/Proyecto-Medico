<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: expedientes.php');
    exit();
}

$id = (int)$_GET['id'];

// Asegurar que la columna para chequeos exista (migración ligera en runtime)
$conn->query("ALTER TABLE expedientes ADD COLUMN IF NOT EXISTS chequeos_seleccionados TEXT DEFAULT NULL");

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $sexo = $_POST['sexo'] ?? 'O';
    $direccion = $_POST['direccion'] ?? null;
    $alergias = $_POST['alergias'] ?? null;
    $antecedentes = $_POST['antecedentes'] ?? null;
    $medicamentos = $_POST['medicamentos_actuales'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $altura = $_POST['altura'] ?? null;
    $notas = $_POST['notas'] ?? null;

    // Procesar chequeos seleccionados (se recibe como array de checkboxes)
    $chequeos_selected_arr = $_POST['chequeos_seleccionados'] ?? null;
    $chequeos_selected_json = null;
    if (!empty($chequeos_selected_arr) && is_array($chequeos_selected_arr)) {
        $chequeos_selected_json = json_encode(array_values($chequeos_selected_arr), JSON_UNESCAPED_UNICODE);
    }

    $update_sql = 'UPDATE expedientes SET nombre = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?, direccion = ?, alergias = ?, antecedentes = ?, medicamentos_actuales = ?, peso = ?, altura = ?, notas = ?, chequeos_seleccionados = ? WHERE id = ?';
    $up = $conn->prepare($update_sql);
    if ($up) {
        $up->bind_param('ssssssssssssi', $nombre, $telefono, $fecha_nacimiento, $sexo, $direccion, $alergias, $antecedentes, $medicamentos, $peso, $altura, $notas, $chequeos_selected_json, $id);
        $up->execute();
    } else {
        // Si no se pudo preparar (p. ej. columna ausente por alguna razón), intentar sin el campo de chequeos
        $up2 = $conn->prepare('UPDATE expedientes SET nombre = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?, direccion = ?, alergias = ?, antecedentes = ?, medicamentos_actuales = ?, peso = ?, altura = ?, notas = ? WHERE id = ?');
        if ($up2) {
            $up2->bind_param('sssssssssssi', $nombre, $telefono, $fecha_nacimiento, $sexo, $direccion, $alergias, $antecedentes, $medicamentos, $peso, $altura, $notas, $id);
            $up2->execute();
        } else {
            error_log('No se pudo preparar la actualización del expediente.');
        }
    }
    header('Location: expedientes.php');
    exit();
}

// Cargar datos
$q = $conn->prepare('SELECT * FROM expedientes WHERE id = ? LIMIT 1');
$q->bind_param('i', $id);
$q->execute();
$exp = $q->get_result()->fetch_assoc();
if (!$exp) { header('Location: expedientes.php'); exit(); }

// Preparar lista de chequeos y selecciones actuales (para pre-marcar checkboxes)
$defaultChecks = [
    'Chequeo general', 'Signos vitales', 'Examen de sangre', 'Electrocardiograma',
    'Prueba de glucemia', 'Perfil lipídico', 'Radiografía', 'Ecografía',
    'Vacunación', 'Historia clínica', 'Control de peso', 'Control de la tensión'
];
$checksList = $defaultChecks;
// Si la fila contiene una lista personalizada (campo `chequeos_lista`), usarla
if (!empty($exp['chequeos_lista']) || !empty($exp['checks_list']) || !empty($exp['chequeos_items'])) {
    $raw = $exp['chequeos_lista'] ?? $exp['checks_list'] ?? $exp['chequeos_items'];
    if (is_array($raw)) {
        $checksList = $raw;
    } elseif (is_string($raw)) {
        $tmp = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $checksList = $tmp;
        } else {
            $checksList = array_filter(array_map('trim', explode(',', $raw)));
        }
    }
}

// Selecciones guardadas (CSV, JSON o array)
$selected = [];
$selectedRaw = $exp['chequeos_seleccionados'] ?? null;
if ($selectedRaw) {
    if (is_array($selectedRaw)) {
        $selected = $selectedRaw;
    } elseif (is_string($selectedRaw)) {
        $tmp = json_decode($selectedRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $selected = $tmp;
        } else {
            $selected = array_filter(array_map('trim', explode(',', $selectedRaw)));
        }
    }
}
$selectedNorm = array_map('mb_strtolower', $selected);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Expediente</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo"><h1>Expediente #<?php echo $exp['id']; ?></h1></div>
            <ul class="nav-menu"><li><a href="expedientes.php">Volver</a></li></ul>
        </nav>
        <main>
            <form method="POST" action="expediente_ver.php?id=<?php echo $exp['id']; ?>">
                <div class="form-group"><label>Email</label><input type="email" name="paciente_email" value="<?php echo htmlspecialchars($exp['paciente_email']); ?>" disabled></div>
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($exp['nombre']); ?>"></div>
                <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" value="<?php echo htmlspecialchars($exp['telefono']); ?>"></div>
                <div class="form-group"><label>Fecha Nacimiento</label><input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($exp['fecha_nacimiento']); ?>"></div>
                <div class="form-group"><label>Sexo</label>
                    <select name="sexo">
                        <option value="O" <?php if($exp['sexo']=='O') echo 'selected'; ?>>Otro</option>
                        <option value="M" <?php if($exp['sexo']=='M') echo 'selected'; ?>>Masculino</option>
                        <option value="F" <?php if($exp['sexo']=='F') echo 'selected'; ?>>Femenino</option>
                    </select>
                </div>
                <div class="form-group"><label>Alergias</label><textarea name="alergias"><?php echo htmlspecialchars($exp['alergias']); ?></textarea></div>
                <div class="form-group"><label>Antecedentes</label><textarea name="antecedentes"><?php echo htmlspecialchars($exp['antecedentes']); ?></textarea></div>
                <div class="form-group"><label>Medicamentos</label><textarea name="medicamentos_actuales"><?php echo htmlspecialchars($exp['medicamentos_actuales']); ?></textarea></div>
                <div class="form-group"><label>Peso</label><input type="text" name="peso" value="<?php echo htmlspecialchars($exp['peso']); ?>"></div>
                <div class="form-group"><label>Altura</label><input type="text" name="altura" value="<?php echo htmlspecialchars($exp['altura']); ?>"></div>
                <div class="form-group"><label>Notas</label><textarea name="notas"><?php echo htmlspecialchars($exp['notas']); ?></textarea></div>
                <div class="form-group">
                    <label>Chequeos médicos</label>
                    <div class="checks-list">
                        <?php foreach ($checksList as $item): ?>
                            <?php $isChecked = in_array(mb_strtolower($item), $selectedNorm); ?>
                            <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                                <input type="checkbox" name="chequeos_seleccionados[]" value="<?php echo htmlspecialchars($item); ?>" <?php if($isChecked) echo 'checked'; ?>> <?php echo htmlspecialchars($item); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Guardar</button>
                <a class="btn btn-success" href="generar-expediente-pdf.php?id=<?php echo $exp['id']; ?>">Generar PDF</a>
            </form>
        </main>
    </div>
</body>
</html>
