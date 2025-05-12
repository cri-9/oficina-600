-- Verificar si la tabla existe y crearla si no existe
CREATE TABLE IF NOT EXISTS historial_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_turno INT NOT NULL,
    numero_turno VARCHAR(10) NOT NULL,
    id_modulo INT NOT NULL,
    id_usuario INT NOT NULL,
    perfil_id INT NOT NULL,
    fecha_creacion DATETIME NOT NULL,
    fecha_finalizacion DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_turno) REFERENCES turnos(id),
    FOREIGN KEY (id_modulo) REFERENCES modulos(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 