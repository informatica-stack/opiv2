-- ============================================================================
-- BASE DE DATOS DE PRODUCCIÓN: `OPI` (Sistema de Órdenes de Pedido Interno)
-- Municipalidad de Lebu
-- Generado el: 2026-08-04
-- Versión: 2.0 (Limpia - Sin solicitudes de prueba)
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. Tabla `areas_gestion`
-- --------------------------------------------------------
CREATE TABLE `areas_gestion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `areas_gestion` (`id`, `codigo`, `nombre`, `activo`) VALUES
(1, 'AG 01', 'Gestión Interna', 1),
(2, 'AG 02', 'Servicios Comunitarios', 1),
(3, 'S/I', 'Sin Imputación', 1);

-- --------------------------------------------------------
-- 2. Tabla `centros_costo`
-- --------------------------------------------------------
CREATE TABLE `centros_costo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_cuenta` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `anio_fiscal` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `centros_costo` (`id`, `codigo_cuenta`, `nombre`, `anio_fiscal`, `activo`) VALUES
(1, '11', 'Depto. Informática', 2026, 1);

-- --------------------------------------------------------
-- 3. Tabla `configuraciones_sistema`
-- --------------------------------------------------------
CREATE TABLE `configuraciones_sistema` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `configuraciones_sistema` (`clave`, `valor`, `descripcion`) VALUES
('limite_peso_adjunto_mb', '10', 'Límite máximo de peso de archivos adjuntos en Megabytes (MB)'),
('modo_mantenimiento', '0', 'Indica si el sistema está en mantenimiento (1 = Sí, 0 = No)'),
('valor_utm', '66000', 'Valor de 1 UTM en pesos chilenos (CLP)');

-- --------------------------------------------------------
-- 4. Tabla `cuentas_maestras`
-- --------------------------------------------------------
CREATE TABLE `cuentas_maestras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `presupuesto_global_total` decimal(15,2) DEFAULT '0.00',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cuentas_maestras` (`id`, `codigo`, `nombre`, `presupuesto_global_total`, `activo`) VALUES
(1, '215-22-04-001', 'Materiales de Oficina', 0.00, 1),
(3, '215-29-06-001', 'Equipos computacionales y periféricos', 150000000.00, 1);

-- --------------------------------------------------------
-- 5. Tabla `cuentas_presupuestarias`
-- --------------------------------------------------------
CREATE TABLE `cuentas_presupuestarias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_costo_id` int NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `presupuesto_inicial` decimal(15,2) DEFAULT '0.00',
  `monto_comprometido` decimal(15,2) DEFAULT '0.00',
  `monto_ejecutado` decimal(15,2) DEFAULT '0.00',
  `saldo_disponible` decimal(15,2) GENERATED ALWAYS AS (((`presupuesto_inicial` - `monto_comprometido`) - `monto_ejecutado`)) VIRTUAL,
  `activo` tinyint(1) DEFAULT '1',
  `area_gestion_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `centro_costo_id` (`centro_costo_id`),
  KEY `fk_cuenta_ag` (`area_gestion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cuentas_presupuestarias` (`id`, `centro_costo_id`, `codigo`, `nombre`, `presupuesto_inicial`, `monto_comprometido`, `monto_ejecutado`, `activo`, `area_gestion_id`) VALUES
(1, 1, '215-22-04-001', 'Materiales de Oficina', 5000000.00, 0.00, 0.00, 1, 1),
(3, 1, '215-22-04-003', 'Equipamiento Computacional', 10000000.00, 0.00, 0.00, 1, 1),
(4, 1, '215-22-04-009', 'Insumos computacionales y repuestos', 7000000.00, 0.00, 0.00, 1, 1);

-- --------------------------------------------------------
-- 6. Tabla `estados_tramite`
-- --------------------------------------------------------
CREATE TABLE `estados_tramite` (
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `rol_responsable` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- 7. Tabla `expedientes` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_interno` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `titulo_compra` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Sin Título',
  `folio_opi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_creador_id` int NOT NULL,
  `unidad_origen_id` int NOT NULL,
  `centro_costo_id` int NOT NULL,
  `tipo_compra_id` int NOT NULL,
  `prioridad_id` int NOT NULL,
  `rango_utm_id` int DEFAULT NULL,
  `estado_actual` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'BORRADOR',
  `proveedor_adjudicado_id` int DEFAULT NULL,
  `id_contrato_suministro` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orden_compra_numero` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `decreto_alcaldicio_numero` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conv_marco_oc` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_licitacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monto_estimado` decimal(15,2) NOT NULL,
  `monto_definitivo` decimal(15,2) DEFAULT NULL,
  `motivo_compra` text COLLATE utf8mb4_general_ci NOT NULL,
  `observacion_cierre` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_visa_presupuesto` datetime DEFAULT NULL,
  `fecha_adjudicacion` datetime DEFAULT NULL,
  `fecha_aprobacion_opi` datetime DEFAULT NULL,
  `num_certificado_oficial` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_entidad_gobierno` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'ID PE-MUN-00335',
  `id_compra_agil` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_compras_proyecto` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_compras_item` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_interno` (`codigo_interno`),
  UNIQUE KEY `folio_opi` (`folio_opi`),
  KEY `fk_exp_usr` (`usuario_creador_id`),
  KEY `fk_exp_uni` (`unidad_origen_id`),
  KEY `fk_exp_cc` (`centro_costo_id`),
  KEY `fk_exp_tipo` (`tipo_compra_id`),
  KEY `fk_exp_pri` (`prioridad_id`),
  KEY `fk_exp_est` (`estado_actual`),
  KEY `fk_exp_prov` (`proveedor_adjudicado_id`),
  KEY `fk_exp_rango` (`rango_utm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 8. Tabla `expedientes_criterios` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes_criterios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `numero_criterio` int NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_criterio_exp` (`expediente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 9. Tabla `expedientes_documentos` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes_documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `subido_por_id` int NOT NULL,
  `tipo_doc` enum('TDR_ESPECIFICACIONES','COTIZACION_RESPALDO','CUADRO_COMPARATIVO','OPI_FIRMADA_PDF','OPI_ANULADA','FICHA_PROVEEDOR','CDP_BORRADOR','SITUACION_PRESUPUESTARIA','DECRETO_ALCALDICIO','OTRO') COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_original` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_subida` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_doc_exp` (`expediente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 10. Tabla `expedientes_firmas` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes_firmas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `autoridad_id` int NOT NULL,
  `cargo_firmante` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_firma` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fir_exp` (`expediente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 11. Tabla `expedientes_historial` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes_historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `accion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `estado_anterior` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_nuevo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_general_ci,
  `fecha_accion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_hist_exp` (`expediente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 12. Tabla `expedientes_items` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `expedientes_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `presupuesto_asignado_id` int NOT NULL,
  `id_producto_cm` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_medida` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  `total_linea` decimal(15,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`)) VIRTUAL,
  `monto_total_presupuesto` decimal(15,2) DEFAULT NULL,
  `monto_comprometido_fecha` decimal(15,2) DEFAULT NULL,
  `monto_operacion` decimal(15,2) DEFAULT NULL,
  `saldo_final_resultante` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_itm_exp` (`expediente_id`),
  KEY `fk_itm_pre` (`presupuesto_asignado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 13. Tabla `flujos_definicion`
-- --------------------------------------------------------
CREATE TABLE `flujos_definicion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_compra_id` int NOT NULL,
  `estado_actual` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `requiere_archivo` tinyint(1) DEFAULT '0',
  `accion_codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'APROBAR',
  `accion_label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aprobar y Continuar',
  `estado_destino` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `requiere_comentario` tinyint(1) DEFAULT '0',
  `monto_min_utm` decimal(10,2) DEFAULT NULL,
  `monto_max_utm` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_flu_tipo` (`tipo_compra_id`),
  KEY `fk_flu_act` (`estado_actual`),
  KEY `fk_flu_dest_new` (`estado_destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `flujos_definicion` (`id`, `tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`, `monto_min_utm`, `monto_max_utm`) VALUES
-- COMPRA ÁGIL (tipo_compra_id = 6)
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
(109, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, NULL, NULL),
(110, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(112, 6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Visar y Reservar Fondos', 'EN_AUTORIZACION_COTIZACION', 0, NULL, NULL),
(113, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'APROBAR', 'Autorizar Cotización', 'EN_COTIZACION_ADQ', 0, NULL, NULL),
(114, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 0, NULL, NULL),
(115, 6, 'EN_AUTORIZACION_COTIZACION', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 0, NULL, NULL),
(116, 6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),

-- TRATO DIRECTO (tipo_compra_id = 2)
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
(99, 2, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'SOLICITAR_CDP', 'Solicitar CDP a Finanzas', 'ESPERANDO_CDP_FINANZAS', 0, NULL, NULL),
(101, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, NULL, NULL),
(102, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(118, 2, 'ESPERANDO_CDP_FINANZAS', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),

-- CONVENIO MARCO (tipo_compra_id = 4)
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
(103, 4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'SOLICITAR_CDP', 'Solicitar CDP a Finanzas', 'ESPERANDO_CDP_FINANZAS', 0, NULL, NULL),
(105, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, NULL, NULL),
(106, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, NULL, NULL),
(117, 4, 'ESPERANDO_CDP_FINANZAS', 0, 'APROBAR', 'Firmar CDP y Enviar a Administrador', 'EN_APROBACION_ADMINISTRADOR', 0, NULL, NULL),

-- LICITACIÓN PÚBLICA (tipo_compra_id = 3)
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

-- CONTRATO DE SUMINISTROS (tipo_compra_id = 5)
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
-- 14. Tabla `presupuestos_asignados`
-- --------------------------------------------------------
CREATE TABLE `presupuestos_asignados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_costo_id` int NOT NULL,
  `cuenta_maestra_id` int NOT NULL,
  `area_gestion_id` int DEFAULT NULL,
  `monto_inicial_asignado` decimal(15,2) DEFAULT '0.00',
  `monto_comprometido` decimal(15,2) DEFAULT '0.00',
  `monto_ejecutado` decimal(15,2) DEFAULT '0.00',
  `saldo_disponible` decimal(15,2) GENERATED ALWAYS AS (((`monto_inicial_asignado` - `monto_comprometido`) - `monto_ejecutado`)) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `fk_pre_cc` (`centro_costo_id`),
  KEY `fk_pre_cta` (`cuenta_maestra_id`),
  KEY `fk_pre_ag` (`area_gestion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `presupuestos_asignados` (`id`, `centro_costo_id`, `cuenta_maestra_id`, `area_gestion_id`, `monto_inicial_asignado`, `monto_comprometido`, `monto_ejecutado`) VALUES
(2, 1, 1, 1, 1000000.00, 0.00, 0.00),
(3, 1, 3, 1, 150000000.00, 0.00, 0.00);

-- --------------------------------------------------------
-- 15. Tabla `prioridades`
-- --------------------------------------------------------
CREATE TABLE `prioridades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `clase_css` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'bg-gray-100 text-gray-800',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `prioridades` (`id`, `codigo`, `nombre`, `clase_css`, `activo`) VALUES
(1, 'NORMAL', 'Normal', 'bg-primary-subtle text-primary-emphasis', 1),
(2, 'URGENTE', 'Urgente', 'bg-warning-subtle text-warning-emphasis fw-bold', 1),
(3, 'EMERGENCIA', 'Emergencia', 'bg-danger-subtle text-danger-emphasis fw-bold', 1);

-- --------------------------------------------------------
-- 16. Tabla `proveedores` (Tabla de Datos Maestros de Proveedores)
-- --------------------------------------------------------
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rut` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `giro` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 17. Tabla `rangos_utm`
-- --------------------------------------------------------
CREATE TABLE `rangos_utm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `min_utm` decimal(10,2) DEFAULT '0.00',
  `max_utm` decimal(10,2) DEFAULT NULL,
  `regla_cotizaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rangos_utm` (`id`, `nombre`, `min_utm`, `max_utm`, `regla_cotizaciones`, `activo`) VALUES
(1, 'Menor', 0.00, 3.00, 'Sin mínimos', 1),
(2, 'Bajo', 3.01, 10.00, 'Mínimo 1 Cotización', 1),
(3, 'Intermedio', 10.01, 100.00, 'Mínimo 3 Cotizaciones', 1),
(4, 'Alto', 100.01, 1000.00, 'Licitación / Gran Compra', 1),
(5, 'Muy Alto', 1000.01, 2000.00, 'Mayores exigencias', 1),
(6, 'Estratégico', 2000.01, 5000.00, 'Aprobación Concejo posible', 1),
(7, 'Mayor', 5000.01, NULL, 'Máxima autoridad', 1);

-- --------------------------------------------------------
-- 18. Tabla `roles`
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN_MUNICIPAL', 'Aprobador Final'),
(2, 'JEFE_UNIDAD', 'Visador Técnico'),
(3, 'PRESUPUESTO', 'Visador Financiero'),
(4, 'ADQUISICIONES', 'Operador de Compras'),
(5, 'USUARIO_REQ', 'Solicitante'),
(6, 'SYSADMIN', 'Superadministrador de Sistemas'),
(7, 'FINANZAS', 'Visador de Finanzas - Emisión de CDP');

-- --------------------------------------------------------
-- 19. Tabla `subrogancias` (Tabla Transaccional - Vacía)
-- --------------------------------------------------------
CREATE TABLE `subrogancias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_titular_id` int NOT NULL,
  `usuario_subrogante_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sub_tit` (`usuario_titular_id`),
  KEY `fk_sub_sup` (`usuario_subrogante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 20. Tabla `tipos_compra`
-- --------------------------------------------------------
CREATE TABLE `tipos_compra` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `limite_utm` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `requiere_cotizacion` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tipos_compra` (`id`, `codigo`, `nombre`, `limite_utm`, `activo`, `requiere_cotizacion`) VALUES
(1, 'SISTEMA_DIRECTO', 'Orden de Compra Por Sistema Directo', NULL, 0, 0),
(2, 'TRATO_DIRECTO', 'Trato Directo', NULL, 1, 0),
(3, 'LICITACION', 'Licitación Pública', NULL, 1, 1),
(4, 'CONVENIO_MARCO', 'Convenio Marco', NULL, 1, 0),
(5, 'CONTRATO_SUMINISTRO', 'Contrato de Suministros', NULL, 1, 0),
(6, 'COMPRA_AGIL', 'Compra Ágil', NULL, 1, 1);

-- --------------------------------------------------------
-- 21. Tabla `unidades`
-- --------------------------------------------------------
CREATE TABLE `unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `padre_id` int DEFAULT NULL,
  `centro_costo_id` int DEFAULT NULL,
  `jefe_actual_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_uni_padre` (`padre_id`),
  KEY `fk_uni_cc` (`centro_costo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- 22. Tabla `usuarios` (Usuarios Semilla de Administración)
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidad_id` int NOT NULL,
  `rol_id` int NOT NULL,
  `rut` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `es_jefe_unidad` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `cargo` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_verificacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT '0',
  `estado_aprobacion` enum('APROBADO','PENDIENTE_VERIFICACION','PENDIENTE_APROBACION','RECHAZADO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'APROBADO',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usr_uni` (`unidad_id`),
  KEY `fk_usr_rol` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `unidad_id`, `rol_id`, `rut`, `nombre_completo`, `email`, `password_hash`, `es_jefe_unidad`, `activo`, `cargo`, `token_verificacion`, `email_verificado`, `estado_aprobacion`, `fecha_registro`) VALUES
(1, 1, 6, '11.111.111-1', 'Departamento de Informática', 'informatica@lebu.cl', '$2y$10$4/wrHWwJnBaCk/q5JGWrK./XJEVgXzQ.3S3p/3iZ1sxhoIXeYOSfq', 0, 1, 'Departamento de informática', NULL, 1, 'APROBADO', CURRENT_TIMESTAMP),
(2, 5, 1, '16.981.872-K', 'Oscar Muñoz Arriagada', 'administrador@lebu.cl', '$2y$10$OHlyMewXSDy2C85PosohWurCRh3fvzGPy.XQ5sYUyIoiQl0IB6yXC', 1, 1, 'Administrador Municipal', NULL, 1, 'APROBADO', CURRENT_TIMESTAMP);

-- --------------------------------------------------------
-- RESTRICCIONES DE CLAVES FORÁNEAS (Foreign Keys)
-- --------------------------------------------------------

ALTER TABLE `cuentas_presupuestarias`
  ADD CONSTRAINT `cuentas_presupuestarias_ibfk_1` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cuenta_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`);

ALTER TABLE `expedientes`
  ADD CONSTRAINT `fk_exp_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_exp_est` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_exp_pri` FOREIGN KEY (`prioridad_id`) REFERENCES `prioridades` (`id`),
  ADD CONSTRAINT `fk_exp_prov` FOREIGN KEY (`proveedor_adjudicado_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `fk_exp_rango` FOREIGN KEY (`rango_utm_id`) REFERENCES `rangos_utm` (`id`),
  ADD CONSTRAINT `fk_exp_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`),
  ADD CONSTRAINT `fk_exp_uni` FOREIGN KEY (`unidad_origen_id`) REFERENCES `unidades` (`id`),
  ADD CONSTRAINT `fk_exp_usr` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `expedientes_criterios`
  ADD CONSTRAINT `fk_criterio_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `expedientes_documentos`
  ADD CONSTRAINT `fk_doc_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `expedientes_firmas`
  ADD CONSTRAINT `fk_fir_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `expedientes_historial`
  ADD CONSTRAINT `fk_hist_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `expedientes_items`
  ADD CONSTRAINT `fk_itm_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_itm_pre` FOREIGN KEY (`presupuesto_asignado_id`) REFERENCES `presupuestos_asignados` (`id`);

ALTER TABLE `flujos_definicion`
  ADD CONSTRAINT `fk_flu_act` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_flu_dest_new` FOREIGN KEY (`estado_destino`) REFERENCES `estados_tramite` (`codigo`),
  ADD CONSTRAINT `fk_flu_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`);

ALTER TABLE `presupuestos_asignados`
  ADD CONSTRAINT `fk_pre_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`),
  ADD CONSTRAINT `fk_pre_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pre_cta` FOREIGN KEY (`cuenta_maestra_id`) REFERENCES `cuentas_maestras` (`id`);

ALTER TABLE `subrogancias`
  ADD CONSTRAINT `fk_sub_sup` FOREIGN KEY (`usuario_subrogante_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_sub_tit` FOREIGN KEY (`usuario_titular_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `unidades`
  ADD CONSTRAINT `fk_uni_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_uni_padre` FOREIGN KEY (`padre_id`) REFERENCES `unidades` (`id`);

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usr_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_usr_uni` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
