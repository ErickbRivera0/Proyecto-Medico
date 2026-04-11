<?php
// Plantilla HTML para generar el PDF del expediente.
// Usa la variable $exp (registro de la base de datos) cuando se incluye.
if (!isset($exp)) $exp = [];
if (!isset($medicoAtencion)) $medicoAtencion = '';
if (!isset($fechaAtencion)) $fechaAtencion = '';
if (!isset($ultimaConsulta)) $ultimaConsulta = null;
if (!isset($consultasRecientes) || !is_array($consultasRecientes)) $consultasRecientes = [];
$detalle = [];
if (!empty($exp['expediente_detalle_json'])) {
  $tmpDetalle = json_decode($exp['expediente_detalle_json'], true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($tmpDetalle)) {
    $detalle = $tmpDetalle;
  }
}
$detalleFam = $detalle['antecedentes_familiares'] ?? [];
$detallePer = $detalle['antecedentes_personales'] ?? [];
$detalleLabs = $detalle['laboratorio'] ?? [];
$detalleRx = $detalle['radiografias'] ?? [];
$detalleUsg = $detalle['ultrasonidos'] ?? [];
$detalleIndicaciones = $detalle['indicaciones_generales'] ?? [];
$checked = static function ($arr, $value) {
  return in_array($value, is_array($arr) ? $arr : [], true) ? 'X' : '&nbsp;';
};

$toFloat = static function ($value) {
  if ($value === null || $value === '') return null;
  $normalized = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', (string)$value));
  return is_numeric($normalized) ? (float)$normalized : null;
};

$toInt = static function ($value) {
  if ($value === null || $value === '') return null;
  $normalized = preg_replace('/[^0-9]/', '', (string)$value);
  return $normalized !== '' ? (int)$normalized : null;
};

$tempClinica = $toFloat($detalle['temperatura'] ?? ($ultimaConsulta['temperatura'] ?? null));
$fcClinica = $toInt($detalle['frecuencia_cardiaca'] ?? ($ultimaConsulta['frecuencia_cardiaca'] ?? null));
$spo2Clinica = $toInt($detalle['saturacion_o2'] ?? ($ultimaConsulta['saturacion_oxigeno'] ?? null));
$glucemiaClinica = $toInt($detalle['glucemia_capilar'] ?? null);
$paTexto = (string)($detalle['presion_arterial'] ?? ($ultimaConsulta['presion_arterial'] ?? ''));
$paSistolica = null;
if (preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', $paTexto, $mPa)) {
  $paSistolica = (int)$mPa[1];
}

