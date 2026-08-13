-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: db_sistemas
-- Tiempo de generación: 12-08-2026 a las 22:15:43
-- Versión del servidor: 8.0.45
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `OPI`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas_gestion`
--

CREATE TABLE `areas_gestion` (
  `id` int NOT NULL,
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `areas_gestion`
--

INSERT INTO `areas_gestion` (`id`, `codigo`, `nombre`, `activo`) VALUES
(1, 'AG 01', 'Gestión Interna', 1),
(2, 'AG 02', 'Servicios Comunitarios', 1),
(3, 'S/I', 'Sin Imputación', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centros_costo`
--

CREATE TABLE `centros_costo` (
  `id` int NOT NULL,
  `codigo_cuenta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `anio_fiscal` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `centros_costo`
--

INSERT INTO `centros_costo` (`id`, `codigo_cuenta`, `nombre`, `anio_fiscal`, `activo`) VALUES
(1, '11', 'Depto. Informática', 2026, 1),
(2, '12', 'Administración', 2026, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones_sistema`
--

CREATE TABLE `configuraciones_sistema` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Volcado de datos para la tabla `configuraciones_sistema`
--

INSERT INTO `configuraciones_sistema` (`clave`, `valor`, `descripcion`) VALUES
('extensiones_permitidas', 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png', NULL),
('limite_peso_adjunto_mb', '50', 'Límite máximo de peso de archivos adjuntos en Megabytes (MB)'),
('modo_mantenimiento', '0', 'Indica si el sistema está en mantenimiento (1 = Sí, 0 = No)'),
('valor_utm', '66000', 'Valor de 1 UTM en pesos chilenos (CLP)');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_maestras`
--

CREATE TABLE `cuentas_maestras` (
  `id` int NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `presupuesto_global_total` decimal(15,2) DEFAULT '0.00',
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas_maestras`
--

INSERT INTO `cuentas_maestras` (`id`, `codigo`, `nombre`, `presupuesto_global_total`, `activo`) VALUES
(1, '215-22-04-001', 'Materiales de Oficina', 0.00, 1),
(3, '215-29-06-001', 'Equipos computacionales y periféricos', 0.00, 1),
(4, '215-24-01-007-001', 'Canastas', 0.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_presupuestarias`
--

CREATE TABLE `cuentas_presupuestarias` (
  `id` int NOT NULL,
  `centro_costo_id` int NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `presupuesto_inicial` decimal(15,2) DEFAULT '0.00',
  `monto_comprometido` decimal(15,2) DEFAULT '0.00',
  `monto_ejecutado` decimal(15,2) DEFAULT '0.00',
  `saldo_disponible` decimal(15,2) GENERATED ALWAYS AS (((`presupuesto_inicial` - `monto_comprometido`) - `monto_ejecutado`)) VIRTUAL,
  `activo` tinyint(1) DEFAULT '1',
  `area_gestion_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuentas_presupuestarias`
--

INSERT INTO `cuentas_presupuestarias` (`id`, `centro_costo_id`, `codigo`, `nombre`, `presupuesto_inicial`, `monto_comprometido`, `monto_ejecutado`, `activo`, `area_gestion_id`) VALUES
(1, 1, '215-22-04-001', 'Materiales de Oficina', NULL, 0.00, 0.00, 1, 1),
(3, 1, '215-22-04-003', 'Equipamiento Computacional', NULL, 0.00, 0.00, 1, 1),
(4, 1, '215-22-04-009', 'Insumos computacionales y repuestos', NULL, 0.00, 0.00, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_tramite`
--

CREATE TABLE `estados_tramite` (
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rol_responsable` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_tramite`
--

INSERT INTO `estados_tramite` (`codigo`, `nombre`, `rol_responsable`, `descripcion`) VALUES
('ANULADO', 'Anulado por Usuario', 'SISTEMA', NULL),
('BORRADOR', 'Borrador / Ingreso de Solicitud', 'USUARIO_REQ', NULL),
('EN_APROBACION_ADMINISTRADOR', 'Firma y Emisión de OPI', 'ADMIN_MUNICIPAL', NULL),
('EN_AUTORIZACION_COTIZACION', 'Autorización de Cotización', 'ADMIN_MUNICIPAL', NULL),
('EN_CORRECCION', 'Devuelto para Corrección', 'USUARIO_REQ', NULL),
('EN_COTIZACION_ADQ', 'Búsqueda de Cotizaciones', 'ADQUISICIONES', NULL),
('EN_EMISION_OC', 'Emisión y Envío de Orden de Compra', 'ADQUISICIONES', NULL),
('EN_EVALUACION_OFERTAS', 'Evaluación y Selección de Ofertas', 'USUARIO_REQ', NULL),
('EN_GESTION_ADQUISICIONES', 'Gestión de Adjudicación Directa', 'ADQUISICIONES', NULL),
('EN_REVISION_JEFATURA', 'Visación de Jefatura', 'JEFE_UNIDAD', NULL),
('EN_VALIDACION_PRESUPUESTARIA', 'Reserva de Presupuesto Inicial', 'PRESUPUESTO', NULL),
('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'Visación de Gasto Definitivo', 'PRESUPUESTO', NULL),
('ESPERANDO_ACEPTACION_OC', 'Esperando Aceptación del Proveedor', 'ADQUISICIONES', NULL),
('ESPERANDO_CDP_FINANZAS', 'Espera de CDP por Finanzas', 'FINANZAS', 'Expediente enviado a Finanzas para adjuntar el Certificado de Disponibilidad Presupuestaria'),
('ESPERANDO_CDP_FINANZAS_FINAL', 'Espera de CDP Final por Finanzas', 'FINANZAS', 'Expediente enviado a Finanzas para adjuntar el Certificado de Disponibilidad Presupuestaria Final'),
('FINALIZADO', 'Proceso Finalizado Exitosamente', 'SISTEMA', NULL),
('RECHAZADO', 'Proceso Rechazado / Cerrado', 'SISTEMA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes`
--

CREATE TABLE `expedientes` (
  `id` int NOT NULL,
  `codigo_interno` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `titulo_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Sin Título',
  `folio_opi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_creador_id` int NOT NULL,
  `unidad_origen_id` int NOT NULL,
  `centro_costo_id` int NOT NULL,
  `tipo_compra_id` int NOT NULL,
  `prioridad_id` int NOT NULL,
  `rango_utm_id` int DEFAULT NULL,
  `estado_actual` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'BORRADOR',
  `proveedor_adjudicado_id` int DEFAULT NULL,
  `id_contrato_suministro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orden_compra_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `decreto_alcaldicio_numero` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conv_marco_oc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_licitacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monto_estimado` decimal(15,2) NOT NULL,
  `monto_definitivo` decimal(15,2) DEFAULT NULL,
  `motivo_compra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `observacion_cierre` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_visa_presupuesto` datetime DEFAULT NULL,
  `fecha_adjudicacion` datetime DEFAULT NULL,
  `fecha_aprobacion_opi` datetime DEFAULT NULL,
  `num_certificado_oficial` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_entidad_gobierno` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'ID PE-MUN-00335',
  `id_compra_agil` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_compras_proyecto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_compras_item` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes`
--

INSERT INTO `expedientes` (`id`, `codigo_interno`, `titulo_compra`, `folio_opi`, `usuario_creador_id`, `unidad_origen_id`, `centro_costo_id`, `tipo_compra_id`, `prioridad_id`, `rango_utm_id`, `estado_actual`, `proveedor_adjudicado_id`, `id_contrato_suministro`, `orden_compra_numero`, `decreto_alcaldicio_numero`, `conv_marco_oc`, `id_licitacion`, `monto_estimado`, `monto_definitivo`, `motivo_compra`, `observacion_cierre`, `created_at`, `fecha_visa_presupuesto`, `fecha_adjudicacion`, `fecha_aprobacion_opi`, `num_certificado_oficial`, `id_entidad_gobierno`, `id_compra_agil`, `plan_compras_proyecto`, `plan_compras_item`) VALUES
(1, 'REQ-2026-0001', 'PRUEBA DE OPI', NULL, 1, 1, 1, 6, 1, 4, 'EN_COTIZACION_ADQ', NULL, NULL, NULL, NULL, NULL, NULL, 1071000.00, NULL, 'OPI PRUEBA TEST 01', NULL, '2026-08-05 10:33:00', '2026-08-05 10:36:18', NULL, NULL, NULL, 'ID PE-MUN-00335', NULL, '5089-70', 8),
(4, 'REQ-2026-0002', 'Compra de sillas', 'OPI-2026-0001', 1, 1, 1, 6, 1, 3, 'ESPERANDO_CDP_FINANZAS_FINAL', 2, NULL, '4189-243-AG26', 'DA8080', '4189-243-AG26', NULL, 5712000.00, 3570000.00, 'sillas de oficina', NULL, '2026-08-12 14:58:47', '2026-08-12 21:55:26', '2026-08-12 21:51:50', '2026-08-12 15:16:30', NULL, 'ID PE-MUN-00335', NULL, '555-44 26', 3),
(5, 'REQ-2026-0003', 'Subida de archivos', NULL, 1, 1, 1, 6, 1, 3, 'EN_REVISION_JEFATURA', NULL, NULL, NULL, NULL, NULL, NULL, 4760000.00, NULL, 'aslkdaklsdlknlasd', NULL, '2026-08-12 21:18:59', NULL, NULL, NULL, NULL, 'ID PE-MUN-00335', NULL, '5054-66-56', 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes_criterios`
--

CREATE TABLE `expedientes_criterios` (
  `id` int NOT NULL,
  `expediente_id` int NOT NULL,
  `numero_criterio` int NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes_documentos`
--

CREATE TABLE `expedientes_documentos` (
  `id` int NOT NULL,
  `expediente_id` int NOT NULL,
  `subido_por_id` int NOT NULL,
  `tipo_doc` enum('TDR_ESPECIFICACIONES','COTIZACION_RESPALDO','CUADRO_COMPARATIVO','OPI_FIRMADA_PDF','OPI_ANULADA','FICHA_PROVEEDOR','CDP_BORRADOR','SITUACION_PRESUPUESTARIA','DECRETO_ALCALDICIO','OTRO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_original` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_subida` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes_documentos`
--

INSERT INTO `expedientes_documentos` (`id`, `expediente_id`, `subido_por_id`, `tipo_doc`, `ruta_archivo`, `nombre_original`, `fecha_subida`) VALUES
(1, 1, 1, 'OTRO', 'uploads/2026/exp_1/adj_1785940380_0.pdf', 'Patente_Comercial_Electronica_-_2-10672.pdf', '2026-08-05 10:33:00'),
(2, 4, 1, 'OTRO', 'uploads/2026/exp_4/adj_1786561127_0.png', 'unnamed (1) (1).png', '2026-08-12 14:58:47'),
(3, 4, 1, 'COTIZACION_RESPALDO', 'uploads/2026/exp_4/COTIZACIONES_1786561857.pdf', 'SAESA-comprobante-pago-35606820-BC397B2E-EA7E-F111-B338-0022482BEE46.pdf', '2026-08-12 15:10:57'),
(4, 4, 1, 'FICHA_PROVEEDOR', 'uploads/2026/exp_4/ficha_prov_1786561975.pdf', 'TRHK -28.pdf', '2026-08-12 15:12:55'),
(5, 4, 1, 'OTRO', 'uploads/2026/exp_4/acta_adj_1786561975.pdf', 'Estado de cuenta_1780581900371.pdf', '2026-08-12 15:12:55'),
(6, 4, 1, 'CDP_BORRADOR', 'uploads/2026/exp_4/CDP_BORRADOR_1786562036.pdf', 'CDP_BORRADOR_1785812715.pdf', '2026-08-12 15:13:56'),
(7, 4, 1, 'SITUACION_PRESUPUESTARIA', 'uploads/2026/exp_4/SITUACION_GASTOS_1786562036.pdf', 'OPI N° OPI-2026-0001 - Municipalidad de Lebu.pdf', '2026-08-12 15:13:56'),
(8, 4, 1, 'CDP_BORRADOR', 'uploads/2026/exp_4/CDP_FIRMADO_FINANZAS_1786562077.pdf', 'CDP_BORRADOR_1786562036.pdf', '2026-08-12 15:14:37'),
(9, 4, 1, 'OPI_FIRMADA_PDF', 'uploads/2026/exp_4/OPI_FIRMADA_1786562808.pdf', 'OPI N° OPI-2026-0001 - Municipalidad de Lebu.pdf', '2026-08-12 15:26:48'),
(10, 4, 1, 'OTRO', 'uploads/2026/exp_4/ORDEN_COMPRA_1786564632.pdf', 'TRHK -28.pdf', '2026-08-12 15:57:12'),
(11, 4, 1, 'DECRETO_ALCALDICIO', 'uploads/2026/exp_4/DECRETO_ALCALDICIO_1786564632.pdf', 'DECRETO.pdf', '2026-08-12 15:57:12'),
(12, 5, 1, 'OTRO', 'uploads/2026/exp_5/adj_1786583939_0.zip', 'habilitacinformulariodigitalpostulacinfiestadelan.zip', '2026-08-12 21:18:59'),
(13, 4, 1, 'FICHA_PROVEEDOR', 'uploads/2026/exp_4/ficha_prov_1786585910.pdf', 'Ficha_PDF_20260812_21_51.pdf', '2026-08-12 21:51:50'),
(14, 4, 1, 'OTRO', 'uploads/2026/exp_4/acta_adj_1786585910.pdf', 'TRHK -28.pdf', '2026-08-12 21:51:50'),
(15, 4, 1, 'CDP_BORRADOR', 'uploads/2026/exp_4/CDP_BORRADOR_1786586126.pdf', 'DECRETO.pdf', '2026-08-12 21:55:26'),
(16, 4, 1, 'SITUACION_PRESUPUESTARIA', 'uploads/2026/exp_4/SITUACION_GASTOS_1786586126.pdf', 'Ficha_PDF_20260812_21_51.pdf', '2026-08-12 21:55:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes_firmas`
--

CREATE TABLE `expedientes_firmas` (
  `id` int NOT NULL,
  `expediente_id` int NOT NULL,
  `autoridad_id` int NOT NULL,
  `cargo_firmante` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_firma` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes_firmas`
--

INSERT INTO `expedientes_firmas` (`id`, `expediente_id`, `autoridad_id`, `cargo_firmante`, `fecha_firma`) VALUES
(1, 4, 1, 'ADMINISTRADOR MUNICIPAL', '2026-08-12 15:26:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes_historial`
--

CREATE TABLE `expedientes_historial` (
  `id` int NOT NULL,
  `expediente_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `accion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado_anterior` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_nuevo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fecha_accion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes_historial`
--

INSERT INTO `expedientes_historial` (`id`, `expediente_id`, `usuario_id`, `accion`, `estado_anterior`, `estado_nuevo`, `comentario`, `fecha_accion`) VALUES
(1, 1, 1, 'CREAR', 'BORRADOR', 'EN_REVISION_JEFATURA', 'Solicitud ingresada al sistema. Proyecto: 5089-70, Ítem: 8', '2026-08-05 10:33:00'),
(2, 1, 1, 'APROBAR', 'EN_REVISION_JEFATURA', 'EN_VALIDACION_PRESUPUESTARIA', 'Aprobar', '2026-08-05 10:33:19'),
(3, 1, 1, 'APROBAR', 'EN_VALIDACION_PRESUPUESTARIA', 'EN_AUTORIZACION_COTIZACION', 'Certificado de Disponibilidad Presupuestaria (CDP) generado. Visación presupuestaria aprobada.', '2026-08-05 10:36:19'),
(4, 1, 1, 'APROBAR', 'EN_AUTORIZACION_COTIZACION', 'EN_COTIZACION_ADQ', 'Cotización autorizada por Administración Municipal.', '2026-08-05 10:36:42'),
(5, 4, 1, 'CREAR', 'BORRADOR', 'EN_REVISION_JEFATURA', 'Solicitud ingresada al sistema. Proyecto: 555-44 26, Ítem: 3', '2026-08-12 14:58:47'),
(6, 4, 1, 'APROBAR', 'EN_REVISION_JEFATURA', 'EN_VALIDACION_PRESUPUESTARIA', 'Aprobar', '2026-08-12 15:06:21'),
(7, 4, 1, 'APROBAR', 'EN_VALIDACION_PRESUPUESTARIA', 'EN_AUTORIZACION_COTIZACION', 'Certificado de Disponibilidad Presupuestaria (CDP) generado. Visación presupuestaria aprobada.', '2026-08-12 15:08:03'),
(8, 4, 1, 'APROBAR', 'EN_AUTORIZACION_COTIZACION', 'EN_COTIZACION_ADQ', 'Cotización autorizada por Administración Municipal.', '2026-08-12 15:09:31'),
(9, 4, 1, 'APROBAR', 'EN_COTIZACION_ADQ', 'EN_EVALUACION_OFERTAS', 'Gestión de Adquisiciones completada. Archivos subidos.', '2026-08-12 15:10:57'),
(10, 4, 1, 'APROBAR', 'EN_EVALUACION_OFERTAS', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'Proveedor seleccionado: churra (69.160.300-8). Acta de Adjudicación adjunta. Montos actualizados.', '2026-08-12 15:12:55'),
(11, 4, 1, 'APROBAR', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS_FINAL', 'Visación final por gasto real aprobada. Borrador de CDP y Situación de Gastos cargados. Expediente enviado a Finanzas para firma.', '2026-08-12 15:13:56'),
(12, 4, 1, 'APROBAR', 'ESPERANDO_CDP_FINANZAS_FINAL', 'EN_APROBACION_ADMINISTRADOR', 'Certificado de Disponibilidad Presupuestaria (CDP) cargado exitosamente desde SMC por Finanzas.', '2026-08-12 15:14:37'),
(13, 4, 1, 'APROBAR', 'EN_APROBACION_ADMINISTRADOR', 'EN_EMISION_OC', 'OPI Oficial Generada y Firmada (Folio: OPI-2026-0001).', '2026-08-12 15:26:48'),
(14, 4, 1, 'APROBAR', 'EN_EMISION_OC', 'ESPERANDO_ACEPTACION_OC', 'Gestión de Adquisiciones completada. Archivos subidos.', '2026-08-12 15:57:12'),
(15, 4, 1, 'RECHAZO_PROVEEDOR', 'ESPERANDO_ACEPTACION_OC', 'EN_EVALUACION_OFERTAS', 'Proveedor rechazó OC. Motivo: PROVEEDOR NO TIENE LOS PRODUCTOS. Se devuelve a evaluación para readjudicar (OPI Vigente).', '2026-08-12 15:58:04'),
(16, 5, 1, 'CREAR', 'BORRADOR', 'EN_REVISION_JEFATURA', 'Solicitud ingresada al sistema. Proyecto: 5054-66-56, Ítem: 8', '2026-08-12 21:18:59'),
(17, 4, 1, 'APROBAR', 'EN_EVALUACION_OFERTAS', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'Proveedor seleccionado: Empresas del Sur (11.239.204-1). Acta de Adjudicación adjunta. Montos actualizados.', '2026-08-12 21:51:50'),
(18, 4, 1, 'APROBAR', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS_FINAL', 'Visación final por gasto real aprobada. Borrador de CDP y Situación de Gastos cargados. Expediente enviado a Finanzas para firma.', '2026-08-12 21:55:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes_items`
--

CREATE TABLE `expedientes_items` (
  `id` int NOT NULL,
  `expediente_id` int NOT NULL,
  `presupuesto_asignado_id` int NOT NULL,
  `id_producto_cm` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  `total_linea` decimal(15,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`)) VIRTUAL,
  `monto_total_presupuesto` decimal(15,2) DEFAULT NULL,
  `monto_comprometido_fecha` decimal(15,2) DEFAULT NULL,
  `monto_operacion` decimal(15,2) DEFAULT NULL,
  `saldo_final_resultante` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes_items`
--

INSERT INTO `expedientes_items` (`id`, `expediente_id`, `presupuesto_asignado_id`, `id_producto_cm`, `descripcion`, `unidad_medida`, `cantidad`, `precio_unitario`, `monto_total_presupuesto`, `monto_comprometido_fecha`, `monto_operacion`, `saldo_final_resultante`) VALUES
(1, 1, 3, NULL, 'Computadores para vivienda, dom y secplan', 'UNIDAD', 1.00, 0.00, NULL, NULL, NULL, NULL),
(4, 4, 3, NULL, 'sillas', 'UNIDAD', 20.00, 178500.00, NULL, NULL, NULL, NULL),
(5, 5, 3, NULL, 'Computadores', 'UNIDAD', 1.00, 0.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `flujos_definicion`
--

CREATE TABLE `flujos_definicion` (
  `id` int NOT NULL,
  `tipo_compra_id` int NOT NULL,
  `estado_actual` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `requiere_archivo` tinyint(1) DEFAULT '0',
  `accion_codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'APROBAR',
  `accion_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aprobar y Continuar',
  `estado_destino` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `requiere_comentario` tinyint(1) DEFAULT '0',
  `monto_min_utm` decimal(10,2) DEFAULT NULL,
  `monto_max_utm` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `flujos_definicion`
--

INSERT INTO `flujos_definicion` (`id`, `tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`, `monto_min_utm`, `monto_max_utm`) VALUES
(1, 6, 'BORRADOR', 0, 'APROBAR', 'Aprobar', 'EN_REVISION_JEFATURA', 0, NULL, NULL),
(2, 6, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA', 0, NULL, NULL),
(3, 6, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(4, 6, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(6, 6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(7, 6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(8, 6, 'EN_COTIZACION_ADQ', 0, 'APROBAR', 'Aprobar', 'EN_EVALUACION_OFERTAS', 0, NULL, NULL),
(9, 6, 'EN_COTIZACION_ADQ', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(10, 6, 'EN_COTIZACION_ADQ', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(11, 6, 'EN_EVALUACION_OFERTAS', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, NULL, NULL),
(12, 6, 'EN_EVALUACION_OFERTAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(13, 6, 'EN_EVALUACION_OFERTAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(14, 6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'APROBAR', 'Aprobar y Enviar a Finanzas para Firma de CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0, NULL, NULL),
(15, 6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_EVALUACION_OFERTAS', 0, NULL, NULL),
(16, 6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(17, 6, 'EN_APROBACION_ADMINISTRADOR', 0, 'APROBAR', 'Aprobar', 'EN_EMISION_OC', 0, NULL, NULL),
(18, 6, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(19, 6, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(20, 6, 'EN_EMISION_OC', 0, 'APROBAR', 'Aprobar', 'ESPERANDO_ACEPTACION_OC', 0, NULL, NULL),
(21, 6, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(22, 6, 'EN_EMISION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(23, 6, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'Aprobar', 'FINALIZADO', 0, NULL, NULL),
(24, 6, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(25, 6, 'ESPERANDO_ACEPTACION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(26, 2, 'BORRADOR', 0, 'APROBAR', 'Aprobar', 'EN_REVISION_JEFATURA', 0, NULL, NULL),
(27, 2, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA', 0, NULL, NULL),
(28, 2, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(29, 2, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(30, 2, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Aprobar', 'EN_GESTION_ADQUISICIONES', 0, NULL, NULL),
(31, 2, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(32, 2, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(33, 2, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Aprobar', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(34, 2, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(35, 2, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(36, 2, 'EN_APROBACION_ADMINISTRADOR', 0, 'APROBAR', 'Aprobar', 'EN_EMISION_OC', 0, NULL, NULL),
(37, 2, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(38, 2, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(39, 2, 'EN_EMISION_OC', 0, 'APROBAR', 'Aprobar', 'ESPERANDO_ACEPTACION_OC', 0, NULL, NULL),
(40, 2, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(41, 2, 'EN_EMISION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(42, 2, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'Aprobar', 'FINALIZADO', 0, NULL, NULL),
(43, 2, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(44, 2, 'ESPERANDO_ACEPTACION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(45, 4, 'BORRADOR', 0, 'APROBAR', 'Aprobar', 'EN_REVISION_JEFATURA', 0, NULL, NULL),
(46, 4, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA', 0, NULL, NULL),
(47, 4, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(48, 4, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(49, 4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Aprobar', 'EN_GESTION_ADQUISICIONES', 0, NULL, NULL),
(50, 4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(51, 4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(52, 4, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Aprobar', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(53, 4, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(54, 4, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(55, 4, 'EN_APROBACION_ADMINISTRADOR', 0, 'APROBAR', 'Aprobar', 'EN_EMISION_OC', 0, NULL, NULL),
(56, 4, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(57, 4, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(58, 4, 'EN_EMISION_OC', 0, 'APROBAR', 'Aprobar', 'ESPERANDO_ACEPTACION_OC', 0, NULL, NULL),
(59, 4, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(60, 4, 'EN_EMISION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(61, 4, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'Aprobar', 'FINALIZADO', 0, NULL, NULL),
(62, 4, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(63, 4, 'ESPERANDO_ACEPTACION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(64, 3, 'BORRADOR', 0, 'APROBAR', 'Aprobar', 'EN_REVISION_JEFATURA', 0, NULL, NULL),
(65, 3, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA', 0, NULL, NULL),
(66, 3, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(67, 3, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(68, 3, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Aprobar', 'EN_COTIZACION_ADQ', 0, NULL, NULL),
(69, 3, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(70, 3, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(71, 3, 'EN_COTIZACION_ADQ', 0, 'APROBAR', 'Aprobar', 'EN_EVALUACION_OFERTAS', 0, NULL, NULL),
(72, 3, 'EN_COTIZACION_ADQ', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(73, 3, 'EN_COTIZACION_ADQ', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(74, 3, 'EN_EVALUACION_OFERTAS', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, NULL, NULL),
(75, 3, 'EN_EVALUACION_OFERTAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(76, 3, 'EN_EVALUACION_OFERTAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(77, 3, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'APROBAR', 'Aprobar y Enviar a Finanzas para Firma de CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0, NULL, NULL),
(78, 3, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(79, 3, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(80, 3, 'EN_APROBACION_ADMINISTRADOR', 0, 'APROBAR', 'Aprobar', 'EN_EMISION_OC', 0, NULL, NULL),
(81, 3, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(82, 3, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(83, 3, 'EN_EMISION_OC', 0, 'APROBAR', 'Aprobar', 'ESPERANDO_ACEPTACION_OC', 0, NULL, NULL),
(84, 3, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(85, 3, 'EN_EMISION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(86, 3, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'Aprobar', 'FINALIZADO', 0, NULL, NULL),
(87, 3, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(88, 3, 'ESPERANDO_ACEPTACION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(99, 2, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'SOLICITAR_CDP', 'Solicitar CDP a Finanzas', 'ESPERANDO_CDP_FINANZAS', 0, NULL, NULL),
(101, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, NULL, NULL),
(102, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(103, 4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'SOLICITAR_CDP', 'Solicitar CDP a Finanzas', 'ESPERANDO_CDP_FINANZAS', 0, NULL, NULL),
(105, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, NULL, NULL),
(106, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(109, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, NULL, NULL),
(110, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(112, 6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Visar y Reservar Fondos', 'EN_AUTORIZACION_COTIZACION', 0, NULL, NULL),
(113, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'APROBAR', 'Autorizar Cotización', 'EN_COTIZACION_ADQ', 0, NULL, NULL),
(114, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(115, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(116, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(117, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(118, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(126, 5, 'BORRADOR', 0, 'APROBAR', 'Aprobar', 'EN_REVISION_JEFATURA', 0, NULL, NULL),
(127, 5, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar', 'EN_VALIDACION_PRESUPUESTARIA', 0, NULL, NULL),
(128, 5, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(129, 5, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(130, 5, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Aprobar', 'EN_GESTION_ADQUISICIONES', 0, NULL, NULL),
(131, 5, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(132, 5, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(133, 5, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Aprobar', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),
(134, 5, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(135, 5, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(136, 5, 'EN_APROBACION_ADMINISTRADOR', 0, 'APROBAR', 'Aprobar', 'EN_EMISION_OC', 0, NULL, NULL),
(137, 5, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(138, 5, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(139, 5, 'EN_EMISION_OC', 0, 'APROBAR', 'Aprobar', 'ESPERANDO_ACEPTACION_OC', 0, NULL, NULL),
(140, 5, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(141, 5, 'EN_EMISION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(142, 5, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'Aprobar', 'FINALIZADO', 0, NULL, NULL),
(143, 5, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(144, 5, 'ESPERANDO_ACEPTACION_OC', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(145, 5, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'SOLICITAR_CDP', 'Solicitar CDP a Finanzas', 'ESPERANDO_CDP_FINANZAS', 0, NULL, NULL),
(146, 5, 'ESPERANDO_CDP_FINANZAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, NULL, NULL),
(147, 5, 'ESPERANDO_CDP_FINANZAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(148, 5, 'ESPERANDO_CDP_FINANZAS', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuestos_asignados`
--

CREATE TABLE `presupuestos_asignados` (
  `id` int NOT NULL,
  `centro_costo_id` int NOT NULL,
  `cuenta_maestra_id` int NOT NULL,
  `area_gestion_id` int DEFAULT NULL,
  `monto_inicial_asignado` decimal(15,2) DEFAULT '0.00',
  `monto_comprometido` decimal(15,2) DEFAULT '0.00',
  `monto_ejecutado` decimal(15,2) DEFAULT '0.00',
  `saldo_disponible` decimal(15,2) GENERATED ALWAYS AS (((`monto_inicial_asignado` - `monto_comprometido`) - `monto_ejecutado`)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `presupuestos_asignados`
--

INSERT INTO `presupuestos_asignados` (`id`, `centro_costo_id`, `cuenta_maestra_id`, `area_gestion_id`, `monto_inicial_asignado`, `monto_comprometido`, `monto_ejecutado`) VALUES
(2, 1, 1, 1, NULL, 0.00, 0.00),
(3, 1, 3, 1, NULL, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prioridades`
--

CREATE TABLE `prioridades` (
  `id` int NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clase_css` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'bg-gray-100 text-gray-800',
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prioridades`
--

INSERT INTO `prioridades` (`id`, `codigo`, `nombre`, `clase_css`, `activo`) VALUES
(1, 'NORMAL', 'Normal', 'bg-primary-subtle text-primary-emphasis', 1),
(2, 'URGENTE', 'Urgente', 'bg-warning-subtle text-warning-emphasis fw-bold', 1),
(3, 'EMERGENCIA', 'Emergencia', 'bg-danger-subtle text-danger-emphasis fw-bold', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int NOT NULL,
  `rut` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `giro` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `rut`, `razon_social`, `giro`, `direccion`, `telefono`, `email`) VALUES
(1, '69.160.300-8', 'CHURRA ENTERPRISES', NULL, 'Lebu', NULL, NULL),
(2, '11.239.204-1', 'Empresas del Sur', NULL, 'Lebu', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rangos_utm`
--

CREATE TABLE `rangos_utm` (
  `id` int NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `min_utm` decimal(10,2) DEFAULT '0.00',
  `max_utm` decimal(10,2) DEFAULT NULL,
  `regla_cotizaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rangos_utm`
--

INSERT INTO `rangos_utm` (`id`, `nombre`, `min_utm`, `max_utm`, `regla_cotizaciones`, `activo`) VALUES
(1, 'Menor', 0.00, 3.00, 'Sin mínimos', 1),
(2, 'Bajo', 3.01, 10.00, 'Mínimo 1 Cotización', 1),
(3, 'Intermedio', 10.01, 100.00, 'Mínimo 3 Cotizaciones', 1),
(4, 'Alto', 100.01, 1000.00, 'Licitación / Gran Compra', 1),
(5, 'Muy Alto', 1000.01, 2000.00, 'Mayores exigencias', 1),
(6, 'Estratégico', 2000.01, 5000.00, 'Aprobación Concejo posible', 1),
(7, 'Mayor', 5000.01, NULL, 'Máxima autoridad', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN_MUNICIPAL', 'Aprobador Final'),
(2, 'JEFE_UNIDAD', 'Visador Técnico'),
(3, 'PRESUPUESTO', 'Visador Financiero'),
(4, 'ADQUISICIONES', 'Operador de Compras'),
(5, 'USUARIO_REQ', 'Solicitante'),
(6, 'SYSADMIN', 'Superadministrador de Sistemas'),
(7, 'FINANZAS', 'Visador de Finanzas - Emisión de CDP');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subrogancias`
--

CREATE TABLE `subrogancias` (
  `id` int NOT NULL,
  `usuario_titular_id` int NOT NULL,
  `usuario_subrogante_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_compra`
--

CREATE TABLE `tipos_compra` (
  `id` int NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `limite_utm` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `requiere_cotizacion` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_compra`
--

INSERT INTO `tipos_compra` (`id`, `codigo`, `nombre`, `limite_utm`, `activo`, `requiere_cotizacion`) VALUES
(1, 'SISTEMA_DIRECTO', 'Orden de Compra Por Sistema Directo', NULL, 0, 0),
(2, 'TRATO_DIRECTO', 'Trato Directo', NULL, 1, 0),
(3, 'LICITACION', 'Licitación Pública', NULL, 1, 1),
(4, 'CONVENIO_MARCO', 'Convenio Marco', NULL, 1, 0),
(5, 'CONTRATO_SUMINISTRO', 'Contrato de Suministros', NULL, 1, 0),
(6, 'COMPRA_AGIL', 'Compra Ágil', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id` int NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `padre_id` int DEFAULT NULL,
  `centro_costo_id` int DEFAULT NULL,
  `jefe_actual_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id`, `nombre`, `padre_id`, `centro_costo_id`, `jefe_actual_id`) VALUES
(1, 'Departamento de informática', 5, 1, NULL),
(2, 'DIDECO', NULL, NULL, NULL),
(3, 'PRESUPUESTO', NULL, NULL, NULL),
(4, 'Adquisiciones', 10, NULL, NULL),
(5, 'Administración municipal', NULL, NULL, NULL),
(6, 'OLN', 2, NULL, NULL),
(7, 'Alcadía', NULL, NULL, NULL),
(8, 'Tránsito', NULL, NULL, NULL),
(9, 'Juzgado policia local', NULL, NULL, NULL),
(10, 'Finanzas', NULL, NULL, NULL),
(11, 'Transparencia', NULL, NULL, NULL),
(12, 'Personal', NULL, NULL, NULL),
(13, 'RR.HH', NULL, NULL, NULL),
(14, 'Jurídico', NULL, NULL, NULL),
(15, 'OIRS', NULL, NULL, NULL),
(16, 'Of. Partes', 18, NULL, NULL),
(17, 'Gabinete', NULL, NULL, NULL),
(18, 'Secretaría Municipal', NULL, NULL, NULL),
(19, 'UDEL', NULL, NULL, NULL),
(20, 'SS.GG', NULL, NULL, NULL),
(21, 'SECPLAN', NULL, NULL, NULL),
(22, 'Obras', NULL, NULL, NULL),
(23, 'Autogerenación Isla Mocha', 5, NULL, NULL),
(24, 'DAS-CESFAM', NULL, NULL, NULL),
(25, 'Control', NULL, NULL, NULL),
(26, 'Dirección de obras', NULL, NULL, NULL),
(27, 'Vivienda', NULL, NULL, NULL),
(28, 'Permisos de circulación', NULL, NULL, NULL),
(29, 'Oficina Local de la Niñez (ex OPD)', NULL, NULL, NULL),
(30, 'PRODESAL', NULL, NULL, NULL),
(31, 'OMIL', NULL, NULL, NULL),
(32, 'Seguridad Pública', NULL, NULL, NULL),
(33, 'PROEMPLEO', NULL, NULL, NULL),
(34, 'Medio Ambiente', 21, NULL, NULL),
(35, 'Biblioteca municipal', 2, NULL, NULL),
(36, 'Centro de la mujer', 2, NULL, NULL),
(38, 'Movilización', 20, NULL, NULL),
(39, 'Bodega', 10, NULL, NULL),
(40, 'Subsidios', NULL, NULL, NULL),
(41, 'Adulto mayor', 2, NULL, NULL),
(42, 'RR.PP', NULL, NULL, NULL),
(43, 'Fomento UDEL', 19, NULL, NULL),
(44, 'Oficina de Concejales', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `unidad_id` int NOT NULL,
  `rol_id` int NOT NULL,
  `rut` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_completo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `es_jefe_unidad` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `cargo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_verificacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT '0',
  `estado_aprobacion` enum('APROBADO','PENDIENTE_VERIFICACION','PENDIENTE_APROBACION','RECHAZADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'APROBADO',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `unidad_id`, `rol_id`, `rut`, `nombre_completo`, `email`, `password_hash`, `es_jefe_unidad`, `activo`, `cargo`, `token_verificacion`, `email_verificado`, `estado_aprobacion`, `fecha_registro`) VALUES
(1, 1, 6, '11.111.111-1', 'Departamento de Informática', 'informatica@lebu.cl', '$2y$10$4/wrHWwJnBaCk/q5JGWrK./XJEVgXzQ.3S3p/3iZ1sxhoIXeYOSfq', 0, 1, 'Departamento de informática', NULL, 1, 'APROBADO', '2026-08-05 14:31:30'),
(2, 5, 1, '16.981.872-K', 'Oscar Muñoz Arriagada', 'administrador@lebu.cl', '$2y$10$OHlyMewXSDy2C85PosohWurCRh3fvzGPy.XQ5sYUyIoiQl0IB6yXC', 1, 1, 'Administrador Municipal', NULL, 1, 'APROBADO', '2026-08-05 14:31:30'),
(3, 5, 5, '55.555.555-5', 'Usuario prueba ClaveUnica', 'claveunica@lebu.cl', '$2y$10$eT/tzzWBBhGBN701KFAgfu4woRuPmRBk8UtbDlBgAQmOpjadYfCGC', 0, 1, 'Pruebas ClaveUnica', NULL, 1, 'APROBADO', '2026-08-05 17:01:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas_gestion`
--
ALTER TABLE `areas_gestion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `centros_costo`
--
ALTER TABLE `centros_costo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuraciones_sistema`
--
ALTER TABLE `configuraciones_sistema`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `cuentas_maestras`
--
ALTER TABLE `cuentas_maestras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `cuentas_presupuestarias`
--
ALTER TABLE `cuentas_presupuestarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `centro_costo_id` (`centro_costo_id`),
  ADD KEY `fk_cuenta_ag` (`area_gestion_id`);

--
-- Indices de la tabla `estados_tramite`
--
ALTER TABLE `estados_tramite`
  ADD PRIMARY KEY (`codigo`);

--
-- Indices de la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_interno` (`codigo_interno`),
  ADD UNIQUE KEY `folio_opi` (`folio_opi`),
  ADD KEY `fk_exp_usr` (`usuario_creador_id`),
  ADD KEY `fk_exp_uni` (`unidad_origen_id`),
  ADD KEY `fk_exp_cc` (`centro_costo_id`),
  ADD KEY `fk_exp_tipo` (`tipo_compra_id`),
  ADD KEY `fk_exp_pri` (`prioridad_id`),
  ADD KEY `fk_exp_est` (`estado_actual`),
  ADD KEY `fk_exp_prov` (`proveedor_adjudicado_id`),
  ADD KEY `fk_exp_rango` (`rango_utm_id`);

--
-- Indices de la tabla `expedientes_criterios`
--
ALTER TABLE `expedientes_criterios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_criterio_exp` (`expediente_id`);

--
-- Indices de la tabla `expedientes_documentos`
--
ALTER TABLE `expedientes_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_doc_exp` (`expediente_id`);

--
-- Indices de la tabla `expedientes_firmas`
--
ALTER TABLE `expedientes_firmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fir_exp` (`expediente_id`);

--
-- Indices de la tabla `expedientes_historial`
--
ALTER TABLE `expedientes_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hist_exp` (`expediente_id`);

--
-- Indices de la tabla `expedientes_items`
--
ALTER TABLE `expedientes_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_itm_exp` (`expediente_id`),
  ADD KEY `fk_itm_pre` (`presupuesto_asignado_id`);

--
-- Indices de la tabla `flujos_definicion`
--
ALTER TABLE `flujos_definicion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_flu_tipo` (`tipo_compra_id`),
  ADD KEY `fk_flu_act` (`estado_actual`),
  ADD KEY `fk_flu_dest_new` (`estado_destino`);

--
-- Indices de la tabla `presupuestos_asignados`
--
ALTER TABLE `presupuestos_asignados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pre_cc` (`centro_costo_id`),
  ADD KEY `fk_pre_cta` (`cuenta_maestra_id`),
  ADD KEY `fk_pre_ag` (`area_gestion_id`);

--
-- Indices de la tabla `prioridades`
--
ALTER TABLE `prioridades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`);

--
-- Indices de la tabla `rangos_utm`
--
ALTER TABLE `rangos_utm`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `subrogancias`
--
ALTER TABLE `subrogancias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sub_tit` (`usuario_titular_id`),
  ADD KEY `fk_sub_sup` (`usuario_subrogante_id`);

--
-- Indices de la tabla `tipos_compra`
--
ALTER TABLE `tipos_compra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uni_padre` (`padre_id`),
  ADD KEY `fk_uni_cc` (`centro_costo_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usr_uni` (`unidad_id`),
  ADD KEY `fk_usr_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas_gestion`
--
ALTER TABLE `areas_gestion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `centros_costo`
--
ALTER TABLE `centros_costo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cuentas_maestras`
--
ALTER TABLE `cuentas_maestras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cuentas_presupuestarias`
--
ALTER TABLE `cuentas_presupuestarias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `expedientes`
--
ALTER TABLE `expedientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `expedientes_criterios`
--
ALTER TABLE `expedientes_criterios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `expedientes_documentos`
--
ALTER TABLE `expedientes_documentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `expedientes_firmas`
--
ALTER TABLE `expedientes_firmas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `expedientes_historial`
--
ALTER TABLE `expedientes_historial`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `expedientes_items`
--
ALTER TABLE `expedientes_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `flujos_definicion`
--
ALTER TABLE `flujos_definicion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT de la tabla `presupuestos_asignados`
--
ALTER TABLE `presupuestos_asignados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `prioridades`
--
ALTER TABLE `prioridades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `rangos_utm`
--
ALTER TABLE `rangos_utm`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `subrogancias`
--
ALTER TABLE `subrogancias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_compra`
--
ALTER TABLE `tipos_compra`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cuentas_presupuestarias`
--
ALTER TABLE `cuentas_presupuestarias`
  ADD CONSTRAINT `cuentas_presupuestarias_ibfk_1` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cuenta_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`);

--
-- Filtros para la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD CONSTRAINT `fk_exp_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_exp_est` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_exp_pri` FOREIGN KEY (`prioridad_id`) REFERENCES `prioridades` (`id`),
  ADD CONSTRAINT `fk_exp_prov` FOREIGN KEY (`proveedor_adjudicado_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `fk_exp_rango` FOREIGN KEY (`rango_utm_id`) REFERENCES `rangos_utm` (`id`),
  ADD CONSTRAINT `fk_exp_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`),
  ADD CONSTRAINT `fk_exp_uni` FOREIGN KEY (`unidad_origen_id`) REFERENCES `unidades` (`id`),
  ADD CONSTRAINT `fk_exp_usr` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `expedientes_criterios`
--
ALTER TABLE `expedientes_criterios`
  ADD CONSTRAINT `fk_criterio_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `expedientes_documentos`
--
ALTER TABLE `expedientes_documentos`
  ADD CONSTRAINT `fk_doc_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `expedientes_firmas`
--
ALTER TABLE `expedientes_firmas`
  ADD CONSTRAINT `fk_fir_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `expedientes_historial`
--
ALTER TABLE `expedientes_historial`
  ADD CONSTRAINT `fk_hist_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `expedientes_items`
--
ALTER TABLE `expedientes_items`
  ADD CONSTRAINT `fk_itm_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_itm_pre` FOREIGN KEY (`presupuesto_asignado_id`) REFERENCES `presupuestos_asignados` (`id`);

--
-- Filtros para la tabla `flujos_definicion`
--
ALTER TABLE `flujos_definicion`
  ADD CONSTRAINT `fk_flu_act` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_flu_dest_new` FOREIGN KEY (`estado_destino`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_flu_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`);

--
-- Filtros para la tabla `presupuestos_asignados`
--
ALTER TABLE `presupuestos_asignados`
  ADD CONSTRAINT `fk_pre_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`),
  ADD CONSTRAINT `fk_pre_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pre_cta` FOREIGN KEY (`cuenta_maestra_id`) REFERENCES `cuentas_maestras` (`id`);

--
-- Filtros para la tabla `subrogancias`
--
ALTER TABLE `subrogancias`
  ADD CONSTRAINT `fk_sub_sup` FOREIGN KEY (`usuario_subrogante_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_sub_tit` FOREIGN KEY (`usuario_titular_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `fk_uni_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_uni_padre` FOREIGN KEY (`padre_id`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usr_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_usr_uni` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
