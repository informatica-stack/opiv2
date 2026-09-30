-- =========================================================================================
-- MÓDULO FIRMADOR INSTITUCIONAL (FIRMA AL PASO) - AUDITORÍA Y TRAZABILIDAD LEGAL
-- =========================================================================================

CREATE TABLE IF NOT EXISTS `firmas_al_paso_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `run_firmante` varchar(20) NOT NULL,
  `nombre_firmante` varchar(150) NOT NULL,
  `cargo_firmante` varchar(150) DEFAULT NULL,
  `es_subrogante` tinyint(1) NOT NULL DEFAULT 0,
  `subrogado_nombre` varchar(150) DEFAULT NULL,
  `nombre_archivo_original` varchar(255) NOT NULL,
  `tamano_bytes` bigint NOT NULL,
  `checksum_original_sha256` varchar(64) NOT NULL,
  `checksum_firmado_sha256` varchar(64) NOT NULL,
  `firmagob_id_solicitud` varchar(100) DEFAULT NULL,
  `tipo_firma` enum('VISIBLE','INVISIBLE') NOT NULL DEFAULT 'VISIBLE',
  `modo_firma` enum('DESATENDIDA','ATENDIDA') NOT NULL DEFAULT 'DESATENDIDA',
  `pagina_firmada` int DEFAULT NULL,
  `coord_llx` float DEFAULT NULL,
  `coord_lly` float DEFAULT NULL,
  `coord_urx` float DEFAULT NULL,
  `coord_ury` float DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_run_firmante` (`run_firmante`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
