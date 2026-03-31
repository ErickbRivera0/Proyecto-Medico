<?php
/**
 * EJEMPLOS DE USO DE LAS CLASES
 * 
 * Este archivo muestra cómo usar las clases Medico y Cita
 * que están disponibles en la carpeta models/
 */

// Incluir configuración y clases
require_once 'config.php';
require_once 'models/Medico.php';
require_once 'models/Cita.php';

// ====================================================================
// EJEMPLO 1: TRABAJAR CON MÉDICOS
// ====================================================================

/*
// Crear instancia de Medico
$medico = new Medico($conn);

// Obtener todos los médicos
$resultado = $medico->obtener_todos();
while ($fila = $resultado->fetch_assoc()) {
    echo $fila['nombre'] . " - " . $fila['especialidad'] . "<br>";
}

// Obtener médico específico
$medico_data = $medico->obtener_por_id(1);
echo "Médico: " . $medico_data['nombre'] . "<br>";

// Obtener médicos por especialidad
$cardiologo = $medico->obtener_por_especialidad('Cardiología');
while ($fila = $cardiologo->fetch_assoc()) {
    echo $fila['nombre'] . "<br>";
}

// Agregar nuevo médico
$medico->agregar('Dr. Luis García', 'Cirugía', '504-2234-5683', 'luis@clinica.com');

// Actualizar médico
$medico->actualizar(1, 'Dr. Carlos González', 'Cardiología', '504-2234-5678', 'carlos.updated@clinica.com');

// Eliminar médico
$medico->eliminar(10);

// Contar total de médicos
$total = $medico->contar();
echo "Total de médicos: " . $total . "<br>";
*/

// ====================================================================
// EJEMPLO 2: TRABAJAR CON CITAS
// ====================================================================

/*
// Crear instancia de Cita
$cita = new Cita($conn);

// Obtener todas las citas
$resultado = $cita->obtener_todas();
while ($fila = $resultado->fetch_assoc()) {
    echo $fila['paciente_nombre'] . " - " . $fila['fecha'] . "<br>";
}

// Obtener cita específica
$cita_data = $cita->obtener_por_id(1);
echo "Cita: " . $cita_data['paciente_nombre'] . "<br>";

// Obtener citas de un médico
$citas_medico = $cita->obtener_por_medico(1);
while ($fila = $citas_medico->fetch_assoc()) {
    echo $fila['paciente_nombre'] . "<br>";
}

// Obtener citas de un paciente
$citas_paciente = $cita->obtener_por_paciente('juan@example.com');
while ($fila = $citas_paciente->fetch_assoc()) {
    echo $fila['paciente_nombre'] . " - " . $fila['fecha'] . "<br>";
}

// Agregar nueva cita
$cita->agregar(
    'Mario López',
    'mario@example.com',
    '504-1234-5678',
    1,  // medico_id
    '2026-02-15',
    '10:30',
    'Revisión general'
);

// Actualizar cita
$cita->actualizar(
    1,
    '2026-02-20',
    '14:00',
    'Chequeo completo',
    'confirmada'
);

// Cambiar estado de cita
$cita->cambiar_estado(1, 'completada');

// Eliminar cita
$cita->eliminar(5);

// Obtener citas de hoy
$citas_hoy = $cita->obtener_de_hoy();
echo "Citas de hoy: " . $citas_hoy->num_rows . "<br>";

// Contar citas pendientes
$pendientes = $cita->contar_pendientes();
echo "Citas pendientes: " . $pendientes . "<br>";
*/

// ====================================================================
// EJEMPLO 3: CREAR UN CONTROLADOR PERSONALIZADO
// ====================================================================

/*
// Archivo: controllers/CitasController.php

class CitasController {
    private $cita;
    private $medico;
    
    public function __construct($conexion) {
        $this->cita = new Cita($conexion);
        $this->medico = new Medico($conexion);
    }
    
    // Obtener estadísticas
    public function obtener_estadisticas() {
        return [
            'total_citas' => $this->obtener_total_citas(),
            'citas_pendientes' => $this->cita->contar_pendientes(),
            'total_medicos' => $this->medico->contar(),
        ];
    }
    
    // Agendar cita con validaciones
    public function agendar_cita($datos) {
        // Validar email
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Email inválido'];
        }
        
        // Validar fecha no sea en el pasado
        if (strtotime($datos['fecha']) < strtotime('today')) {
            return ['error' => 'Fecha debe ser futura'];
        }
        
        // Verificar disponibilidad del médico
        // ... código de validación ...
        
        // Agregar cita
        $resultado = $this->cita->agregar(
            $datos['nombre'],
            $datos['email'],
            $datos['telefono'],
            $datos['medico_id'],
            $datos['fecha'],
            $datos['hora'],
            $datos['motivo']
        );
        
        if ($resultado) {
            return ['exito' => 'Cita agendada correctamente'];
        } else {
            return ['error' => 'Error al agendar la cita'];
        }
    }
    
    // Obtener total de citas
    private function obtener_total_citas() {
        $resultado = $this->cita->obtener_todas();
        return $resultado->num_rows;
    }
}
*/

?>
