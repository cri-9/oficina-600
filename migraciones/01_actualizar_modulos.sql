-- Estructura de tabla (no cambia)
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `id_perfil` int(11) NOT NULL,
  `codigo` char(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos actualizado:
INSERT INTO `modulos` (`id`, `nombre`, `id_perfil`, `codigo`) VALUES
(1, 'Modulo 1 G,C,A,B,M', 1, 'G'), -- General
(2, 'Modulo 2 G,C,A,B,M', 1, 'G'),
(3, 'Modulo 3 G,C,A,B,M', 1, 'G'),
(4, 'Modulo 4 G,C,A,B,M', 1, 'G'),
(5, 'Modulo 5 S', 4, 'S'),         -- SAE
(6, 'Modulo 6 S', 4, 'S'),
(7, 'Modulo 7 S', 4, 'S');
-- Notas:
-- id_perfil de módulos 1-4 podría repetirse (1,2,3,5,6) según a qué perfil quieras asociarlos, 
-- pero puedes dejarlo así y hacer la relación en tu lógica de aplicación.