-- Índices para la tabla turnos
ALTER TABLE turnos ADD INDEX idx_numero_turno (numero_turno);
ALTER TABLE turnos ADD INDEX idx_estado (estado);
ALTER TABLE turnos ADD INDEX idx_tipo_atencion (tipo_atencion);
ALTER TABLE turnos ADD INDEX idx_creado_en (creado_en);
ALTER TABLE turnos ADD INDEX idx_hora_llamado (hora_llamado);

-- Índice compuesto para búsquedas frecuentes
ALTER TABLE turnos ADD INDEX idx_estado_tipo_atencion (estado, tipo_atencion);

-- Índices para la tabla tipos_atencion_modulos
ALTER TABLE tipos_atencion_modulos ADD INDEX idx_tipo_atencion (tipo_atencion);

-- Índices para la tabla usuarios
ALTER TABLE usuarios ADD INDEX idx_perfil (perfil_id);

-- Índices para la tabla modulos
ALTER TABLE modulos ADD INDEX idx_estado (estado);

-- Optimizar tablas
OPTIMIZE TABLE turnos;
OPTIMIZE TABLE tipos_atencion_modulos;
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE modulos; 