$alertasClinicas = [];
if ($tempClinica !== null && $tempClinica >= 38.0) $alertasClinicas[] = 'Fiebre (>= 38.0 C)';
if ($spo2Clinica !== null && $spo2Clinica < 92) $alertasClinicas[] = 'Saturacion baja (< 92%)';
if ($paSistolica !== null && $paSistolica >= 140) $alertasClinicas[] = 'Presion arterial elevada (sistolica >= 140 mmHg)';
if ($fcClinica !== null && ($fcClinica > 100 || $fcClinica < 50)) $alertasClinicas[] = 'Frecuencia cardiaca fuera de rango (50-100 lpm)';
if ($glucemiaClinica !== null && $glucemiaClinica >= 200) $alertasClinicas[] = 'Glucemia capilar elevada (>= 200 mg/dl)';
// Calcular edad si hay fecha de nacimiento
$edad = '';
if (!empty($exp['fecha_nacimiento'])) {
    try {
        $dob = new DateTime($exp['fecha_nacimiento']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $edad = $diff->y;
    } catch (Exception $e) {
        $edad = '';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Expediente <?php echo isset($exp['id']) ? $exp['id'] : ''; ?></title>
  <style>
    <?php
      // Incrustar CSS de impresión para que Dompdf lo pueda procesar sin rutas remotas
      $cssPath = __DIR__ . '/assets/css/expediente-print.css';
      if (file_exists($cssPath)) echo file_get_contents($cssPath);
    ?>
    .grid-2 { width:100%; }
    .grid-2 .col { width:49%; display:inline-block; vertical-align:top; }
    .line-note { border-bottom:1px solid #888; min-height:14px; margin-bottom:6px; }
    .check-item { display:inline-block; margin-right:10px; margin-bottom:4px; }
    .check-box { display:inline-block; width:12px; height:12px; border:1px solid #555; text-align:center; line-height:12px; font-size:10px; margin-right:4px; }
  </style>
</head>
<body>
  <div class="pdf-wrapper">
    <header class="pdf-header">
      <div class="header-top">
        
        <div class="logo-box">
          <!-- Logo simple SVG -->
          <svg width="54" height="54" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
            <rect rx="10" width="64" height="64" fill="#2b7bbf" />
            <path d="M36 18H28v10H18v8h10v10h8V36h10v-8H36z" fill="#fff"/>
          </svg>
        </div>
        <div class="clinic-meta">
          <div class="clinic-name">SALFORD &amp; CO.</div>
          <div class="clinic-sub">Clínica médica</div>
        </div>
      </div>
    </header>

    <section class="section patient-info">
      <div class="section-title">INFORMACIÓN DEL PACIENTE</div>
      <div class="row">
        <div class="col-60">
          <div class="label">Nombre completo</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['nombre'] ?? ''); ?></div>

          <div class="label">Dirección</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['direccion'] ?? ''); ?></div>

          <div class="label">Correo electrónico</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['paciente_email'] ?? ''); ?></div>
        </div>

        <div class="col-40">
          <div class="small-row"><div class="label-inline">Fecha de nacimiento</div><div class="line-inline"><?php echo htmlspecialchars($exp['fecha_nacimiento'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Edad</div><div class="line-inline"><?php echo $edad; ?></div></div>
          <div class="small-row"><div class="label-inline">Teléfono</div><div class="line-inline"><?php echo htmlspecialchars($exp['telefono'] ?? ''); ?></div></div>
          <div class="small-row">
            <div class="label-inline">Sexo</div>
            <div class="sex-options">
              <span class="chk"><?php echo ($exp['sexo'] === 'F') ? 'X' : '&nbsp;'; ?></span> Femenino
              <span class="chk"><?php echo ($exp['sexo'] === 'M') ? 'X' : '&nbsp;'; ?></span> Masculino
              <span class="chk"><?php echo ($exp['sexo'] === 'O') ? 'X' : '&nbsp;'; ?></span> Otro
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="section-title">1. DATOS GENERALES DEL PACIENTE</div>
      <div class="row">
        <div class="col-100">
          <div class="small-row"><div class="label-inline">Nombre completo</div><div class="line-inline"><?php echo htmlspecialchars($exp['nombre'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Identidad</div><div class="line-inline"><?php echo htmlspecialchars($detalle['identidad'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Edad</div><div class="line-inline"><?php echo htmlspecialchars((string)$edad); ?></div></div>
          <div class="small-row"><div class="label-inline">Sexo</div><div class="line-inline"><span class="check-box"><?php echo ($exp['sexo'] === 'M') ? 'X' : '&nbsp;'; ?></span> M <span class="check-box"><?php echo ($exp['sexo'] === 'F') ? 'X' : '&nbsp;'; ?></span> F</div></div>
          <div class="small-row"><div class="label-inline">Fecha de nacimiento</div><div class="line-inline"><?php echo htmlspecialchars($exp['fecha_nacimiento'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Teléfono</div><div class="line-inline"><?php echo htmlspecialchars($exp['telefono'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Dirección</div><div class="line-inline"><?php echo htmlspecialchars($exp['direccion'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Estado civil</div><div class="line-inline"><?php echo htmlspecialchars($detalle['estado_civil'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Ocupación</div><div class="line-inline"><?php echo htmlspecialchars($detalle['ocupacion'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Contacto de emergencia</div><div class="line-inline"><?php echo htmlspecialchars($detalle['contacto_emergencia'] ?? ($exp['contacto_nombre'] ?? '')); ?></div></div>
          <div class="small-row"><div class="label-inline">Teléfono emergencia</div><div class="line-inline"><?php echo htmlspecialchars($detalle['telefono_emergencia'] ?? ($exp['contacto_telefono'] ?? '')); ?></div></div>
          <div class="small-row"><div class="label-inline">Fecha de consulta</div><div class="line-inline"><?php echo htmlspecialchars($detalle['fecha_consulta'] ?? $fechaAtencion); ?></div></div>
          <div class="small-row"><div class="label-inline">Médico responsable</div><div class="line-inline"><?php echo htmlspecialchars($detalle['medico_responsable'] ?? $medicoAtencion); ?></div></div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="section-title">2. PRECLÍNICA / SIGNOS VITALES</div>
      <div class="row">
        <div class="col-100">
          <div class="small-row"><div class="label-inline">Peso</div><div class="line-inline"><?php echo htmlspecialchars($exp['peso'] ?? ''); ?> kg</div></div>
          <div class="small-row"><div class="label-inline">Talla</div><div class="line-inline"><?php echo htmlspecialchars($exp['altura'] ?? ''); ?> cm</div></div>
          <div class="small-row"><div class="label-inline">IMC</div><div class="line-inline"><?php echo htmlspecialchars($detalle['imc'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Temperatura</div><div class="line-inline"><?php echo htmlspecialchars($detalle['temperatura'] ?? ($ultimaConsulta['temperatura'] ?? '')); ?> °C</div></div>
          <div class="small-row"><div class="label-inline">Presión arterial</div><div class="line-inline"><?php echo htmlspecialchars($detalle['presion_arterial'] ?? ($ultimaConsulta['presion_arterial'] ?? '')); ?></div></div>
          <div class="small-row"><div class="label-inline">Frecuencia cardiaca</div><div class="line-inline"><?php echo htmlspecialchars($detalle['frecuencia_cardiaca'] ?? ($ultimaConsulta['frecuencia_cardiaca'] ?? '')); ?> lpm</div></div>
          <div class="small-row"><div class="label-inline">Frecuencia respiratoria</div><div class="line-inline"><?php echo htmlspecialchars($detalle['frecuencia_respiratoria'] ?? ''); ?> rpm</div></div>
          <div class="small-row"><div class="label-inline">Saturación O2</div><div class="line-inline"><?php echo htmlspecialchars($detalle['saturacion_o2'] ?? ($ultimaConsulta['saturacion_oxigeno'] ?? '')); ?> %</div></div>
          <div class="small-row"><div class="label-inline">Glucemia capilar</div><div class="line-inline"><?php echo htmlspecialchars($detalle['glucemia_capilar'] ?? ''); ?> mg/dl</div></div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="section-title">RIESGO CLÍNICO</div>
      <?php if (count($alertasClinicas) === 0): ?>
        <div class="textblock">Sin banderas rojas detectadas con los signos cargados.</div>
      <?php else: ?>
        <div class="textblock">
          <?php foreach ($alertasClinicas as $alerta): ?>
            <div>- <?php echo htmlspecialchars($alerta); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="section">
      <div class="section-title">3. ANTECEDENTES PATOLÓGICOS FAMILIARES</div>
      <div class="checkbox-grid">
        <?php $famOptionsPdf = ['HTA','Diabetes','Cardiopatias','ACV','Cancer','Asma/EPOC','Enfermedad renal','Epilepsia','Trastornos mentales','Tuberculosis']; ?>
        <?php foreach ($famOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detalleFam, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
        <div class="checkbox-item"><span class="checkbox"></span> Otros: <?php echo htmlspecialchars($detalle['antecedentes_familiares_otros'] ?? ''); ?></div>
      </div>
    </section>

    <section class="section">
      <div class="section-title">4. ANTECEDENTES PERSONALES PATOLÓGICOS</div>
      <div class="checkbox-grid">
        <?php $perOptionsPdf = ['HTA','Diabetes','Asma','Cardiopatia','Gastritis/ERGE','Enfermedad renal','Enfermedad hepatica']; ?>
        <?php foreach ($perOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detallePer, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="small-row"><div class="label-inline">Cirugías previas</div><div class="line-inline"><?php echo htmlspecialchars($detalle['cirugias_previas'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Hospitalizaciones</div><div class="line-inline"><?php echo htmlspecialchars($detalle['hospitalizaciones'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Traumatismos</div><div class="line-inline"><?php echo htmlspecialchars($detalle['traumatismos'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Transfusiones</div><div class="line-inline"><?php echo htmlspecialchars($detalle['transfusiones'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">ITS</div><div class="line-inline"><?php echo htmlspecialchars($detalle['its'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Otros</div><div class="line-inline"><?php echo htmlspecialchars($detalle['antecedentes_personales_otros'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">5. ALERGIAS</div>
      <div class="small-row"><div class="label-inline">Medicamentos</div><div class="line-inline"><?php echo htmlspecialchars($detalle['alergias_medicamentos'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Alimentos</div><div class="line-inline"><?php echo htmlspecialchars($detalle['alergias_alimentos'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Ambientales</div><div class="line-inline"><?php echo htmlspecialchars($detalle['alergias_ambientales'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Otros</div><div class="line-inline"><?php echo htmlspecialchars($detalle['alergias_otros'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">6. HÁBITOS TÓXICOS</div>
      <div class="small-row"><div class="label-inline">Tabaco</div><div class="line-inline"><span class="check-box"><?php echo (($detalle['tabaco_estado'] ?? '') === 'No') ? 'X' : '&nbsp;'; ?></span> No <span class="check-box"><?php echo (($detalle['tabaco_estado'] ?? '') === 'Si') ? 'X' : '&nbsp;'; ?></span> Sí | Cantidad: <?php echo htmlspecialchars($detalle['tabaco_cantidad'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Alcohol</div><div class="line-inline"><span class="check-box"><?php echo (($detalle['alcohol_estado'] ?? '') === 'No') ? 'X' : '&nbsp;'; ?></span> No <span class="check-box"><?php echo (($detalle['alcohol_estado'] ?? '') === 'Si') ? 'X' : '&nbsp;'; ?></span> Sí | Frecuencia: <?php echo htmlspecialchars($detalle['alcohol_frecuencia'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Drogas</div><div class="line-inline"><span class="check-box"><?php echo (($detalle['drogas_estado'] ?? '') === 'No') ? 'X' : '&nbsp;'; ?></span> No <span class="check-box"><?php echo (($detalle['drogas_estado'] ?? '') === 'Si') ? 'X' : '&nbsp;'; ?></span> Sí | Tipo: <?php echo htmlspecialchars($detalle['drogas_tipo'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Café / energizantes</div><div class="line-inline"><?php echo htmlspecialchars($detalle['cafe_energizantes'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">7. HISTORIA DE LA ENFERMEDAD ACTUAL</div>
      <div class="textblock"><?php echo nl2br(htmlspecialchars($detalle['historia_enfermedad_actual'] ?? '')); ?></div>
    </section>

    <section class="section">
      <div class="section-title">8. EXAMEN FÍSICO</div>
      <div class="small-row"><div class="label-inline">Estado general</div><div class="line-inline"><?php echo htmlspecialchars($detalle['estado_general'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Consciente</div><div class="line-inline"><?php echo htmlspecialchars($detalle['consciente'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Hidratación</div><div class="line-inline"><?php echo htmlspecialchars($detalle['hidratacion'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Coloración</div><div class="line-inline"><?php echo htmlspecialchars($detalle['coloracion'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Cabeza y cuello</div><div class="line-inline"><?php echo htmlspecialchars($detalle['cabeza_cuello'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Cardiopulmonar</div><div class="line-inline"><?php echo htmlspecialchars($detalle['cardiopulmonar'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Abdomen</div><div class="line-inline"><?php echo htmlspecialchars($detalle['abdomen'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Extremidades</div><div class="line-inline"><?php echo htmlspecialchars($detalle['extremidades'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Neurológico</div><div class="line-inline"><?php echo htmlspecialchars($detalle['neurologico'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Piel y mucosas</div><div class="line-inline"><?php echo htmlspecialchars($detalle['piel_mucosas'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">9. EXÁMENES DE LABORATORIO</div>
      <?php $labOptionsPdf = ['Hemograma completo','Glucosa en ayunas','HbA1c','Urea','Creatinina','Acido urico','Perfil lipidico completo','AST/TGO','ALT/TGP','Bilirrubinas','Fosfatasa alcalina','Electrolitos (Na, K, Cl)','TSH','T3 / T4','Examen general de orina','Urocultivo','Coproparasitario','Prueba embarazo (b-HCG)','VIH','VDRL/RPR','HBsAg','Anti-HCV','Amilasa/Lipasa','PCR','VSG','Troponinas','Dimero D','Gasometria arterial','Grupo y RH','Tiempo de protrombina (TP/INR)','TPT']; ?>
      <div class="checkbox-grid">
        <?php foreach ($labOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detalleLabs, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="small-row"><div class="label-inline">Otros</div><div class="line-inline"><?php echo htmlspecialchars($detalle['laboratorio_otros'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">10. RADIOGRAFÍAS</div>
      <?php $rxOptionsPdf = ['Rx Torax','Rx Abdomen','Rx Columna cervical','Rx Columna lumbar','Rx Pelvis','Rx Extremidad superior','Rx Extremidad inferior']; ?>
      <div class="checkbox-grid">
        <?php foreach ($rxOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detalleRx, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="small-row"><div class="label-inline">Otros</div><div class="line-inline"><?php echo htmlspecialchars($detalle['radiografias_otros'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">11. ULTRASONIDOS</div>
      <?php $usgOptionsPdf = ['USG Abdominal','USG Hepatobiliar','USG Renal y vias urinarias','USG Pelvico','USG Obstetrico','USG Tiroides','USG Mamario','USG Testicular','USG Doppler venoso']; ?>
      <div class="checkbox-grid">
        <?php foreach ($usgOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detalleUsg, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="small-row"><div class="label-inline">Otros</div><div class="line-inline"><?php echo htmlspecialchars($detalle['ultrasonidos_otros'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">12. DIAGNÓSTICO</div>
      <div class="small-row"><div class="label-inline">Diagnóstico principal</div><div class="line-inline"><?php echo htmlspecialchars($detalle['diagnostico_principal'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Diagnósticos secundarios</div><div class="line-inline"><?php echo htmlspecialchars($detalle['diagnosticos_secundarios'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">CIE-10</div><div class="line-inline"><?php echo htmlspecialchars($detalle['cie10'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">13. TRATAMIENTO Y SEGUIMIENTO</div>
      <div class="label">Tratamiento indicado</div>
      <div class="textblock"><?php echo nl2br(htmlspecialchars($detalle['tratamiento_indicado'] ?? '')); ?></div>
      <div class="label">Indicaciones generales</div>
      <?php $indOptionsPdf = ['Reposo','Hidratacion','Dieta','Control de signos vitales','Referencia a especialista']; ?>
      <div class="checkbox-grid">
        <?php foreach ($indOptionsPdf as $opt): ?>
          <div class="checkbox-item"><span class="checkbox checked"><?php echo $checked($detalleIndicaciones, $opt); ?></span> <?php echo htmlspecialchars($opt); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="small-row"><div class="label-inline">Próxima cita</div><div class="line-inline"><?php echo htmlspecialchars($detalle['proxima_cita'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Signos de alarma explicados</div><div class="line-inline"><span class="check-box"><?php echo (($detalle['signos_alarma_explicados'] ?? '') === 'Si') ? 'X' : '&nbsp;'; ?></span> Sí <span class="check-box"><?php echo (($detalle['signos_alarma_explicados'] ?? '') === 'No') ? 'X' : '&nbsp;'; ?></span> No</div></div>
      <div class="small-row"><div class="label-inline">Referido</div><div class="line-inline"><span class="check-box"><?php echo (($detalle['referido'] ?? '') === 'Si') ? 'X' : '&nbsp;'; ?></span> Sí <span class="check-box"><?php echo (($detalle['referido'] ?? '') === 'No') ? 'X' : '&nbsp;'; ?></span> No</div></div>
      <div class="small-row"><div class="label-inline">A</div><div class="line-inline"><?php echo htmlspecialchars($detalle['referido_a'] ?? ''); ?></div></div>
    </section>

    <section class="section">
      <div class="section-title">FIRMA Y SELLO</div>
      <div class="small-row"><div class="label-inline">Firma médico</div><div class="line-inline"><?php echo htmlspecialchars($detalle['firma_medico'] ?? ''); ?></div></div>
      <div class="small-row"><div class="label-inline">Sello</div><div class="line-inline"><?php echo htmlspecialchars($detalle['sello_medico'] ?? ''); ?></div></div>
    
    </section>

    <section class="section clinical-data">
      <div class="section-title">DATOS CLÍNICOS</div>
      <div class="row">
        <div class="col-60">
          <div class="label">Alergias</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['alergias'] ?? '')); ?></div>

          <div class="label">Antecedentes</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['antecedentes'] ?? '')); ?></div>
        </div>

        <div class="col-40">
          <div class="label">Medicamentos actuales</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['medicamentos_actuales'] ?? '')); ?></div>

          <div class="small-row"><div class="label-inline">Peso</div><div class="line-inline"><?php echo htmlspecialchars($exp['peso'] ?? ''); ?></div></div>
          <div class="small-row"><div class="label-inline">Altura</div><div class="line-inline"><?php echo htmlspecialchars($exp['altura'] ?? ''); ?></div></div>

          <div class="label">Notas</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['notas'] ?? '')); ?></div>
        </div>
      </div>
    </section>

    <section class="section checks">
      <div class="section-title">CHEQUEOS MÉDICOS</div>
      <?php
      // Lista por defecto de chequeos (en español)
      $defaultChecks = [
        'Chequeo general', 'Signos vitales', 'Examen de sangre', 'Electrocardiograma',
        'Prueba de glucemia', 'Perfil lipídico', 'Radiografía', 'Ecografía',
        'Vacunación', 'Historia clínica', 'Control de peso', 'Control de la tensión'
      ];

      // Construir la lista de elementos a mostrar. Si el proyecto guarda una lista personalizada,
      // puede venir en `chequeos_lista`, `checks_list` o `chequeos_items` (CSV, JSON o array).
      $checksList = $defaultChecks;
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

      // Detectar casillas seleccionadas: se aceptan varios nombres de campo que contengan
      // las opciones marcadas (CSV, JSON o array). Prioriza `chequeos_seleccionados`.
      $selectedRaw = null;
      foreach (['chequeos_seleccionados','chequeos_selected','selected_checks','chequeos'] as $k) {
        if (!empty($exp[$k])) { $selectedRaw = $exp[$k]; break; }
      }
      $selected = [];
      if ($selectedRaw !== null) {
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
      // Normalizar a minúsculas para comparaciones insensibles a mayúsculas/acentos simples
      $selectedNorm = array_map('mb_strtolower', $selected);
      ?>
      <div class="checkbox-grid">
      <?php foreach ($checksList as $item):
        $checked = in_array(mb_strtolower($item), $selectedNorm, true);
      ?>
        <div class="checkbox-item">
        <span class="checkbox<?php echo $checked ? ' checked' : ''; ?>"><?php echo $checked ? '✔' : ''; ?></span>
        <?php echo htmlspecialchars($item); ?>
        </div>
      <?php endforeach; ?>
      <div class="checkbox-item"><span class="checkbox"></span> Otro: __________________________</div>
      </div>
    </section>

    <section class="section medical-note">
      <div class="section-title">ATENCION MEDICA REGISTRADA</div>
      <div class="row">
        <div class="col-100">
          <div class="small-row"><div class="label-inline">Medico que atendio</div><div class="line-inline"><?php echo htmlspecialchars($medicoAtencion ?: 'No especificado'); ?></div></div>
          <div class="small-row"><div class="label-inline">Fecha de atencion</div><div class="line-inline"><?php echo htmlspecialchars($fechaAtencion ?: 'No especificada'); ?></div></div>

          <div class="label">Diagnostico</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['diagnostico'] ?? '')); ?></div>

          <div class="label">Tratamiento</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['tratamiento'] ?? '')); ?></div>

          <div class="label">Observaciones</div>
          <div class="textblock"><?php echo nl2br(htmlspecialchars($exp['observaciones'] ?? '')); ?></div>
        </div>
      </div>
    </section>

    <section class="section medical-note">
      <div class="section-title">ULTIMA CONSULTA</div>
      <div class="row">
        <div class="col-100">
          <?php if ($ultimaConsulta): ?>
            <div class="small-row"><div class="label-inline">Motivo</div><div class="line-inline"><?php echo htmlspecialchars($ultimaConsulta['motivo_consulta'] ?? ''); ?></div></div>
            <div class="small-row"><div class="label-inline">Presion arterial</div><div class="line-inline"><?php echo htmlspecialchars($ultimaConsulta['presion_arterial'] ?? '-'); ?></div></div>
            <div class="small-row"><div class="label-inline">Temperatura</div><div class="line-inline"><?php echo htmlspecialchars($ultimaConsulta['temperatura'] ?? '-'); ?></div></div>
            <div class="small-row"><div class="label-inline">Frecuencia cardiaca</div><div class="line-inline"><?php echo htmlspecialchars($ultimaConsulta['frecuencia_cardiaca'] ?? '-'); ?></div></div>
            <div class="small-row"><div class="label-inline">Saturacion oxigeno</div><div class="line-inline"><?php echo htmlspecialchars($ultimaConsulta['saturacion_oxigeno'] ?? '-'); ?></div></div>
            <div class="label">Diagnostico de la consulta</div>
            <div class="textblock"><?php echo nl2br(htmlspecialchars($ultimaConsulta['diagnostico'] ?? '')); ?></div>
            <div class="label">Tratamiento de la consulta</div>
            <div class="textblock"><?php echo nl2br(htmlspecialchars($ultimaConsulta['tratamiento'] ?? '')); ?></div>
            <div class="label">Observaciones de la consulta</div>
            <div class="textblock"><?php echo nl2br(htmlspecialchars($ultimaConsulta['observaciones'] ?? '')); ?></div>
          <?php else: ?>
            <div class="textblock">No hay consultas estructuradas registradas.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section medical-note">
      <div class="section-title">HISTORIAL RECIENTE DE CONSULTAS</div>
      <div class="row">
        <div class="col-100">
          <?php if (count($consultasRecientes) === 0): ?>
            <div class="textblock">No hay historial reciente.</div>
          <?php else: ?>
            <?php foreach ($consultasRecientes as $i => $consulta): ?>
              <div class="textblock" style="margin-bottom:8px;">
                <strong><?php echo $i + 1; ?>.</strong>
                <?php echo date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])); ?> |
                <?php echo htmlspecialchars($consulta['medico_nombre'] ?: 'Medico no especificado'); ?> |
                Dx: <?php echo htmlspecialchars($consulta['diagnostico'] ?: 'Sin diagnostico'); ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section emergency">
      <div class="section-title">CONTACTO DE EMERGENCIA</div>
      <div class="row">
        <div class="col-100">
          <div class="label">Nombre completo</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['contacto_nombre'] ?? ''); ?></div>

          <div class="label">Teléfono</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['contacto_telefono'] ?? ''); ?></div>

          <div class="label">Parentesco</div>
          <div class="line-field"><?php echo htmlspecialchars($exp['contacto_relacion'] ?? ''); ?></div>
        </div>
      </div>
    </section>

    <footer class="pdf-footer">
      <div class="footer-left">504-9705-3977</div>
      <div class="footer-center">www.CISMEDIC.com</div>
      <div class="footer-right">v-300, ave22, Siguatepeque</div>
    </footer>
  </div>
</body>
</html>
