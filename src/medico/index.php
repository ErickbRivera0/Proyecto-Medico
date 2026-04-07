<?php
session_start();

// Si el médico ya está autenticado, ir al panel. Si no, ir al login.
if (isset($_SESSION['usuario']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'medico') {
    header('Location: ../medico_panel.php');
    exit();
}

header('Location: ../medico_login.php');
exit();
