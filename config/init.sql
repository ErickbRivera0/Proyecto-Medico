-- Establecer collation de la base de datos
ALTER DATABASE citas_medicas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear tabla de médicos
CREATE TABLE IF NOT EXISTS medicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(255),
    hora_inicio TIME DEFAULT '08:00',
    hora_fin TIME DEFAULT '17:00',
    dias_trabajo VARCHAR(50) DEFAULT 'Lunes,Martes,Miercoles,Jueves,Viernes',
    activo TINYINT DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    cancelada_por VARCHAR(50),
    motivo_cancelacion TEXT,
    asistencia ENUM('asistio', 'no_asistio', 'sin_confirmar') DEFAULT 'sin_confirmar',
    email_confirmacion_enviado TINYINT DEFAULT 0,
    email_recordatorio_enviado TINYINT DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE SET NULL,
    INDEX idx_fecha_estado (fecha, estado),
    INDEX idx_paciente_email (paciente_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    fecha_ultimo_login DATETIME,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    INDEX idx_rol_estado (rol, estado)
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

-- Columna estructurada para el formato ampliado del expediente clínico
ALTER TABLE expedientes ADD COLUMN expediente_detalle_json LONGTEXT DEFAULT NULL;

-- Tabla de consultas clinicas por expediente (historial estructurado)
CREATE TABLE IF NOT EXISTS expediente_consultas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediente_id INT NOT NULL,
    cita_id INT DEFAULT NULL,
    medico_id INT DEFAULT NULL,
    medico_nombre VARCHAR(255) DEFAULT NULL,
    motivo_consulta TEXT,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    presion_arterial VARCHAR(20) DEFAULT NULL,
    temperatura VARCHAR(10) DEFAULT NULL,
    frecuencia_cardiaca VARCHAR(10) DEFAULT NULL,
    saturacion_oxigeno VARCHAR(10) DEFAULT NULL,
    fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expediente_fecha (expediente_id, fecha_consulta),
    INDEX idx_cita (cita_id),
    CONSTRAINT fk_consulta_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_consulta_medico FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE SET NULL,
    CONSTRAINT fk_consulta_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de calificaciones de médicos
CREATE TABLE IF NOT EXISTS calificaciones_medicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cita_id INT NOT NULL,
    medico_id INT NOT NULL,
    paciente_email VARCHAR(255) NOT NULL,
    calificacion INT DEFAULT 5 COMMENT '1-5 estrellas',
    comentario TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cita_calificacion (cita_id),
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    INDEX idx_medico_calificacion (medico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de registro de emails enviados
CREATE TABLE IF NOT EXISTS email_notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL COMMENT 'confirmacion,recordatorio,cancelacion',
    cita_id INT,
    destinatario VARCHAR(255) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    enviado TINYINT DEFAULT 0,
    fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_mensaje TEXT,
    fecha_exito DATETIME,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    INDEX idx_tipo_cita (tipo, cita_id),
    INDEX idx_enviado (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
