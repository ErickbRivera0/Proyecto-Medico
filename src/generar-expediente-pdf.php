<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'ID inválido';
    exit();
}

$id = (int)$_GET['id'];

// Obtener expediente
$q = $pdo->prepare('SELECT * FROM expedientes WHERE id = ? LIMIT 1');
$q->bindParam(1, $id, PDO::PARAM_INT);
$q->execute();
$exp = $q->fetch();

if (!$exp) {
    header('HTTP/1.1 404 Not Found');
    echo 'Expediente no encontrado';
    exit();
}

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

// Intentar identificar el medico tratante de la atencion actual
$medicoAtencion = '';
$fechaAtencion = '';

if (isset($_GET['cita_id']) && is_numeric($_GET['cita_id'])) {
    $citaId = (int)$_GET['cita_id'];
    $qMed = $pdo->prepare("SELECT m.nombre AS medico_nombre, c.fecha, c.hora
                           FROM citas c
                           LEFT JOIN medicos m ON c.medico_id = m.id
                           WHERE c.id = ? AND c.paciente_email = ?
                           LIMIT 1");
    $qMed->bindParam(1, $citaId, PDO::PARAM_INT);
    $qMed->bindParam(2, $exp['paciente_email']);
    $qMed->execute();
    $atencion = $qMed->fetch();
    if ($atencion) {
        $medicoAtencion = $atencion['medico_nombre'] ?? '';
        if (!empty($atencion['fecha']) && !empty($atencion['hora'])) {
            $fechaAtencion = date('d/m/Y', strtotime($atencion['fecha'])) . ' ' . substr($atencion['hora'], 0, 5);
        }
    }
}

if ($medicoAtencion === '') {
    $qMed = $pdo->prepare("SELECT m.nombre AS medico_nombre, c.fecha, c.hora
                           FROM citas c
                           LEFT JOIN medicos m ON c.medico_id = m.id
                           WHERE c.paciente_email = ? AND c.estado = 'completada'
                           ORDER BY c.fecha DESC, c.hora DESC
                           LIMIT 1");
    $qMed->bindParam(1, $exp['paciente_email']);
    $qMed->execute();
    $atencion = $qMed->fetch();
    if ($atencion) {
        $medicoAtencion = $atencion['medico_nombre'] ?? '';
        if (!empty($atencion['fecha']) && !empty($atencion['hora'])) {
            $fechaAtencion = date('d/m/Y', strtotime($atencion['fecha'])) . ' ' . substr($atencion['hora'], 0, 5);
        }
    }
}

$ultimaConsulta = null;
$consultasRecientes = [];
$qCons = $pdo->prepare("SELECT * FROM expediente_consultas WHERE expediente_id = ? ORDER BY fecha_consulta DESC LIMIT 5");
$qCons->bindParam(1, $exp['id'], PDO::PARAM_INT);
$qCons->execute();
$consultasRecientes = $qCons->fetchAll();
if (!empty($consultasRecientes)) {
    $ultimaConsulta = $consultasRecientes[0];
    if (empty($medicoAtencion) && !empty($ultimaConsulta['medico_nombre'])) {
        $medicoAtencion = $ultimaConsulta['medico_nombre'];
    }
    if (empty($fechaAtencion) && !empty($ultimaConsulta['fecha_consulta'])) {
        $fechaAtencion = date('d/m/Y H:i', strtotime($ultimaConsulta['fecha_consulta']));
    }
}

// Generar PDF con Dompdf
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Si Dompdf no está instalado, devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Dompdf no instalado';
    exit();
}


require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Renderizar usando plantilla HTML/CSS para impresión A4
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Cargar plantilla (expediente_pdf_template.php) y capturar HTML
ob_start();
// la plantilla usará la variable $exp
include __DIR__ . '/expediente_pdf_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$exportsDir = __DIR__ . '/exports';
if (!is_dir($exportsDir)) mkdir($exportsDir, 0755, true);

$filePath = $exportsDir . '/expediente_' . $exp['id'] . '.pdf';
file_put_contents($filePath, $dompdf->output());

// Enviar PDF al navegador en linea o para descarga
$inline = isset($_GET['inline']) && $_GET['inline'] === '1';
$disposition = $inline ? 'inline' : 'attachment';
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="expediente_' . $exp['id'] . '.pdf"');
readfile($filePath);
exit();
