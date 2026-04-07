-- Establecer collation de la base de datos
ALTER DATABASE citas_medicas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
('Dr. Carlos Gonzalez', 'Cardiologia', '504-2234-5678', 'carlos@clinica.com'),
('Dra. Maria Lopez', 'Dermatologia', '504-2234-5679', 'maria@clinica.com'),
('Dr. Juan Rodriguez', 'Ortopedia', '504-2234-5680', 'juan@clinica.com'),
('Dra. Ana Martinez', 'Neurologia', '504-2234-5681', 'ana@clinica.com'),
('Dr. Pedro Sanchez', 'Oftalmologia', '504-2234-5682', 'pedro@clinica.com');

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

-- Crear tabla de expedientes (historial clínico del paciente)
CREATE TABLE IF NOT EXISTS expedientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paciente_email VARCHAR(255) NOT NULL,
    nombre VARCHAR(150),
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    sexo ENUM('M','F','O') DEFAULT 'O',
    direccion VARCHAR(255),
    alergias TEXT,
    antecedentes TEXT,
    medicamentos_actuales TEXT,
    peso VARCHAR(20),
    altura VARCHAR(20),
    notas LONGTEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_expediente_email (paciente_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna para vincular una cita con un expediente (si aún no existe)
ALTER TABLE citas ADD COLUMN expediente_id INT DEFAULT NULL;

-- Añadir columna para almacenar chequeos seleccionados en el expediente (CSV o JSON)
ALTER TABLE expedientes ADD COLUMN chequeos_seleccionados TEXT DEFAULT NULL;
