<?php
/**
 * Clase para gestionar Médicos
 */

class Medico {
    private $pdo;
    private $tabla = 'medicos';

    public function __construct($conexion) {
        $this->pdo = $conexion;
    }

    /**
     * Login de médico
     */
    public function login($email, $password) {
        $sql = "SELECT * FROM {$this->tabla} WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        $usuario = $stmt->fetch();

        if ($usuario && !empty($usuario['password']) && password_verify($password, $usuario['password'])) {
            return $usuario;
        }

        return false;
    }

    /**
     * Obtener médico por ID
     */
    public function obtener_por_id($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
