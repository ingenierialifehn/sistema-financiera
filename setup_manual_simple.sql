-- ============================================
-- Script de Configuración Manual
-- Módulo de Bóveda y Operaciones
-- ============================================

USE sistema_financiera;

-- 1. Renombrar saldo_caja a saldo_efectivo en agencias
ALTER TABLE agencias 
CHANGE COLUMN saldo_caja saldo_efectivo DECIMAL(15,2) NOT NULL DEFAULT 0.00;

-- 2. Crear tabla ingresos_bancos_agencia (si no existe)
CREATE TABLE IF NOT EXISTS ingresos_bancos_agencia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    banco_id INT NOT NULL,
    agencia_id INT NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    referencia VARCHAR(100) NULL,
    saldo_anterior_banco DECIMAL(15,2) NOT NULL,
    saldo_nuevo_banco DECIMAL(15,2) NOT NULL,
    saldo_anterior_agencia DECIMAL(15,2) NOT NULL,
    saldo_nuevo_agencia DECIMAL(15,2) NOT NULL,
    realizado_por INT NOT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT NULL,
    FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE RESTRICT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id_agencia) ON DELETE RESTRICT,
    FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_banco (banco_id),
    INDEX idx_agencia (agencia_id),
    INDEX idx_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modificar ENUM de estado en préstamos para incluir 'aprobado'
ALTER TABLE prestamos 
MODIFY COLUMN estado ENUM('pendiente', 'aprobado', 'activo', 'completado', 'cancelado', 'en_mora') 
NOT NULL DEFAULT 'pendiente';

SELECT '✓ Configuración completada' as resultado;
