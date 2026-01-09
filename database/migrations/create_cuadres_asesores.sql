-- Tabla para registrar los cuadres de asesores
CREATE TABLE IF NOT EXISTS `cuadres_asesores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_asesor` INT(11) NOT NULL,
  `id_agencia` INT(11) NOT NULL,
  `fecha_cuadre` DATE NOT NULL,
  `monto_recaudado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_entregado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_banco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `banco_id` INT(11) NULL,
  `referencia_banco` VARCHAR(100) NULL,
  `bloqueado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = bloqueado para cobros, 0 = desbloqueado',
  `observaciones` TEXT NULL,
  `id_usuario_registro` INT(11) NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asesor_fecha` (`id_asesor`, `fecha_cuadre`),
  KEY `idx_agencia` (`id_agencia`),
  CONSTRAINT `fk_cuadre_asesor` FOREIGN KEY (`id_asesor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuadre_agencia` FOREIGN KEY (`id_agencia`) REFERENCES `agencias` (`id_agencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuadre_usuario` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
