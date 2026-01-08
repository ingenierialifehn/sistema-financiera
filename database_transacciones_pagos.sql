-- Tabla de Transacciones de Pagos
-- Registra cada pago individual realizado por el cliente

CREATE TABLE IF NOT EXISTS transacciones_pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    cuota_id INT NULL COMMENT 'NULL si es abono a capital',
    
    -- Información del pago
    monto_transaccion DECIMAL(10,2) NOT NULL COMMENT 'Monto de este pago específico',
    tipo_pago ENUM('cuota', 'capital', 'mora') NOT NULL DEFAULT 'cuota',
    
    -- Desglose del pago (proporcional al monto)
    capital_aplicado DECIMAL(10,2) DEFAULT 0.00,
    interes_aplicado DECIMAL(10,2) DEFAULT 0.00,
    gastos_aplicados DECIMAL(10,2) DEFAULT 0.00,
    comision_aplicada DECIMAL(10,2) DEFAULT 0.00,
    mora_aplicada DECIMAL(10,2) DEFAULT 0.00,
    
    -- Información de la transacción
    fecha_pago DATETIME NOT NULL,
    usuario_cobro_id INT NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'tarjeta') DEFAULT 'efectivo',
    referencia VARCHAR(100) NULL COMMENT 'Número de referencia, cheque, etc.',
    
    -- Estado de la cuota después de este pago
    estado_cuota_despues ENUM('pendiente', 'parcial', 'pagada') NULL,
    saldo_cuota_despues DECIMAL(10,2) NULL COMMENT 'Saldo restante de la cuota después de este pago',
    
    -- Observaciones
    observaciones TEXT NULL,
    
    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    FOREIGN KEY (cuota_id) REFERENCES cuotas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_cobro_id) REFERENCES usuarios(id_usuario),
    
    INDEX idx_prestamo (prestamo_id),
    INDEX idx_cuota (cuota_id),
    INDEX idx_fecha (fecha_pago),
    INDEX idx_usuario (usuario_cobro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
