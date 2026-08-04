-- Script SQL para actualizar la base de datos en producción para Convenio Marco y Decreto Alcaldicio
-- Fecha: 2026-08-04

-- 1. Campos adicionales en la tabla expedientes
ALTER TABLE `expedientes`
  ADD COLUMN `decreto_alcaldicio_numero` VARCHAR(100) DEFAULT NULL AFTER `orden_compra_numero`,
  ADD COLUMN `conv_marco_oc` VARCHAR(100) DEFAULT NULL AFTER `decreto_alcaldicio_numero`;

-- 2. Actualización de tipos de documento soportados en expedientes_documentos
ALTER TABLE `expedientes_documentos`
  MODIFY COLUMN `tipo_doc` ENUM(
    'TDR_ESPECIFICACIONES',
    'COTIZACION_RESPALDO',
    'CUADRO_COMPARATIVO',
    'OPI_FIRMADA_PDF',
    'OPI_ANULADA',
    'FICHA_PROVEEDOR',
    'CDP_BORRADOR',
    'SITUACION_PRESUPUESTARIA',
    'DECRETO_ALCALDICIO',
    'OTRO'
  ) NOT NULL;
