-- Recrear tabla ingresos_bancos_agencia con estructura correcta
USE sistema_financiera;

-- Eliminar tabla existente
DROP TABLE IF EXISTS ingresos_bancos_agencia;

-- Crear tabla con la estructura correcta
CREATE TABLE ingresos_bancos_agencia (
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

SELECT '✓ Tabla ingresos_bancos_agencia recreada correctamente' as resultado;
