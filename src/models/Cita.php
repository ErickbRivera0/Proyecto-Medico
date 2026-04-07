<?php
/**
 * Clase para gestionar Citas
 */

class Cita {
    private $pdo;
    private $tabla = 'citas';

    public function __construct($conexion) {
        $this->pdo = $conexion;
    }

    /**
     * Obtener todas las citas
     */
    public function obtener_todas() {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                ORDER BY c.fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Obtener cita por ID
     */
    public function obtener_por_id($id) {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                WHERE c.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Obtener citas por médico
     */
    public function obtener_por_medico($medico_id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE medico_id = ? ORDER BY fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $medico_id);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Obtener citas por paciente
     */
    public function obtener_por_paciente($email) {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.especialidad 
                FROM {$this->tabla} c 
                LEFT JOIN medicos m ON c.medico_id = m.id 
                WHERE c.paciente_email = ? ORDER BY c.fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Agregar nueva cita
     */
    public function agregar($paciente_nombre, $paciente_email, $paciente_telefono, 
                            $medico_id, $fecha, $hora, $motivo) {
        $sql = "INSERT INTO {$this->tabla} 
                (paciente_nombre, paciente_email, paciente_telefono, medico_id, fecha, hora, motivo, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $paciente_nombre);
        $stmt->bindParam(2, $paciente_email);
        $stmt->bindParam(3, $paciente_telefono);
        $stmt->bindParam(4, $medico_id);
        $stmt->bindParam(5, $fecha);
        $stmt->bindParam(6, $hora);
        $stmt->bindParam(7, $motivo);
        return $stmt->execute();
    }

    /**
     * Actualizar cita
     */
    public function actualizar($id, $fecha, $hora, $motivo, $estado) {
        $sql = "UPDATE {$this->tabla} SET fecha = ?, hora = ?, motivo = ?, estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $fecha);
        $stmt->bindParam(2, $hora);
        $stmt->bindParam(3, $motivo);
        $stmt->bindParam(4, $estado);
        $stmt->bindParam(5, $id);
        return $stmt->execute();
    }

    /**
     * Cambiar estado de cita
     */
    public function cambiar_estado($id, $estado) {
        $sql = "UPDATE {$this->tabla} SET estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $estado);
        $stmt->bindParam(2, $id);
        return $stmt->execute();
    }

    /**
     * Eliminar cita
     */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $id);
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $hoy);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Contar citas pendientes
     */
    public function contar_pendientes() {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE estado = 'pendiente'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
}

?>
