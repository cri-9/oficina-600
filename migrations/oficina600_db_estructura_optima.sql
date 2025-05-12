-- Estructura optimizada para oficina600_db

CREATE DATABASE IF NOT EXISTS oficina600_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE oficina600_db;

-- --------------------------------------------------------
-- Tabla perfiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfiles (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO perfiles (id, nombre) VALUES
  (1, 'Apostilla'),
  (2, 'SAE'),
  (3, 'Inscripción');

-- --------------------------------------------------------
-- Tabla modulos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS modulos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  id_perfil INT(11) NOT NULL,
  codigo CHAR(1) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_modulos_id_perfil (id_perfil),
  CONSTRAINT fk_modulos_perfil FOREIGN KEY (id_perfil) REFERENCES perfiles(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO modulos (id, nombre, id_perfil, codigo) VALUES
  (1, 'Modulo 1 A', 1, 'A'),
  (2, 'Modulo 2 A', 1, 'A'),
  (3, 'Modulo 3 I', 3, 'I'),
  (4, 'Modulo 4 C', 3, 'C'), -- Ajusta id_perfil si "C" es otro perfil
  (5, 'Modulo 1 S', 2, 'S'),
  (6, 'Modulo 2 S', 2, 'S'),
  (7, 'Modulo 3 S', 2, 'S');

-- --------------------------------------------------------
-- Tabla usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  perfil INT(11) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_usuarios_perfil (perfil),
  CONSTRAINT fk_usuarios_perfiles FOREIGN KEY (perfil) REFERENCES perfiles(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla turnos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS turnos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  numero_turno VARCHAR(10) NOT NULL,
  tipo_atencion VARCHAR(255) NOT NULL,
  estado ENUM('pendiente', 'en_atencion', 'finalizado') NOT NULL DEFAULT 'pendiente',
  id_modulo INT(11) NOT NULL,
  id_usuario INT(11) DEFAULT NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  hora_llamado DATETIME DEFAULT NULL,
  finalizado_en DATETIME DEFAULT NULL,
  llamado TINYINT(1) DEFAULT 0,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fecha_finalizado DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_turnos_estado (estado),
  KEY idx_turnos_tipo_atencion (tipo_atencion),
  KEY idx_turnos_modulo (id_modulo),
  CONSTRAINT fk_turnos_modulo FOREIGN KEY (id_modulo) REFERENCES modulos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla historial_llamados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_llamados (
  id INT(11) NOT NULL AUTO_INCREMENT,
  turno_id INT(11) NOT NULL,
  usuario_id INT(11) NOT NULL,
  fecha_llamado DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_historial_turno (turno_id),
  KEY idx_historial_usuario (usuario_id),
  CONSTRAINT fk_historial_turno FOREIGN KEY (turno_id) REFERENCES turnos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla historial_turnos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_turnos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  id_turno INT(11) DEFAULT NULL,
  numero VARCHAR(10) DEFAULT NULL,
  modulo VARCHAR(50) DEFAULT NULL,
  id_usuario INT(11) DEFAULT NULL,
  perfil VARCHAR(50) DEFAULT NULL,
  creado_en DATETIME DEFAULT NULL,
  finalizado_en DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla tipos_atencion_modulos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_atencion_modulos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  tipo_atencion VARCHAR(50) NOT NULL,
  id_modulo INT(11) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tam_id_modulo (id_modulo),
  CONSTRAINT fk_tam_modulo FOREIGN KEY (id_modulo) REFERENCES modulos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Puedes añadir los INSERTs para tipos_atencion_modulos aquí
-- INSERT INTO tipos_atencion_modulos (id, tipo_atencion, id_modulo) VALUES
-- (1, 'Solicitud Certificados', 1), ... etc

-- --------------------------------------------------------
-- Configuración de auto_increment inicial para las tablas principales, si es necesario:
ALTER TABLE modulos AUTO_INCREMENT = 8;
ALTER TABLE tipos_atencion_modulos AUTO_INCREMENT = 7;
ALTER TABLE turnos AUTO_INCREMENT = 71;
ALTER TABLE historial_llamados AUTO_INCREMENT = 1;
ALTER TABLE historial_turnos AUTO_INCREMENT = 1;
ALTER TABLE usuarios AUTO_INCREMENT = 1;

-- Fin del esquema optimizado