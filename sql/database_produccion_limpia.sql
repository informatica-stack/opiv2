-- =========================================================================================
-- SISTEMA OPI MUNICIPAL (OPIv2) - ESQUEMA Y DATOS SEMILLA PARA PRODUCCIÓN
-- Arquitectura Unificada OPI y Cadena de Firmas FirmaGob v18 (Secretaría de Gobierno Digital)
-- =========================================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-03:00";

-- -----------------------------------------------------------------------------------------
-- 1. ESTRUCTURAS DE TABLAS MAESTRAS Y DE CONFIGURACIÓN
-- -----------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `configuraciones_sistema`;
CREATE TABLE `configuraciones_sistema` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuraciones_sistema` (`clave`, `valor`, `descripcion`) VALUES
('extensiones_permitidas', 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png', 'Extensiones de archivo autorizadas para carga'),
('limite_peso_adjunto_mb', '50', 'Límite máximo de peso de archivos adjuntos en Megabytes (MB)'),
('modo_mantenimiento', '0', 'Indica si el sistema está en mantenimiento (1 = Sí, 0 = No)'),
('valor_utm', '66000', 'Valor de 1 UTM en pesos chilenos (CLP)');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `areas_gestion`;
CREATE TABLE `areas_gestion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `areas_gestion` (`id`, `codigo`, `nombre`, `activo`) VALUES
(1, 'AG 01', 'Gestión Interna', 1),
(2, 'AG 02', 'Servicios Comunitarios', 1),
(3, 'S/I', 'Sin Imputación', 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `centros_costo`;
CREATE TABLE `centros_costo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_cuenta` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `anio_fiscal` int NOT NULL DEFAULT 2026,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `centros_costo` (`id`, `codigo_cuenta`, `nombre`, `anio_fiscal`, `activo`) VALUES
(1, '11', 'Depto. Informática', 2026, 1),
(2, '12', 'Administración', 2026, 1),
(3, '13', 'DIDECO', 2026, 1),
(4, '9000', 'Dirección Servicios Generales', 2026, 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `cuentas_maestras`;
CREATE TABLE `cuentas_maestras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo_cuenta` enum('PRESUPUESTARIA','COMPLEMENTARIA') NOT NULL DEFAULT 'PRESUPUESTARIA',
  `presupuesto_global_total` decimal(15,2) DEFAULT '0.00',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cuentas_maestras` (`id`, `codigo`, `nombre`, `tipo_cuenta`, `presupuesto_global_total`, `activo`) VALUES
(1, '2152204001001', 'Por propuesta pública', 'PRESUPUESTARIA', 0.00, 1),
(3, '2152906001', 'Equipos computacionales y periféricos', 'PRESUPUESTARIA', 0.00, 1),
(4, '2152401007001', 'Canastas', 'PRESUPUESTARIA', 0.00, 1),
(5, '2152201001001', 'Actividades propias', 'PRESUPUESTARIA', 0.00, 1),
(6, '2152907001', 'Programas Computacionales', 'PRESUPUESTARIA', 0.00, 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `presupuestos_asignados`;
CREATE TABLE `presupuestos_asignados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `centro_costo_id` int NOT NULL,
  `cuenta_maestra_id` int NOT NULL,
  `area_gestion_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pa_cc` (`centro_costo_id`),
  KEY `fk_pa_cm` (`cuenta_maestra_id`),
  KEY `fk_pa_ag` (`area_gestion_id`),
  CONSTRAINT `fk_pa_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pa_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pa_cm` FOREIGN KEY (`cuenta_maestra_id`) REFERENCES `cuentas_maestras` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `presupuestos_asignados` (`id`, `centro_costo_id`, `cuenta_maestra_id`, `area_gestion_id`) VALUES
(3, 1, 3, 1),
(4, 3, 4, 1),
(5, 1, 6, 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `prioridades`;
CREATE TABLE `prioridades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `clase_css` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `prioridades` (`id`, `codigo`, `nombre`, `clase_css`, `activo`) VALUES
(1, 'BAJA', 'Baja', 'bg-gray-100 text-gray-600 border border-gray-200', 1),
(2, 'MEDIA', 'Media', 'bg-blue-50 text-blue-700 border border-blue-100', 1),
(3, 'ALTA', 'Alta / Urgencia', 'bg-red-50 text-red-700 border border-red-100 animate-pulse', 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `rangos_utm`;
CREATE TABLE `rangos_utm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `min_utm` decimal(10,2) DEFAULT '0.00',
  `max_utm` decimal(10,2) DEFAULT NULL,
  `regla_cotizaciones` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rangos_utm` (`id`, `nombre`, `min_utm`, `max_utm`, `regla_cotizaciones`, `activo`) VALUES
(1, 'Bajo', 0.00, 3.00, 'Sin mínimos', 1),
(3, 'Medio', 3.01, 100.00, NULL, 1),
(4, 'Alto', 100.01, 1000.00, 'Licitación / Gran Compra', 1),
(5, 'Muy alto', 1000.01, 5000.00, 'Mayores exigencias', 1),
(6, 'Sin límite', 5000.01, NULL, 'Aprobación Concejo posible', 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `tipos_compra`;
CREATE TABLE `tipos_compra` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `limite_utm` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `requiere_cotizacion` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tipos_compra` (`id`, `codigo`, `nombre`, `limite_utm`, `activo`, `requiere_cotizacion`) VALUES
(1, 'SISTEMA_DIRECTO', 'Orden de Compra Por Sistema Directo', NULL, 0, 0),
(2, 'TRATO_DIRECTO', 'Trato Directo', NULL, 1, 0),
(3, 'LICITACION', 'Licitación Pública', NULL, 1, 1),
(4, 'CONVENIO_MARCO', 'Convenio Marco', NULL, 1, 0),
(5, 'CONTRATO_SUMINISTRO', 'Contrato de Suministros', NULL, 1, 0),
(6, 'COMPRA_AGIL', 'Compra Ágil', 100.00, 1, 1);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN_MUNICIPAL', 'Administrador Municipal - Aprobador Final y Firma 3/3'),
(2, 'JEFE_UNIDAD', 'Jefatura de Unidad - Visador Técnico y Firma 1/3'),
(3, 'PRESUPUESTO', 'Control Presupuestario - Visador Financiero y Firma 2/3'),
(4, 'ADQUISICIONES', 'Departamento de Adquisiciones - Gestión de Compras y OC'),
(5, 'USUARIO_REQ', 'Usuario Requirente - Solicitante y Evaluador de Ofertas'),
(6, 'SYSADMIN', 'Superadministrador de Sistemas'),
(7, 'FINANZAS', 'Dirección de Finanzas (DAF) - Firma Oficial CDP');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `unidades`;
CREATE TABLE `unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `padre_id` int DEFAULT NULL,
  `centro_costo_id` int DEFAULT NULL,
  `jefe_actual_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_unidades_padre` (`padre_id`),
  KEY `fk_unidades_cc` (`centro_costo_id`),
  CONSTRAINT `fk_unidades_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_unidades_padre` FOREIGN KEY (`padre_id`) REFERENCES `unidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `unidades` (`id`, `nombre`, `padre_id`, `centro_costo_id`, `jefe_actual_id`) VALUES
(2, 'DIDECO', NULL, NULL, NULL),
(3, 'PRESUPUESTO', NULL, NULL, NULL),
(5, 'Administración municipal', NULL, NULL, NULL),
(7, 'Alcaldía', NULL, NULL, NULL),
(8, 'Tránsito', NULL, NULL, NULL),
(9, 'Juzgado policía local', NULL, NULL, NULL),
(10, 'Finanzas', NULL, NULL, NULL),
(11, 'Transparencia', NULL, NULL, NULL),
(12, 'Personal', NULL, NULL, NULL),
(13, 'RR.HH', NULL, NULL, NULL),
(14, 'Jurídico', NULL, NULL, NULL),
(15, 'OIRS', NULL, NULL, NULL),
(17, 'Gabinete', NULL, NULL, NULL),
(18, 'Secretaría Municipal', NULL, NULL, NULL),
(19, 'UDEL', NULL, NULL, NULL),
(20, 'SS.GG', NULL, NULL, NULL),
(21, 'SECPLAN', NULL, NULL, NULL),
(22, 'Obras', NULL, NULL, NULL),
(24, 'DAS-CESFAM', NULL, NULL, NULL),
(25, 'Control', NULL, NULL, NULL),
(26, 'Dirección de obras', NULL, NULL, NULL),
(27, 'Vivienda', NULL, NULL, NULL),
(28, 'Permisos de circulación', NULL, NULL, NULL),
(29, 'Oficina Local de la Niñez (ex OPD)', NULL, NULL, NULL),
(30, 'PRODESAL', NULL, NULL, NULL),
(31, 'Deportes', NULL, NULL, NULL),
(32, 'Turismo', NULL, NULL, NULL),
(33, 'Cultura', NULL, NULL, NULL),
(34, 'Fomento Productivo', NULL, NULL, NULL),
(35, 'Medio Ambiente', NULL, NULL, NULL),
(36, 'Seguridad Pública', NULL, NULL, NULL),
(37, 'Operaciones', NULL, NULL, NULL),
(38, 'Aseo y Ornato', NULL, NULL, NULL),
(39, 'Alumbrado Público', NULL, NULL, NULL),
(40, 'Cementerio', NULL, NULL, NULL),
(41, 'Maestranza', NULL, NULL, NULL),
(42, 'Rentas y Patentes', NULL, NULL, NULL),
(43, 'Tesorería Municipal', NULL, NULL, NULL),
(1, 'Informática', 5, 1, NULL),
(4, 'Adquisiciones', 10, NULL, NULL),
(6, 'OLN', 2, NULL, NULL),
(16, 'Of. Partes', 18, NULL, NULL),
(23, 'Autogeneración Isla Mocha', 5, NULL, NULL);

-- --------------------------------------------------------

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidad_id` int NOT NULL,
  `rol_id` int NOT NULL,
  `rut` varchar(12) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `es_jefe_unidad` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `cargo` varchar(150) DEFAULT NULL,
  `token_verificacion` varchar(100) DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT '0',
  `estado_aprobacion` enum('APROBADO','PENDIENTE_VERIFICACION','PENDIENTE_APROBACION','RECHAZADO') NOT NULL DEFAULT 'APROBADO',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_rut` (`rut`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `fk_usuarios_unidad` (`unidad_id`),
  KEY `fk_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_usuarios_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `unidad_id`, `rol_id`, `rut`, `nombre_completo`, `email`, `password_hash`, `es_jefe_unidad`, `activo`, `cargo`, `token_verificacion`, `email_verificado`, `estado_aprobacion`, `fecha_registro`) VALUES
(1, 1, 6, '11.111.111-1', 'Departamento de Informática', 'informatica@lebu.cl', '$2y$10$4/wrHWwJnBaCk/q5JGWrK./XJEVgXzQ.3S3p/3iZ1sxhoIXeYOSfq', 0, 1, 'Departamento de informática', NULL, 1, 'APROBADO', '2026-08-05 14:31:30'),
(2, 5, 5, '15.737.866-K', 'Usuario Requirente Inicial', 'usuario2@usuario2.cl', '$2y$10$eiAkeJKv1tz5QFrGovud1eUXF9PfovjlpV1FzIr0rUbakNYIZ4eum', 0, 1, 'Funcionario Municipal', NULL, 1, 'APROBADO', '2026-08-24 17:59:39'),
(3, 1, 6, '17439829-1', 'Juan Carlos Arriagada', 'juancarlosarriagada219@gmail.com', NULL, 1, 1, 'Técnico Informática', NULL, 1, 'APROBADO', '2026-09-01 17:28:51'),
(4, 1, 5, '19511214-2', 'Diego Gerardo Castro Carrillo', 'diego.castro.carrillo@gmail.com', NULL, 0, 1, 'Técnico Informática', NULL, 1, 'APROBADO', '2026-09-02 09:22:57'),
(5, 3, 3, '16108513-8', 'Roxana Bernal Vásquez', 'rbernal@lebu.cl', NULL, 0, 1, 'Profesional Presupuesto', NULL, 1, 'APROBADO', '2026-09-02 15:33:32');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `subrogancias`;
CREATE TABLE `subrogancias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_titular_id` int NOT NULL,
  `usuario_subrogante_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_subrogancias_titular` (`usuario_titular_id`),
  KEY `fk_subrogancias_subrogante` (`usuario_subrogante_id`),
  CONSTRAINT `fk_subrogancias_subrogante` FOREIGN KEY (`usuario_subrogante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subrogancias_titular` FOREIGN KEY (`usuario_titular_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rut` varchar(12) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proveedores_rut` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `proveedores` (`id`, `rut`, `razon_social`, `direccion`, `activo`) VALUES
(1, '77.508.642-4', 'VENTA, COMPRAS DE EQUIPOS & ACCESORIOS TECNOLOGICOS SPA', '', 1),
(2, '96.678.350-8', 'TECHNOSYSTEMS CHILE SPA', '', 1);

-- -----------------------------------------------------------------------------------------
-- 2. ESTADOS DEL TRÁMITE Y MOTOR DE FLUJOS DINÁMICOS
-- -----------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `estados_tramite`;
CREATE TABLE `estados_tramite` (
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol_responsable` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `estados_tramite` (`codigo`, `nombre`, `rol_responsable`, `descripcion`) VALUES
('BORRADOR', 'Borrador / Creación de OPI', 'USUARIO_REQ', 'OPI en redacción por el usuario requirente'),
('EN_REVISION_JEFATURA', 'Esperando V°B° Jefatura', 'JEFE_UNIDAD', 'Revisión y autorización técnica preliminar'),
('EN_VALIDACION_PRESUPUESTARIA', 'Esperando V°B° Presupuesto Inicial', 'PRESUPUESTO', 'Verificación de saldo preliminar estimado'),
('EN_AUTORIZACION_COTIZACION', 'Esperando Autorización para Cotizar', 'ADMIN_MUNICIPAL', 'Autorización de Administrador para cotizar en portal'),
('EN_GESTION_ADQUISICIONES', 'En Proceso de Cotización / Portal', 'ADQUISICIONES', 'Publicación y recepción de ofertas en Mercado Público'),
('EN_EVALUACION_OFERTAS', 'Evaluación de Ofertas / Selección Proveedor', 'USUARIO_REQ', 'Usuario requirente evalúa ofertas, adjunta acta y adjudica'),
('EN_FIRMA_JEFATURA', 'Esperando Firma Digital Jefatura (1/3)', 'JEFE_UNIDAD', '1ª Firma electrónica FirmaGob en la OPI base'),
('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'Imputación Final y Borrador CDP', 'PRESUPUESTO', '2ª Firma electrónica FirmaGob OPI (Presupuesto) y emisión de borrador CDP'),
('ESPERANDO_CDP_FINANZAS_FINAL', 'Esperando Firma Oficial CDP (Finanzas)', 'FINANZAS', 'Firma electrónica FirmaGob en CDP Oficial por Director/a DAF'),
('EN_APROBACION_ADMINISTRADOR', 'Esperando Firma Administrador (3/3)', 'ADMIN_MUNICIPAL', '3ª Firma electrónica FirmaGob OPI, asignación de folio y pase a OC'),
('EN_EMISION_OC', 'Esperando Emisión de Orden de Compra', 'ADQUISICIONES', 'Adquisiciones emite y sube OC de Mercado Público'),
('ESPERANDO_ACEPTACION_OC', 'Esperando Aceptación del Proveedor', 'ADQUISICIONES', 'Adquisiciones supervisa la aceptación o rechazo de la OC en Mercado Público'),
('FINALIZADO', 'OPI Tramitada y Finalizada', 'SISTEMA', 'Trámite concluido con OC y decreto adjuntos'),
('EN_CORRECCION', 'Devuelto para Correcciones', 'USUARIO_REQ', 'Expediente devuelto para subsanar observaciones'),
('RECHAZADO', 'Rechazado Definitivamente', 'SISTEMA', 'Expediente rechazado y archivado'),
('ANULADO', 'Anulado por Usuario', 'SISTEMA', 'Expediente cancelado por el requirente');

-- --------------------------------------------------------

DROP TABLE IF EXISTS `flujos_definicion`;
CREATE TABLE `flujos_definicion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_compra_id` int NOT NULL,
  `estado_actual` varchar(50) NOT NULL,
  `rango_utm_id` int DEFAULT NULL,
  `monto_min_utm` decimal(10,2) DEFAULT NULL,
  `monto_max_utm` decimal(10,2) DEFAULT NULL,
  `requiere_archivo` tinyint(1) DEFAULT '0',
  `accion_codigo` varchar(50) NOT NULL,
  `accion_label` varchar(100) NOT NULL,
  `estado_destino` varchar(50) NOT NULL,
  `requiere_comentario` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_flujos_tipo` (`tipo_compra_id`),
  KEY `fk_flujos_est_act` (`estado_actual`),
  KEY `fk_flujos_est_dest` (`estado_destino`),
  CONSTRAINT `fk_flujos_est_act` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`) ON DELETE CASCADE,
  CONSTRAINT `fk_flujos_est_dest` FOREIGN KEY (`estado_destino`) REFERENCES `estados_tramite` (`codigo`) ON DELETE CASCADE,
  CONSTRAINT `fk_flujos_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- TRANSICIONES LIMPIAS: COMPRA ÁGIL (tipo 6)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`) VALUES
(6, 'BORRADOR', 0, 'APROBAR', 'Enviar a Jefatura', 'EN_REVISION_JEFATURA', 0),
(6, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar Requerimiento', 'EN_VALIDACION_PRESUPUESTARIA', 0),
(6, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(6, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Visar Saldo Estimado', 'EN_AUTORIZACION_COTIZACION', 0),
(6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(6, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(6, 'EN_AUTORIZACION_COTIZACION', 0, 'APROBAR', 'Autorizar Inicio de Cotización', 'EN_GESTION_ADQUISICIONES', 0),
(6, 'EN_AUTORIZACION_COTIZACION', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(6, 'EN_AUTORIZACION_COTIZACION', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(6, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Enviar Ofertas a Evaluación del Solicitante', 'EN_EVALUACION_OFERTAS', 0),
(6, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(6, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(6, 'EN_EVALUACION_OFERTAS', 0, 'ADJUDICAR', 'Adjudicar Oferta y Compilar OPI', 'EN_FIRMA_JEFATURA', 0),
(6, 'EN_EVALUACION_OFERTAS', 0, 'DEVOLVER', 'Devolver a Adquisiciones para Recotizar', 'EN_GESTION_ADQUISICIONES', 1),
(6, 'EN_EVALUACION_OFERTAS', 0, 'RECHAZAR', 'Desestimar Ofertas y Cerrar', 'RECHAZADO', 1),
(6, 'EN_FIRMA_JEFATURA', 0, 'FIRMAR_JEFATURA', 'Firmar OPI V°B° Jefatura (1/3)', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0),
(6, 'EN_FIRMA_JEFATURA', 0, 'DEVOLVER', 'Devolver a Solicitante para Reevaluar', 'EN_EVALUACION_OFERTAS', 1),
(6, 'EN_FIRMA_JEFATURA', 0, 'RECHAZAR', 'Rechazar Adjudicación', 'RECHAZADO', 1),
(6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'FIRMAR_PRESUPUESTO', 'Firmar OPI V°B° Presupuesto (2/3) y Emitir CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0),
(6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_EVALUACION_OFERTAS', 1),
(6, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'RECHAZAR', 'Rechazar Imputación', 'RECHAZADO', 1),
(6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'FIRMAR_CDP_FINANZAS', 'Firmar CDP Oficial (Finanzas)', 'EN_APROBACION_ADMINISTRADOR', 0),
(6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'DEVOLVER', 'Devolver a Presupuesto', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1),
(6, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'RECHAZAR', 'Rechazar CDP', 'RECHAZADO', 1),
(6, 'EN_APROBACION_ADMINISTRADOR', 0, 'FIRMAR_ADMIN', 'Firmar y Emitir OPI Definitiva (3/3)', 'EN_EMISION_OC', 0),
(6, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1),
(6, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar OPI', 'RECHAZADO', 1),
(6, 'EN_EMISION_OC', 0, 'APROBAR', 'Registrar OC y Esperar Aceptación', 'ESPERANDO_ACEPTACION_OC', 0),
(6, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver a Administrador', 'EN_APROBACION_ADMINISTRADOR', 1),
(6, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'OC Aceptada en Portal', 'FINALIZADO', 0),
(6, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Rechazo de OC - Devolver a Evaluación', 'EN_EVALUACION_OFERTAS', 1);

-- TRANSICIONES LIMPIAS: LICITACIÓN PÚBLICA (tipo 3)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`)
SELECT 3, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`
FROM `flujos_definicion` WHERE `tipo_compra_id` = 6;

-- TRANSICIONES LIMPIAS: CONVENIO MARCO (tipo 4)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`) VALUES
(4, 'BORRADOR', 0, 'APROBAR', 'Enviar a Jefatura', 'EN_REVISION_JEFATURA', 0),
(4, 'EN_REVISION_JEFATURA', 0, 'APROBAR', 'Aprobar Requerimiento', 'EN_VALIDACION_PRESUPUESTARIA', 0),
(4, 'EN_REVISION_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(4, 'EN_REVISION_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'APROBAR', 'Visar Saldo y Enviar a Adquisiciones', 'EN_GESTION_ADQUISICIONES', 0),
(4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(4, 'EN_VALIDACION_PRESUPUESTARIA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(4, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Seleccionar Catálogo y Enviar a Firma Jefatura', 'EN_FIRMA_JEFATURA', 0),
(4, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(4, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(4, 'EN_FIRMA_JEFATURA', 0, 'FIRMAR_JEFATURA', 'Firmar OPI V°B° Jefatura (1/3)', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0),
(4, 'EN_FIRMA_JEFATURA', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_GESTION_ADQUISICIONES', 1),
(4, 'EN_FIRMA_JEFATURA', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),
(4, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'FIRMAR_PRESUPUESTO', 'Firmar OPI V°B° Presupuesto (2/3) y Emitir CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0),
(4, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_GESTION_ADQUISICIONES', 1),
(4, 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 'RECHAZAR', 'Rechazar Imputación', 'RECHAZADO', 1),
(4, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'FIRMAR_CDP_FINANZAS', 'Firmar CDP Oficial (Finanzas)', 'EN_APROBACION_ADMINISTRADOR', 0),
(4, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'DEVOLVER', 'Devolver a Presupuesto', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1),
(4, 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 'RECHAZAR', 'Rechazar CDP', 'RECHAZADO', 1),
(4, 'EN_APROBACION_ADMINISTRADOR', 0, 'FIRMAR_ADMIN', 'Firmar y Emitir OPI Definitiva (3/3)', 'EN_EMISION_OC', 0),
(4, 'EN_APROBACION_ADMINISTRADOR', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1),
(4, 'EN_APROBACION_ADMINISTRADOR', 0, 'RECHAZAR', 'Rechazar OPI', 'RECHAZADO', 1),
(4, 'EN_EMISION_OC', 0, 'APROBAR', 'Registrar OC y Esperar Aceptación', 'ESPERANDO_ACEPTACION_OC', 0),
(4, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver a Administrador', 'EN_APROBACION_ADMINISTRADOR', 1),
(4, 'ESPERANDO_ACEPTACION_OC', 0, 'APROBAR', 'OC Aceptada en Portal', 'FINALIZADO', 0),
(4, 'ESPERANDO_ACEPTACION_OC', 0, 'DEVOLVER', 'Rechazo de OC - Devolver para Corrección', 'EN_CORRECCION', 1);

-- TRANSICIONES LIMPIAS: TRATO DIRECTO (tipo 2)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`)
SELECT 2, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`
FROM `flujos_definicion` WHERE `tipo_compra_id` = 4;

-- TRANSICIONES LIMPIAS: CONTRATO DE SUMINISTROS (tipo 5)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`)
SELECT 5, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`
FROM `flujos_definicion` WHERE `tipo_compra_id` = 4;

-- -----------------------------------------------------------------------------------------
-- 3. ESTRUCTURAS TRANSACCIONALES DE EXPEDIENTES (LIMPIAS PARA INICIO)
-- -----------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `expedientes`;
CREATE TABLE `expedientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_interno` varchar(50) NOT NULL,
  `titulo_compra` varchar(255) NOT NULL DEFAULT 'Sin Título',
  `folio_opi` varchar(50) DEFAULT NULL,
  `usuario_creador_id` int NOT NULL,
  `unidad_origen_id` int NOT NULL,
  `centro_costo_id` int NOT NULL,
  `tipo_compra_id` int NOT NULL,
  `prioridad_id` int NOT NULL,
  `cuenta_presupuestaria_id` int DEFAULT NULL,
  `cuenta_maestra_id` int DEFAULT NULL,
  `area_gestion_id` int DEFAULT NULL,
  `rango_utm_id` int DEFAULT NULL,
  `proveedor_adjudicado_id` int DEFAULT NULL,
  `id_contrato_suministro` varchar(100) DEFAULT NULL,
  `id_compra_agil` varchar(50) DEFAULT NULL,
  `id_licitacion` varchar(100) DEFAULT NULL,
  `orden_compra_numero` varchar(100) DEFAULT NULL,
  `decreto_alcaldicio_numero` varchar(100) DEFAULT NULL,
  `conv_marco_oc` varchar(100) DEFAULT NULL,
  `monto_estimado` decimal(15,2) NOT NULL DEFAULT '0.00',
  `monto_definitivo` decimal(15,2) DEFAULT NULL,
  `tipo_impuesto` enum('NETO','IVA_INCLUIDO','EXENTO') DEFAULT 'NETO',
  `motivo_compra` text NOT NULL,
  `estado_actual` varchar(50) NOT NULL DEFAULT 'BORRADOR',
  `observacion_cierre` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_visa_presupuesto` datetime DEFAULT NULL,
  `fecha_adjudicacion` datetime DEFAULT NULL,
  `fecha_aprobacion_opi` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `num_certificado_oficial` varchar(20) DEFAULT NULL,
  `id_entidad_gobierno` varchar(50) DEFAULT 'ID PE-MUN-00335',
  `plan_compras_proyecto` varchar(100) DEFAULT NULL,
  `plan_compras_item` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_expedientes_codigo` (`codigo_interno`),
  KEY `fk_exp_usuario` (`usuario_creador_id`),
  KEY `fk_exp_unidad` (`unidad_origen_id`),
  KEY `fk_exp_tipo` (`tipo_compra_id`),
  KEY `fk_exp_prioridad` (`prioridad_id`),
  KEY `fk_exp_cc` (`centro_costo_id`),
  KEY `fk_exp_cm` (`cuenta_maestra_id`),
  KEY `fk_exp_ag` (`area_gestion_id`),
  KEY `fk_exp_prov` (`proveedor_adjudicado_id`),
  KEY `fk_exp_estado` (`estado_actual`),
  CONSTRAINT `fk_exp_ag` FOREIGN KEY (`area_gestion_id`) REFERENCES `areas_gestion` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_cm` FOREIGN KEY (`cuenta_maestra_id`) REFERENCES `cuentas_maestras` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_estado` FOREIGN KEY (`estado_actual`) REFERENCES `estados_tramite` (`codigo`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_prioridad` FOREIGN KEY (`prioridad_id`) REFERENCES `prioridades` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_prov` FOREIGN KEY (`proveedor_adjudicado_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_exp_tipo` FOREIGN KEY (`tipo_compra_id`) REFERENCES `tipos_compra` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_unidad` FOREIGN KEY (`unidad_origen_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_exp_usuario` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_items`;
CREATE TABLE `expedientes_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `presupuesto_asignado_id` int DEFAULT NULL,
  `id_producto_cm` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `unidad_medida` varchar(50) NOT NULL DEFAULT 'UNIDAD',
  `cantidad` decimal(12,2) NOT NULL DEFAULT '1.00',
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_items_exp` (`expediente_id`),
  KEY `fk_items_pa` (`presupuesto_asignado_id`),
  CONSTRAINT `fk_items_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_pa` FOREIGN KEY (`presupuesto_asignado_id`) REFERENCES `presupuestos_asignados` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_documentos`;
CREATE TABLE `expedientes_documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `subido_por_id` int NOT NULL,
  `tipo_doc` varchar(50) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_docs_exp` (`expediente_id`),
  KEY `fk_docs_usr` (`subido_por_id`),
  CONSTRAINT `fk_docs_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_docs_usr` FOREIGN KEY (`subido_por_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_historial`;
CREATE TABLE `expedientes_historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `accion` varchar(50) NOT NULL,
  `estado_anterior` varchar(50) NOT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `comentario` text,
  `fecha_accion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_hist_exp` (`expediente_id`),
  KEY `fk_hist_usr` (`usuario_id`),
  CONSTRAINT `fk_hist_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_firmas`;
CREATE TABLE `expedientes_firmas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `usuario_firmante_id` int DEFAULT NULL,
  `autoridad_id` int DEFAULT NULL,
  `nombre_firmante` varchar(150) DEFAULT NULL,
  `rut_firmante` varchar(12) DEFAULT NULL,
  `cargo_firmante` varchar(150) DEFAULT NULL,
  `firmagob_solicitud_id` varchar(50) DEFAULT NULL,
  `etapa_firma` enum('JEFATURA','PRESUPUESTO','FINANZAS','ADMIN_MUNICIPAL') NOT NULL DEFAULT 'ADMIN_MUNICIPAL',
  `checksum_original` varchar(64) DEFAULT NULL,
  `checksum_signed` varchar(64) DEFAULT NULL,
  `tipo_firma` enum('FIRMAGOB_ATENDIDA','FIRMAGOB_DESATENDIDA','MANUAL_DOCDIGITAL') DEFAULT 'FIRMAGOB_ATENDIDA',
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_firma` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hash_documento` varchar(64) DEFAULT NULL,
  `nombre_archivo_firmado` varchar(255) DEFAULT NULL,
  `valida` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_firmas_exp` (`expediente_id`),
  KEY `fk_firmas_usr` (`usuario_firmante_id`),
  CONSTRAINT `fk_firmas_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_firmas_usr` FOREIGN KEY (`usuario_firmante_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_criterios`;
CREATE TABLE `expedientes_criterios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `numero_criterio` int DEFAULT NULL,
  `nombre_criterio` varchar(150) NOT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `porcentaje_ponderacion` decimal(5,2) DEFAULT NULL,
  `descripcion` text,
  PRIMARY KEY (`id`),
  KEY `fk_criterios_exp` (`expediente_id`),
  CONSTRAINT `fk_criterios_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `expedientes_autorizaciones_cc`;
CREATE TABLE `expedientes_autorizaciones_cc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expediente_id` int NOT NULL,
  `tipo_autorizacion` enum('UNIDAD_ORIGEN','CENTRO_COSTO_EXTERNO') DEFAULT 'CENTRO_COSTO_EXTERNO',
  `centro_costo_id` int NOT NULL,
  `unidad_responsable_id` int NOT NULL,
  `monto_imputado` decimal(15,2) NOT NULL DEFAULT '0.00',
  `estado` enum('PENDIENTE','APROBADO','RECHAZADO','DEVUELTO') DEFAULT 'PENDIENTE',
  `visado_por_id` int DEFAULT NULL,
  `fecha_visacion` datetime DEFAULT NULL,
  `comentario` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exp_aut_exp` (`expediente_id`),
  KEY `idx_exp_aut_cc` (`centro_costo_id`),
  KEY `idx_exp_aut_un` (`unidad_responsable_id`),
  KEY `idx_exp_aut_estado` (`estado`),
  CONSTRAINT `fk_aut_exp` FOREIGN KEY (`expediente_id`) REFERENCES `expedientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aut_cc` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`),
  CONSTRAINT `fk_aut_un` FOREIGN KEY (`unidad_responsable_id`) REFERENCES `unidades` (`id`),
  CONSTRAINT `fk_aut_usr` FOREIGN KEY (`visado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;