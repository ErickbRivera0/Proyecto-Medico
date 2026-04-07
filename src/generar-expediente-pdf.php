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

// Enviar PDF al navegador para descarga
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="expediente_' . $exp['id'] . '.pdf"');
readfile($filePath);
exit();
