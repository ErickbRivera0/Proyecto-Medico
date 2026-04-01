<?php
// Plantilla HTML para generar el PDF del expediente.
// Usa la variable $exp (registro de la base de datos) cuando se incluye.
if (!isset($exp)) $exp = [];
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
