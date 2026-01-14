-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 14-01-2026 a las 19:23:42
-- Versión del servidor: 10.11.14-MariaDB-ubu2204-log
-- Versión de PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `agrocosta_empleados`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `empleado` varchar(100) DEFAULT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `correo_jefe` varchar(100) DEFAULT NULL,
  `motivo` varchar(50) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `observacion_jefe` text DEFAULT NULL,
  `archivo_soporte` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`id`, `empleado`, `cedula`, `cargo`, `correo_jefe`, `motivo`, `estado`, `fecha`, `fecha_inicio`, `fecha_fin`, `hora_inicio`, `hora_fin`, `notas`, `observacion_jefe`, `archivo_soporte`) VALUES
(5, 'Ana Milena Castilla Contreras', '1045728401', 'Servicios Generales', 'pmancini@agro-costa.com', 'Cita Médica', 'Aprobado', '2026-01-14 17:35:27', '2026-01-16', '2026-01-16', '02:00:00', '05:00:00', 'cita con especialidad', 'ok', '1768412127_soporte.pdf'),
(11, 'Fabrizio Galiano', '1045728398', 'Asesor Comercial', 'pmancini@agro-costa.com', 'Día de la Familia', 'Rechazado', '2026-01-14 18:30:21', '2026-01-15', '2026-01-15', '00:00:00', '00:00:00', '', 'no', NULL),
(13, 'Fabrizio Galiano', '1045728398', 'Asesor Comercial', 'paolomanciniv@gmail.com', 'Licencia No Remunerada', 'Aprobado', '2026-01-14 18:40:07', '2026-01-17', '2026-01-17', '00:00:00', '00:00:00', '', 'ok', NULL),
(14, 'Ana Milena Castilla Contreras', '1045728401', 'Servicios Generales', 'pmancini@agro-costa.com', 'Día de la Familia', 'Pendiente', '2026-01-14 18:52:39', '2026-01-16', '2026-01-16', '08:00:00', '10:20:00', '', NULL, NULL),
(15, 'Fabrizio Galiano', '1045728398', 'Asesor Comercial', 'paolomanciniv@gmail.com', 'Calamidad Doméstica', 'Rechazado', '2026-01-14 18:55:36', '2026-01-14', '2026-01-14', '00:00:00', '00:00:00', '', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `cedula` varchar(20) DEFAULT '',
  `cargo` varchar(100) DEFAULT '',
  `area` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `rol`, `correo`, `cedula`, `cargo`, `area`) VALUES
(1, 'Ronaldo', 'Agr20201*', 'RRHH Agro-Costa', 'admin', 'rrosado@agro-costa.com', '000000', 'Jefe RRHH', 'Administración'),
(2, 'paolomanciniv@gmail.com', 'paolo123', 'Paolo Mancini', 'jefe', 'paolomanciniv@gmail.com', '12345678', 'Jefe de Operaciones', 'Operaciones'),
(3, 'fabrizio', 'paolo123', 'Fabrizio Galiano', 'empleado', 'paolovittoriomancini15@gmail.com', '1045728398', 'Asesor Comercial', 'Comercial'),
(4, 'oscar', 'paolo123', 'Oscar Olivo', 'empleado', 'oscar@gmail.com', '1045728399', 'Asesor Comercial', 'Comercial'),
(5, 'julio', 'paolo123', 'Julio', 'empleado', 'julio@gmail.com', '1045728400', 'Asesor Comercial', 'Comercial'),
(6, 'ana', 'paolo123', 'Ana Milena Castilla Contreras', 'empleado', 'ana@gmail.com', '1045728401', 'Servicios Generales', 'Mantenimiento'),
(7, 'josemiguel', 'paolo123', 'Jose Miguel', 'empleado', 'josemiguel@gmail.com', '1045728402', 'Conductor', 'Logística');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
