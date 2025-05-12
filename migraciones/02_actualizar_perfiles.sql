-- Estructura de tabla para la tabla `perfiles`
CREATE TABLE `perfiles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `perfiles`
INSERT INTO `perfiles` (`id`, `nombre`) VALUES
(1, 'Módulos Generales (Atención, Certificados, Apostilla, Inscripción)'),
(2, 'Módulos SAE');