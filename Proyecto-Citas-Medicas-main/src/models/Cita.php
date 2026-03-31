<?php
/**
 * Clase para gestionar Citas
 */

class Cita {
    private $conn;
    private $tabla = 'citas';

    public function __construct($conexion) {
        $this->conn = $conexion;
    }

    /**
     * Obtener todas las citas
     */
    public function obtener_todas() {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                ORDER BY c.fecha DESC";
        return $this->conn->query($sql);
    }

    /**
     * Obtener cita por ID
     */
    public function obtener_por_id($id) {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                WHERE c.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Obtener citas por médico
     */
    public function obtener_por_medico($medico_id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE medico_id = ? ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $medico_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtener citas por paciente
     */
    public function obtener_por_paciente($email) {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                WHERE c.paciente_email = ? ORDER BY c.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Agregar nueva cita
     */
    public function agregar($paciente_nombre, $paciente_email, $paciente_telefono, 
                            $medico_id, $fecha, $hora, $motivo) {
        $sql = "INSERT INTO {$this->tabla} 
                (paciente_nombre, paciente_email, paciente_telefono, medico_id, fecha, hora, motivo, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssisss", $paciente_nombre, $paciente_email, $paciente_telefono, 
                          $medico_id, $fecha, $hora, $motivo);
        return $stmt->execute();
    }

    /**
     * Actualizar cita
     */
    public function actualizar($id, $fecha, $hora, $motivo, $estado) {
        $sql = "UPDATE {$this->tabla} SET fecha = ?, hora = ?, motivo = ?, estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $fecha, $hora, $motivo, $estado, $id);
        return $stmt->execute();
    }

    /**
     * Cambiar estado de cita
     */
    public function cambiar_estado($id, $estado) {
        $sql = "UPDATE {$this->tabla} SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }

    /**
     * Eliminar cita
     */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Obtener citas de hoy
     */
    public function obtener_de_hoy() {
        $hoy = date('Y-m-d');
        $sql = "SELECT c.*, m.nombre as medico_nombre 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                WHERE DATE(c.fecha) = ? ORDER BY c.hora ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $hoy);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Contar citas pendientes
     */
    public function contar_pendientes() {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE estado = 'pendiente'";
        $resultado = $this->conn->query($sql);
        $fila = $resultado->fetch_assoc();
        return $fila['total'];
    }
}

?>
