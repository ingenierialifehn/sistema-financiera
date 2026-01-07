USE sistema_financiera;

-- ============================================
-- MODULO DE TESORERIA
-- ============================================

-- Tabla de Bancos
CREATE TABLE IF NOT EXISTS bancos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_banco VARCHAR(100) NOT NULL,
    numero_cuenta VARCHAR(50) UNIQUE NOT NULL,
    tipo_cuenta VARCHAR(50) DEFAULT 'Ahorro',
    moneda VARCHAR(10) DEFAULT 'HNL',
    saldo_actual DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Movimientos Bancarios (Historial)
CREATE TABLE IF NOT EXISTS movimientos_bancarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    banco_id INT NOT NULL,
    tipo_transaccion ENUM('ingreso', 'egreso', 'traspaso_caja') NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    saldo_anterior DECIMAL(15,2) NOT NULL,
    saldo_nuevo DECIMAL(15,2) NOT NULL,
    referencia VARCHAR(100) NULL,
    descripcion TEXT NULL,
    realizado_por INT NOT NULL,
    entidad_destino_tipo ENUM('usuario', 'agencia', 'banco', 'externo') NULL,
    entidad_destino_id INT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE CASCADE,
    FOREIGN KEY (realizado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_banco (banco_id),
    INDEX idx_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar usuarios con saldo en caja virtual (Cajeros)
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS saldo_caja_virtual DECIMAL(15,2) NOT NULL DEFAULT 0.00;

-- Actualizar agencias con saldo en caja
ALTER TABLE agencias 
ADD COLUMN IF NOT EXISTS saldo_caja DECIMAL(15,2) NOT NULL DEFAULT 0.00;
