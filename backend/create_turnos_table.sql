-- Verificar si la tabla existe y crearla si no existe
CREATE TABLE IF NOT EXISTS turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_turno VARCHAR(10) NOT NULL,
    tipo_atencion VARCHAR(50) NOT NULL,
    id_modulo INT NOT NULL,
    estado ENUM('pendiente', 'en_atencion', 'finalizado') NOT NULL DEFAULT 'pendiente',
    fecha_creacion DATETIME NOT NULL,
    fecha_actualizacion DATETIME NOT NULL,
    FOREIGN KEY (id_modulo) REFERENCES modulos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar si las columnas existen y añadirlas si no existen
ALTER TABLE turnos
ADD COLUMN IF NOT EXISTS fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP; 