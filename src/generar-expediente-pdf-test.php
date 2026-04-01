<?php
// Script de prueba para generar un PDF del expediente sin depender de la BD.
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$exp = [
    'id' => 999,
    'paciente_email' => 'test@ejemplo.com',
    'nombre' => 'Juan Pérez',
    'direccion' => 'Calle Falsa 123, Ciudad',
    'fecha_nacimiento' => '1988-04-01',
    'sexo' => 'M',
    'telefono' => '+504 5555-5555',
    'alergias' => "Penicilina\nNinguna otra conocida",
    'antecedentes' => "Hipertensión arterial\nApendicectomía (2010)",
    'medicamentos_actuales' => "Aspirina 100mg - diario\nLisinopril 10mg",
    'peso' => '78 kg',
    'altura' => '1.75 m',
    'notas' => "Paciente con seguimiento trimestral. Requiere control de presión.",
    // Ejemplo de chequeos seleccionados (JSON)
    'chequeos_seleccionados' => json_encode(['Chequeo general', 'Signos vitales', 'Vacunación'], JSON_UNESCAPED_UNICODE),
];

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Incluir plantilla y capturar HTML
ob_start();
include __DIR__ . '/expediente_pdf_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$exportsDir = __DIR__ . '/exports';
if (!is_dir($exportsDir)) mkdir($exportsDir, 0755, true);

$filePath = $exportsDir . '/expediente_test_' . $exp['id'] . '.pdf';
file_put_contents($filePath, $dompdf->output());

echo "PDF generado: " . $filePath . PHP_EOL;

?>
