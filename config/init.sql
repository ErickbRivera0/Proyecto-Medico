-- Crear tabla de médicos
CREATE TABLE IF NOT EXISTS medicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de citas
CREATE TABLE IF NOT EXISTS citas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paciente_nombre VARCHAR(255) NOT NULL,
    paciente_email VARCHAR(255) NOT NULL,
    paciente_telefono VARCHAR(20) NOT NULL,
    medico_id INT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo LONGTEXT,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE SET NULL
);

-- Insertar médicos de ejemplo
INSERT INTO medicos (nombre, especialidad, telefono, email) VALUES
('Dr. Carlos González', 'Cardiología', '504-2234-5678', 'carlos@clinica.com'),
('Dra. María López', 'Dermatología', '504-2234-5679', 'maria@clinica.com'),
('Dr. Juan Rodríguez', 'Ortopedia', '504-2234-5680', 'juan@clinica.com'),
('Dra. Ana Martínez', 'Neurología', '504-2234-5681', 'ana@clinica.com'),
('Dr. Pedro Sánchez', 'Oftalmología', '504-2234-5682', 'pedro@clinica.com');

-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL DEFAULT 'usuario',
    estado VARCHAR(50) NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
