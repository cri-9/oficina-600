-- =============================================
-- SQL DE MIGRACIÓN Y CREACIÓN ÓPTIMA PARA 'turnos'
-- =============================================

-- 1. Respaldar la tabla original antes de cualquier cambio
CREATE TABLE IF NOT EXISTS turnos_backup_20240508 AS SELECT * FROM turnos;

-- 2. Crear la nueva tabla 'turnos' normalizada
CREATE TABLE IF NOT EXISTS turnos_new (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    numero_turno VARCHAR(10) NOT NULL,
    tipo_atencion VARCHAR(255) NOT NULL,
    estado ENUM('pendiente','en_atencion','finalizado') NOT NULL DEFAULT 'pendiente',
    fecha_atencion DATETIME DEFAULT NULL,
    id_modulo INT(11) NOT NULL,
    id_usuario INT(11) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalizado_en DATETIME DEFAULT NULL,
    llamado TINYINT(1) DEFAULT 0,
    -- Si necesitas algún campo extra, agrégalo aquí.
    CONSTRAINT fk_turnos_modulo_new FOREIGN KEY (id_modulo) REFERENCES modulos(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Migrar datos (ajusta los campos si cambian)
INSERT INTO turnos_new (id, numero_turno, tipo_atencion, estado, fecha_atencion, id_modulo, id_usuario, creado_en, fecha_actualizacion, finalizado_en, llamado)
SELECT
    id,
    numero_turno,
    tipo_atencion,
    estado,
    fecha_atencion,
    id_modulo,
    id_usuario,
    IFNULL(creado_en, fecha_creacion),
    IFNULL(fecha_actualizacion, fecha_creacion),
    finalizado_en,
    IFNULL(llamado,0)
FROM turnos;

-- 4. Renombrar tablas: antigua a "_old" y nueva a principal
RENAME TABLE turnos TO turnos_old, turnos_new TO turnos;

-- 5. Indices recomendados
CREATE INDEX idx_turnos_estado ON turnos(estado);
CREATE INDEX idx_turnos_tipo_atencion ON turnos(tipo_atencion);
CREATE INDEX idx_turnos_modulo ON turnos(id_modulo);

-- 6. (Opcional) Limpia la tabla vieja si ya validaste todo:
-- DROP TABLE turnos_old;

-- === FIN DE MIGRACIÓN ===