<?php
/**
 * Clase para gestionar Médicos
 */

class Medico {
    private $conn;
    private $tabla = 'medicos';

    public function __construct($conexion) {
        $this->conn = $conexion;
    }

    /**
     * Obtener todos los médicos
     */
    public function obtener_todos() {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY nombre ASC";
        return $this->conn->query($sql);
    }

    /**
     * Obtener médico por ID
     */
    public function obtener_por_id($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Obtener médicos por especialidad
     */
    public function obtener_por_especialidad($especialidad) {
        $sql = "SELECT * FROM {$this->tabla} WHERE especialidad LIKE ? ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $param = "%$especialidad%";
        $stmt->bind_param("s", $param);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Agregar nuevo médico
     */
    public function agregar($nombre, $especialidad, $telefono, $email) {
        $sql = "INSERT INTO {$this->tabla} (nombre, especialidad, telefono, email) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $especialidad, $telefono, $email);
        return $stmt->execute();
    }

    /**
     * Actualizar médico
     */
    public function actualizar($id, $nombre, $especialidad, $telefono, $email) {
        $sql = "UPDATE {$this->tabla} SET nombre = ?, especialidad = ?, 
                telefono = ?, email = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $nombre, $especialidad, $telefono, $email, $id);
        return $stmt->execute();
    }

    /**
     * Eliminar médico
     */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Contar médicos
     */
    public function contar() {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla}";
        $resultado = $this->conn->query($sql);
        $fila = $resultado->fetch_assoc();
        return $fila['total'];
    }
}

?>
