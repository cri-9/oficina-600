-- =============================================
-- SQL de migración y estructura óptima para tabla 'turnos'
-- Oficina600 - Normalización de turnos y consistencia referencial
--
-- PASOS:
-- 1. Respaldar tu tabla (copia de seguridad)
-- 2. Crear nueva tabla normalizada
-- 3. Migrar los datos ("modulo" a "id_modulo" usando lookups)
-- 4. Renombrar tablas para dejar la nueva como activa
-- =============================================

-- 1. Backup de la tabla actual (¡Siempre recomendado antes de migrar!)
CREATE TABLE turnos_backup_20240504 AS SELECT * FROM turnos;

-- 2. Crear la nueva estructura normalizada
CREATE TABLE turnos_new (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    numero_turno VARCHAR(10) NOT NULL,
    tipo_atencion VARCHAR(255) NOT NULL,
    estado ENUM('pendiente','en_atencion','finalizado') DEFAULT 'pendiente',
    id_modulo INT(11) NOT NULL,
    id_usuario INT(11) DEFAULT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    finalizado_en DATETIME DEFAULT NULL,
    -- Si usas historial o auditoría, puedes agregar campos adicionales aquí
    CONSTRAINT fk_turnos_modulo FOREIGN KEY (id_modulo) REFERENCES modulos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Copiar datos antiguos a la nueva estructura usando join por módulo
--    Se asume que el campo 'modulo' viejo es el id numérico (¡confírmalo antes!)
--
--    Si en tu tabla original 'turnos.modulo' guarda el 'nombre' o el 'id' del módulo:
--    - Si es el NOMBRE, debes hacer un join por nombre.
--    - Si es el ID (parece serlo por los inserts), puedes usarlo directo.

INSERT INTO turnos_new (id, numero_turno, tipo_atencion, estado, id_modulo, id_usuario, creado_en, finalizado_en)
SELECT
    t.id,
    t.numero_turno,
    t.tipo_atencion,
    t.estado,
    CAST(t.modulo AS UNSIGNED) AS id_modulo, -- Asume que 'modulo' es el id
    t.id_usuario,
    t.creado_en,
    t.finalizado_en
FROM turnos t;

-- 4. Renombrar tablas (swap names)
RENAME TABLE turnos TO turnos_old, turnos_new TO turnos;

-- 5. (Opcional) Agrega índices adicionales si los necesitas
CREATE INDEX idx_turnos_estado ON turnos(estado);
CREATE INDEX idx_turnos_tipo ON turnos(tipo_atencion);

-- 6. (Opcional) Elimina la columna antigua 'numero' si sigue en tu backup viejo
-- ALTER TABLE turnos DROP COLUMN numero;

-- === FIN DE MIGRACIÓN ===

-- ¡Tu tabla 'turnos' ya está lista, limpia y referenciada!
-- No olvides adaptar tus scripts PHP para que utilicen el campo id_modulo (entero) y el campo estado como bandera de lógica.