-- =========================================================================================
-- MIGRACIÓN SQL: NUEVA ARQUITECTURA OPI Y CADENA DE FIRMAS DIGITALES FIRMAGOB
-- =========================================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ACTUALIZACIÓN / INSERCIÓN DE ESTADOS EN `estados_tramite`
INSERT INTO `estados_tramite` (`codigo`, `nombre`, `rol_responsable`, `descripcion`) VALUES
('BORRADOR', 'Borrador / Creación de OPI', 'USUARIO_REQ', 'OPI en redacción por el usuario requirente'),
('EN_REVISION_JEFATURA', 'Esperando V°B° Jefatura', 'JEFE_UNIDAD', 'Revisión y autorización de inicio técnica'),
('EN_VALIDACION_PRESUPUESTARIA', 'Esperando V°B° Presupuesto Inicial', 'PRESUPUESTO', 'Verificación de saldo preliminar estimado'),
('EN_AUTORIZACION_COTIZACION', 'Esperando Autorización para Cotizar', 'ADMIN_MUNICIPAL', 'Autorización de Administrador para salir al portal'),
('EN_GESTION_ADQUISICIONES', 'En Proceso de Cotización / Portal', 'ADQUISICIONES', 'Publicación y recepción de ofertas'),
('EN_EVALUACION_OFERTAS', 'Evaluación de Ofertas / Selección Proveedor', 'USUARIO_REQ', 'Usuario requirente evalúa ofertas y adjudica'),
('EN_FIRMA_JEFATURA', 'Esperando Firma Digital Jefatura (1/3)', 'JEFE_UNIDAD', '1ª Firma electrónica FirmaGob en la OPI adjudicada'),
('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'Imputación Final y Borrador CDP', 'PRESUPUESTO', '2ª Firma electrónica FirmaGob OPI (Presupuesto) y emisión de borrador CDP'),
('ESPERANDO_CDP_FINANZAS_FINAL', 'Esperando Firma Oficial CDP (Finanzas)', 'FINANZAS', 'Firma electrónica FirmaGob en CDP Oficial'),
('EN_APROBACION_ADMINISTRADOR', 'Esperando Firma Administrador (3/3)', 'ADMIN_MUNICIPAL', '3ª Firma electrónica FirmaGob OPI, folio oficial y autorización OC'),
('EN_EMISION_OC', 'Esperando Emisión de Orden de Compra', 'ADQUISICIONES', 'Adquisiciones emite y sube OC de Mercado Público'),
('FINALIZADO', 'OPI Tramitada y Finalizada', 'SISTEMA', 'Trámite concluido con OC adjunta'),
('EN_CORRECCION', 'Devuelto para Correcciones', 'USUARIO_REQ', 'OPI devuelta para correcciones'),
('RECHAZADO', 'Rechazado Definitivamente', 'SISTEMA', 'OPI rechazada y cerrada'),
('ANULADO', 'Anulado por Usuario', 'SISTEMA', 'OPI anulada')
ON DUPLICATE KEY UPDATE 
    `nombre` = VALUES(`nombre`), 
    `rol_responsable` = VALUES(`rol_responsable`), 
    `descripcion` = VALUES(`descripcion`);

-- 2. AMPLIACIÓN DE `expedientes_firmas` PARA METADATOS DE FIRMAGOB
-- Verificar si ya existen las columnas antes de agregarlas
ALTER TABLE `expedientes_firmas`
ADD COLUMN IF NOT EXISTS `etapa_firma` ENUM('JEFATURA', 'PRESUPUESTO', 'FINANZAS', 'ADMIN_MUNICIPAL') NOT NULL DEFAULT 'ADMIN_MUNICIPAL' AFTER `cargo_firmante`,
ADD COLUMN IF NOT EXISTS `firmagob_solicitud_id` VARCHAR(50) NULL AFTER `cargo_firmante`,
ADD COLUMN IF NOT EXISTS `checksum_original` VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS `checksum_signed` VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS `tipo_firma` ENUM('FIRMAGOB_ATENDIDA', 'FIRMAGOB_DESATENDIDA', 'MANUAL_DOCDIGITAL') DEFAULT 'FIRMAGOB_ATENDIDA',
ADD COLUMN IF NOT EXISTS `ip_origen` VARCHAR(45) NULL;

-- 3. ACTUALIZACIÓN DE TRANSICIONES EN `flujos_definicion` PARA COMPRA ÁGIL (tipo 6) Y LICITACIÓN (tipo 3)
-- Limpiar transiciones previas de Compra Ágil (6) y Licitación (3)
DELETE FROM `flujos_definicion` WHERE `tipo_compra_id` IN (3, 6);

-- Insertar transiciones limpias para COMPRA ÁGIL (6)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`) VALUES
-- FASE 1: Autorizaciones Previas
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

-- FASE 2: Cotización y Adjudicación
(6, 'EN_GESTION_ADQUISICIONES', 0, 'APROBAR', 'Enviar Ofertas a Evaluación del Solicitante', 'EN_EVALUACION_OFERTAS', 0),
(6, 'EN_GESTION_ADQUISICIONES', 0, 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1),
(6, 'EN_GESTION_ADQUISICIONES', 0, 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1),

(6, 'EN_EVALUACION_OFERTAS', 0, 'ADJUDICAR', 'Adjudicar Oferta y Compilar OPI', 'EN_FIRMA_JEFATURA', 0),
(6, 'EN_EVALUACION_OFERTAS', 0, 'DEVOLVER', 'Devolver a Adquisiciones para Recotizar', 'EN_GESTION_ADQUISICIONES', 1),
(6, 'EN_EVALUACION_OFERTAS', 0, 'RECHAZAR', 'Desestimar Ofertas y Cerrar', 'RECHAZADO', 1),

-- FASE 3: Cadena de Firmas OPI + Firma Oficial CDP
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

-- FASE 4: Emisión OC y Cierre
(6, 'EN_EMISION_OC', 0, 'ADJUNTAR_OC', 'Adjuntar OC y Finalizar', 'FINALIZADO', 0),
(6, 'EN_EMISION_OC', 0, 'DEVOLVER', 'Devolver a Administrador', 'EN_APROBACION_ADMINISTRADOR', 1);

-- Duplicar misma estructura lógica para LICITACIÓN PÚBLICA (3)
INSERT INTO `flujos_definicion` (`tipo_compra_id`, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`)
SELECT 3, `estado_actual`, `requiere_archivo`, `accion_codigo`, `accion_label`, `estado_destino`, `requiere_comentario`
FROM `flujos_definicion` WHERE `tipo_compra_id` = 6;

SET FOREIGN_KEY_CHECKS = 1;
