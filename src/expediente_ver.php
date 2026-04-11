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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: expedientes.php');
    exit();
}

$id = (int)$_GET['id'];

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
    // Ignorar errores de migracion ligera
}

// Asegurar que la columna para chequeos exista (migración ligera en runtime)
try {
    $pdo->exec("ALTER TABLE expedientes ADD COLUMN chequeos_seleccionados TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si la columna ya existe
}

// Asegurar columna de detalle estructurado para el expediente ampliado
try {
    $pdo->exec("ALTER TABLE expedientes ADD COLUMN expediente_detalle_json LONGTEXT DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si la columna ya existe
}

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
    $detalle = $_POST['detalle'] ?? [];
    $detalle_json = null;
    if (is_array($detalle) && !empty($detalle)) {
        $detalle_json = json_encode($detalle, JSON_UNESCAPED_UNICODE);
    }

    // Procesar chequeos seleccionados (se recibe como array de checkboxes)
    $chequeos_selected_arr = $_POST['chequeos_seleccionados'] ?? null;
    $chequeos_selected_json = null;
    if (!empty($chequeos_selected_arr) && is_array($chequeos_selected_arr)) {
        $chequeos_selected_json = json_encode(array_values($chequeos_selected_arr), JSON_UNESCAPED_UNICODE);
    }

    $update_sql = 'UPDATE expedientes SET nombre = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?, direccion = ?, alergias = ?, antecedentes = ?, medicamentos_actuales = ?, peso = ?, altura = ?, notas = ?, chequeos_seleccionados = ?, expediente_detalle_json = ? WHERE id = ?';
    $up = $pdo->prepare($update_sql);
    if ($up) {
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
        $up->bindParam(12, $chequeos_selected_json);
        $up->bindParam(13, $detalle_json);
        $up->bindParam(14, $id, PDO::PARAM_INT);
        $up->execute();
    } else {
        // Si no se pudo preparar (p. ej. columna ausente por alguna razón), intentar sin el campo de chequeos
        $up2 = $pdo->prepare('UPDATE expedientes SET nombre = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?, direccion = ?, alergias = ?, antecedentes = ?, medicamentos_actuales = ?, peso = ?, altura = ?, notas = ? WHERE id = ?');
        if ($up2) {
            $up2->bindParam(1, $nombre);
            $up2->bindParam(2, $telefono);
            $up2->bindParam(3, $fecha_nacimiento);
            $up2->bindParam(4, $sexo);
            $up2->bindParam(5, $direccion);
            $up2->bindParam(6, $alergias);
            $up2->bindParam(7, $antecedentes);
            $up2->bindParam(8, $medicamentos);
            $up2->bindParam(9, $peso);
            $up2->bindParam(10, $altura);
            $up2->bindParam(11, $notas);
            $up2->bindParam(12, $id, PDO::PARAM_INT);
            $up2->execute();
        } else {
            error_log('No se pudo preparar la actualización del expediente.');
        }
    }
    header('Location: expedientes.php');
    exit();
}

// Cargar datos
$q = $pdo->prepare('SELECT * FROM expedientes WHERE id = ? LIMIT 1');
$q->bindParam(1, $id, PDO::PARAM_INT);
$q->execute();
$exp = $q->fetch();
if (!$exp) { header('Location: expedientes.php'); exit(); }

$qConsultas = $pdo->prepare('SELECT * FROM expediente_consultas WHERE expediente_id = ? ORDER BY fecha_consulta DESC LIMIT 10');
$qConsultas->bindParam(1, $id, PDO::PARAM_INT);
$qConsultas->execute();
$consultas = $qConsultas->fetchAll();

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

$detalle = [];
if (!empty($exp['expediente_detalle_json'])) {
    $tmp = json_decode($exp['expediente_detalle_json'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $detalle = $tmp;
    }
}

$detalleFam = $detalle['antecedentes_familiares'] ?? [];
$detallePer = $detalle['antecedentes_personales'] ?? [];
$detalleLabs = $detalle['laboratorio'] ?? [];
$detalleRx = $detalle['radiografias'] ?? [];
$detalleUsg = $detalle['ultrasonidos'] ?? [];
$detalleIndicaciones = $detalle['indicaciones_generales'] ?? [];

$pesoBase = isset($exp['peso']) ? (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', (string)$exp['peso'])) : 0.0;
$alturaBaseCm = isset($exp['altura']) ? (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', (string)$exp['altura'])) : 0.0;
$imcBase = '';
if ($pesoBase > 0 && $alturaBaseCm > 0) {
    $alturaM = $alturaBaseCm / 100;
    if ($alturaM > 0) {
        $imcBase = number_format($pesoBase / ($alturaM * $alturaM), 2, '.', '');
    }
}

$tabacoEstado = $detalle['tabaco_estado'] ?? '';
if ($tabacoEstado === '' && !empty($detalle['tabaco'])) {
    $tabacoEstado = stripos((string)$detalle['tabaco'], 'no') !== false ? 'No' : 'Si';
}
$alcoholEstado = $detalle['alcohol_estado'] ?? '';
if ($alcoholEstado === '' && !empty($detalle['alcohol'])) {
    $alcoholEstado = stripos((string)$detalle['alcohol'], 'no') !== false ? 'No' : 'Si';
}
$drogasEstado = $detalle['drogas_estado'] ?? '';
if ($drogasEstado === '' && !empty($detalle['drogas'])) {
    $drogasEstado = stripos((string)$detalle['drogas'], 'no') !== false ? 'No' : 'Si';
}

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
            <section class="expediente-info" style="margin-bottom:1rem;">
                <h3>Historial Clínico de Consultas</h3>
                <?php if (count($consultas) === 0): ?>
                    <p>No hay consultas registradas todavía.</p>
                <?php else: ?>
                    <?php foreach ($consultas as $consulta): ?>
                        <div style="padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:10px;">
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])); ?></p>
                            <p><strong>Médico:</strong> <?php echo htmlspecialchars($consulta['medico_nombre'] ?: 'No especificado'); ?></p>
                            <p><strong>Motivo:</strong> <?php echo nl2br(htmlspecialchars($consulta['motivo_consulta'] ?: '')); ?></p>
                            <p><strong>Signos vitales:</strong>
                                PA <?php echo htmlspecialchars($consulta['presion_arterial'] ?: '-'); ?>,
                                Temp <?php echo htmlspecialchars($consulta['temperatura'] ?: '-'); ?>,
                                FC <?php echo htmlspecialchars($consulta['frecuencia_cardiaca'] ?: '-'); ?>,
                                SpO2 <?php echo htmlspecialchars($consulta['saturacion_oxigeno'] ?: '-'); ?>
                            </p>
                            <p><strong>Diagnóstico:</strong> <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?: '')); ?></p>
                            <p><strong>Tratamiento:</strong> <?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?: '')); ?></p>
                            <p><strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($consulta['observaciones'] ?: '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

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
                <div class="form-group"><label>Peso (kg)</label><input type="text" id="peso_input" name="peso" value="<?php echo htmlspecialchars($exp['peso']); ?>" placeholder="Ej. 72.5"></div>
                <div class="form-group"><label>Altura (cm)</label><input type="text" id="altura_input" name="altura" value="<?php echo htmlspecialchars($exp['altura']); ?>" placeholder="Ej. 168"></div>
                <div class="form-group"><label>Notas</label><textarea name="notas"><?php echo htmlspecialchars($exp['notas']); ?></textarea></div>

                <hr>
                <h3>Formato Clínico Completo</h3>

                <div class="form-group"><label>Identidad</label><input type="text" name="detalle[identidad]" value="<?php echo htmlspecialchars($detalle['identidad'] ?? ''); ?>"></div>
                <div class="form-group"><label>Estado civil</label><input type="text" name="detalle[estado_civil]" value="<?php echo htmlspecialchars($detalle['estado_civil'] ?? ''); ?>"></div>
                <div class="form-group"><label>Ocupación</label><input type="text" name="detalle[ocupacion]" value="<?php echo htmlspecialchars($detalle['ocupacion'] ?? ''); ?>"></div>
                <div class="form-group"><label>Contacto de emergencia</label><input type="text" name="detalle[contacto_emergencia]" value="<?php echo htmlspecialchars($detalle['contacto_emergencia'] ?? ''); ?>"></div>
                <div class="form-group"><label>Teléfono de emergencia</label><input type="text" name="detalle[telefono_emergencia]" value="<?php echo htmlspecialchars($detalle['telefono_emergencia'] ?? ''); ?>"></div>
                <div class="form-group"><label>Fecha de consulta</label><input type="date" name="detalle[fecha_consulta]" value="<?php echo htmlspecialchars($detalle['fecha_consulta'] ?? ''); ?>"></div>
                <div class="form-group"><label>Médico responsable</label><input type="text" name="detalle[medico_responsable]" value="<?php echo htmlspecialchars($detalle['medico_responsable'] ?? ''); ?>"></div>

                <h4>Preclínica / Signos Vitales</h4>
                <div class="form-group"><label>IMC</label><input type="text" id="imc_input" name="detalle[imc]" value="<?php echo htmlspecialchars($detalle['imc'] ?? $imcBase); ?>" readonly></div>
                <div class="form-group"><label>Temperatura (°C)</label><input type="text" name="detalle[temperatura]" value="<?php echo htmlspecialchars($detalle['temperatura'] ?? ''); ?>"></div>
                <div class="form-group"><label>Presión arterial</label><input type="text" name="detalle[presion_arterial]" value="<?php echo htmlspecialchars($detalle['presion_arterial'] ?? ''); ?>"></div>
                <div class="form-group"><label>Frecuencia cardíaca (lpm)</label><input type="text" name="detalle[frecuencia_cardiaca]" value="<?php echo htmlspecialchars($detalle['frecuencia_cardiaca'] ?? ''); ?>"></div>
                <div class="form-group"><label>Frecuencia respiratoria (rpm)</label><input type="text" name="detalle[frecuencia_respiratoria]" value="<?php echo htmlspecialchars($detalle['frecuencia_respiratoria'] ?? ''); ?>"></div>
                <div class="form-group"><label>Saturación O2 (%)</label><input type="text" name="detalle[saturacion_o2]" value="<?php echo htmlspecialchars($detalle['saturacion_o2'] ?? ''); ?>"></div>
                <div class="form-group"><label>Glucemia capilar (mg/dl)</label><input type="text" name="detalle[glucemia_capilar]" value="<?php echo htmlspecialchars($detalle['glucemia_capilar'] ?? ''); ?>"></div>

                <h4>Antecedentes Patológicos Familiares</h4>
                <?php $famOptions = ['HTA','Diabetes','Cardiopatias','ACV','Cancer','Asma/EPOC','Enfermedad renal','Epilepsia','Trastornos mentales','Tuberculosis']; ?>
                <div class="checks-list">
                    <?php foreach ($famOptions as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[antecedentes_familiares][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detalleFam, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Otros familiares</label><input type="text" name="detalle[antecedentes_familiares_otros]" value="<?php echo htmlspecialchars($detalle['antecedentes_familiares_otros'] ?? ''); ?>"></div>

                <h4>Antecedentes Personales Patológicos</h4>
                <?php $perOptions = ['HTA','Diabetes','Asma','Cardiopatia','Gastritis/ERGE','Enfermedad renal','Enfermedad hepatica']; ?>
                <div class="checks-list">
                    <?php foreach ($perOptions as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[antecedentes_personales][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detallePer, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Cirugías previas</label><input type="text" name="detalle[cirugias_previas]" value="<?php echo htmlspecialchars($detalle['cirugias_previas'] ?? ''); ?>"></div>
                <div class="form-group"><label>Hospitalizaciones</label><input type="text" name="detalle[hospitalizaciones]" value="<?php echo htmlspecialchars($detalle['hospitalizaciones'] ?? ''); ?>"></div>
                <div class="form-group"><label>Traumatismos</label><input type="text" name="detalle[traumatismos]" value="<?php echo htmlspecialchars($detalle['traumatismos'] ?? ''); ?>"></div>
                <div class="form-group"><label>Transfusiones</label>
                    <select name="detalle[transfusiones]">
                        <option value="" <?php if(($detalle['transfusiones'] ?? '')==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="Si" <?php if(($detalle['transfusiones'] ?? '')==='Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if(($detalle['transfusiones'] ?? '')==='No') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="form-group"><label>ITS</label>
                    <select name="detalle[its]">
                        <option value="" <?php if(($detalle['its'] ?? '')==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="Si" <?php if(($detalle['its'] ?? '')==='Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if(($detalle['its'] ?? '')==='No') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="form-group"><label>Otros personales</label><input type="text" name="detalle[antecedentes_personales_otros]" value="<?php echo htmlspecialchars($detalle['antecedentes_personales_otros'] ?? ''); ?>"></div>

                <h4>Alergias</h4>
                <div class="form-group"><label>Alergias a medicamentos</label><input type="text" name="detalle[alergias_medicamentos]" value="<?php echo htmlspecialchars($detalle['alergias_medicamentos'] ?? ''); ?>"></div>
                <div class="form-group"><label>Alergias a alimentos</label><input type="text" name="detalle[alergias_alimentos]" value="<?php echo htmlspecialchars($detalle['alergias_alimentos'] ?? ''); ?>"></div>
                <div class="form-group"><label>Alergias ambientales</label><input type="text" name="detalle[alergias_ambientales]" value="<?php echo htmlspecialchars($detalle['alergias_ambientales'] ?? ''); ?>"></div>
                <div class="form-group"><label>Otras alergias</label><input type="text" name="detalle[alergias_otros]" value="<?php echo htmlspecialchars($detalle['alergias_otros'] ?? ''); ?>"></div>

                <h4>Hábitos Tóxicos</h4>
                <div class="form-group"><label>Tabaco</label>
                    <select name="detalle[tabaco_estado]">
                        <option value="" <?php if($tabacoEstado==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="No" <?php if($tabacoEstado==='No') echo 'selected'; ?>>No</option>
                        <option value="Si" <?php if($tabacoEstado==='Si') echo 'selected'; ?>>Sí</option>
                    </select>
                </div>
                <div class="form-group"><label>Cantidad tabaco</label><input type="text" name="detalle[tabaco_cantidad]" value="<?php echo htmlspecialchars($detalle['tabaco_cantidad'] ?? ''); ?>"></div>
                <div class="form-group"><label>Alcohol</label>
                    <select name="detalle[alcohol_estado]">
                        <option value="" <?php if($alcoholEstado==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="No" <?php if($alcoholEstado==='No') echo 'selected'; ?>>No</option>
                        <option value="Si" <?php if($alcoholEstado==='Si') echo 'selected'; ?>>Sí</option>
                    </select>
                </div>
                <div class="form-group"><label>Frecuencia alcohol</label><input type="text" name="detalle[alcohol_frecuencia]" value="<?php echo htmlspecialchars($detalle['alcohol_frecuencia'] ?? ''); ?>"></div>
                <div class="form-group"><label>Drogas</label>
                    <select name="detalle[drogas_estado]">
                        <option value="" <?php if($drogasEstado==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="No" <?php if($drogasEstado==='No') echo 'selected'; ?>>No</option>
                        <option value="Si" <?php if($drogasEstado==='Si') echo 'selected'; ?>>Sí</option>
                    </select>
                </div>
                <div class="form-group"><label>Tipo de droga</label><input type="text" name="detalle[drogas_tipo]" value="<?php echo htmlspecialchars($detalle['drogas_tipo'] ?? ''); ?>"></div>
                <div class="form-group"><label>Café / energizantes</label><input type="text" name="detalle[cafe_energizantes]" value="<?php echo htmlspecialchars($detalle['cafe_energizantes'] ?? ''); ?>"></div>

                <h4>Historia de la enfermedad actual</h4>
                <div class="form-group"><textarea name="detalle[historia_enfermedad_actual]" rows="5"><?php echo htmlspecialchars($detalle['historia_enfermedad_actual'] ?? ''); ?></textarea></div>

                <h4>Examen físico</h4>
                <div class="form-group"><label>Estado general</label><input type="text" name="detalle[estado_general]" value="<?php echo htmlspecialchars($detalle['estado_general'] ?? ''); ?>" placeholder="Bueno/Regular/Malo"></div>
                <div class="form-group"><label>Consciente</label><input type="text" name="detalle[consciente]" value="<?php echo htmlspecialchars($detalle['consciente'] ?? ''); ?>" placeholder="Sí/No"></div>
                <div class="form-group"><label>Hidratación</label><input type="text" name="detalle[hidratacion]" value="<?php echo htmlspecialchars($detalle['hidratacion'] ?? ''); ?>" placeholder="Adecuada/Deshidratado"></div>
                <div class="form-group"><label>Coloración</label><input type="text" name="detalle[coloracion]" value="<?php echo htmlspecialchars($detalle['coloracion'] ?? ''); ?>" placeholder="Normal/Pálido/Ictérico/Cianótico"></div>
                <div class="form-group"><label>Cabeza y cuello</label><textarea name="detalle[cabeza_cuello]" rows="2"><?php echo htmlspecialchars($detalle['cabeza_cuello'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Cardiopulmonar</label><textarea name="detalle[cardiopulmonar]" rows="2"><?php echo htmlspecialchars($detalle['cardiopulmonar'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Abdomen</label><textarea name="detalle[abdomen]" rows="2"><?php echo htmlspecialchars($detalle['abdomen'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Extremidades</label><textarea name="detalle[extremidades]" rows="2"><?php echo htmlspecialchars($detalle['extremidades'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Neurológico</label><textarea name="detalle[neurologico]" rows="2"><?php echo htmlspecialchars($detalle['neurologico'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Piel y mucosas</label><textarea name="detalle[piel_mucosas]" rows="2"><?php echo htmlspecialchars($detalle['piel_mucosas'] ?? ''); ?></textarea></div>

                <h4>Exámenes de laboratorio</h4>
                <?php $labOptions = ['Hemograma completo','Glucosa en ayunas','HbA1c','Urea','Creatinina','Acido urico','Perfil lipidico completo','AST/TGO','ALT/TGP','Bilirrubinas','Fosfatasa alcalina','Electrolitos (Na, K, Cl)','TSH','T3 / T4','Examen general de orina','Urocultivo','Coproparasitario','Prueba embarazo (b-HCG)','VIH','VDRL/RPR','HBsAg','Anti-HCV','Amilasa/Lipasa','PCR','VSG','Troponinas','Dimero D','Gasometria arterial','Grupo y RH','Tiempo de protrombina (TP/INR)','TPT']; ?>
                <div class="checks-list">
                    <?php foreach ($labOptions as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[laboratorio][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detalleLabs, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Otros laboratorio</label><input type="text" name="detalle[laboratorio_otros]" value="<?php echo htmlspecialchars($detalle['laboratorio_otros'] ?? ''); ?>"></div>

                <h4>Radiografías</h4>
                <?php $rxOptions = ['Rx Torax','Rx Abdomen','Rx Columna cervical','Rx Columna lumbar','Rx Pelvis','Rx Extremidad superior','Rx Extremidad inferior']; ?>
                <div class="checks-list">
                    <?php foreach ($rxOptions as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[radiografias][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detalleRx, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Otras radiografías</label><input type="text" name="detalle[radiografias_otros]" value="<?php echo htmlspecialchars($detalle['radiografias_otros'] ?? ''); ?>"></div>

                <h4>Ultrasonidos</h4>
                <?php $usgOptions = ['USG Abdominal','USG Hepatobiliar','USG Renal y vias urinarias','USG Pelvico','USG Obstetrico','USG Tiroides','USG Mamario','USG Testicular','USG Doppler venoso']; ?>
                <div class="checks-list">
                    <?php foreach ($usgOptions as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[ultrasonidos][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detalleUsg, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Otros ultrasonidos</label><input type="text" name="detalle[ultrasonidos_otros]" value="<?php echo htmlspecialchars($detalle['ultrasonidos_otros'] ?? ''); ?>"></div>

                <h4>Diagnóstico</h4>
                <div class="form-group"><label>Diagnóstico principal</label><textarea name="detalle[diagnostico_principal]" rows="2"><?php echo htmlspecialchars($detalle['diagnostico_principal'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Diagnósticos secundarios</label><textarea name="detalle[diagnosticos_secundarios]" rows="2"><?php echo htmlspecialchars($detalle['diagnosticos_secundarios'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>CIE-10</label><input type="text" name="detalle[cie10]" value="<?php echo htmlspecialchars($detalle['cie10'] ?? ''); ?>"></div>

                <h4>Tratamiento y seguimiento</h4>
                <div class="form-group"><label>Tratamiento indicado</label><textarea name="detalle[tratamiento_indicado]" rows="4"><?php echo htmlspecialchars($detalle['tratamiento_indicado'] ?? ''); ?></textarea></div>
                <?php $indOpts = ['Reposo','Hidratacion','Dieta','Control de signos vitales','Referencia a especialista']; ?>
                <div class="checks-list">
                    <?php foreach ($indOpts as $opt): ?>
                        <label style="display:inline-block;margin-right:12px;margin-bottom:6px;">
                            <input type="checkbox" name="detalle[indicaciones_generales][]" value="<?php echo htmlspecialchars($opt); ?>" <?php if(in_array($opt, $detalleIndicaciones, true)) echo 'checked'; ?>> <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group"><label>Próxima cita</label><input type="date" name="detalle[proxima_cita]" value="<?php echo htmlspecialchars($detalle['proxima_cita'] ?? ''); ?>"></div>
                <div class="form-group"><label>Signos de alarma explicados</label>
                    <select name="detalle[signos_alarma_explicados]">
                        <option value="" <?php if(($detalle['signos_alarma_explicados'] ?? '')==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="Si" <?php if(($detalle['signos_alarma_explicados'] ?? '')==='Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if(($detalle['signos_alarma_explicados'] ?? '')==='No') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="form-group"><label>Referido</label>
                    <select name="detalle[referido]">
                        <option value="" <?php if(($detalle['referido'] ?? '')==='') echo 'selected'; ?>>Seleccionar</option>
                        <option value="Si" <?php if(($detalle['referido'] ?? '')==='Si') echo 'selected'; ?>>Sí</option>
                        <option value="No" <?php if(($detalle['referido'] ?? '')==='No') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="form-group"><label>Referido a</label><input type="text" name="detalle[referido_a]" value="<?php echo htmlspecialchars($detalle['referido_a'] ?? ''); ?>"></div>

                <h4>Firmas</h4>
                <div class="form-group"><label>Firma médico</label><input type="text" name="detalle[firma_medico]" value="<?php echo htmlspecialchars($detalle['firma_medico'] ?? ''); ?>"></div>
                <div class="form-group"><label>Sello</label><input type="text" name="detalle[sello_medico]" value="<?php echo htmlspecialchars($detalle['sello_medico'] ?? ''); ?>"></div>
                <div class="form-group"><label>Firma paciente</label><input type="text" name="detalle[firma_paciente]" value="<?php echo htmlspecialchars($detalle['firma_paciente'] ?? ''); ?>"></div>

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
    <script>
        (function () {
            var pesoInput = document.getElementById('peso_input');
            var alturaInput = document.getElementById('altura_input');
            var imcInput = document.getElementById('imc_input');

            function parseNumber(value) {
                if (!value) return NaN;
                return parseFloat(String(value).replace(',', '.').replace(/[^0-9.]/g, ''));
            }

            function recalcularImc() {
                var peso = parseNumber(pesoInput && pesoInput.value);
                var alturaCm = parseNumber(alturaInput && alturaInput.value);
                if (!isNaN(peso) && !isNaN(alturaCm) && alturaCm > 0) {
                    var alturaM = alturaCm / 100;
                    var imc = peso / (alturaM * alturaM);
                    imcInput.value = imc.toFixed(2);
                }
            }

            if (pesoInput) pesoInput.addEventListener('input', recalcularImc);
            if (alturaInput) alturaInput.addEventListener('input', recalcularImc);
        })();
    </script>
</body>
</html>
