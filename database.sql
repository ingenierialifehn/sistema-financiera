-- ============================================
-- SISTEMA FINANCIERO - BASE DE DATOS
-- Compatible con MySQL 8.0+
-- ============================================

CREATE DATABASE IF NOT EXISTS sistema_financiera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_financiera;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    rol ENUM('admin', 'cobrador', 'cliente', 'colaborador') NOT NULL DEFAULT 'cliente',
    estado ENUM('activo', 'inactivo', 'bloqueado') NOT NULL DEFAULT 'activo',
    token_sesion VARCHAR(255) NULL,
    token_expiracion DATETIME NULL,
    ultimo_acceso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado),
    INDEX idx_token (token_sesion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: clientes
-- ============================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    codigo_cliente VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    tipo_documento ENUM('DNI', 'RUC', 'CE') NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20) NOT NULL,
    direccion TEXT,
    fecha_nacimiento DATE,
    ocupacion VARCHAR(100),
    referencia_personal VARCHAR(100),
    telefono_referencia VARCHAR(20),
    foto_documento VARCHAR(255) NULL,
    estado ENUM('activo', 'inactivo', 'en_mora', 'bloqueado') NOT NULL DEFAULT 'activo',
    cobrador_id INT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (cobrador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_cliente),
    INDEX idx_documento (numero_documento),
    INDEX idx_cobrador (cobrador_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: prestamos
-- ============================================
CREATE TABLE IF NOT EXISTS prestamos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    numero_prestamo VARCHAR(20) UNIQUE NOT NULL,
    monto_prestado DECIMAL(10,2) NOT NULL,
    tasa_interes DECIMAL(5,2) NOT NULL,
    periodo_meses INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    monto_cuota DECIMAL(10,2) NOT NULL,
    fecha_desembolso DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    dia_pago INT NOT NULL COMMENT 'Día del mes en que se paga (1-28)',
    estado ENUM('pendiente', 'activo', 'completado', 'cancelado', 'en_mora') NOT NULL DEFAULT 'pendiente',
    observaciones TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_cliente (cliente_id),
    INDEX idx_numero (numero_prestamo),
    INDEX idx_estado (estado),
    INDEX idx_fecha_desembolso (fecha_desembolso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: cuotas
-- ============================================
CREATE TABLE IF NOT EXISTS cuotas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    numero_cuota INT NOT NULL,
    monto_cuota DECIMAL(10,2) NOT NULL,
    monto_pagado DECIMAL(10,2) DEFAULT 0.00,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago DATE NULL,
    estado ENUM('pendiente', 'pagada', 'en_mora', 'cancelada') NOT NULL DEFAULT 'pendiente',
    dias_mora INT DEFAULT 0,
    monto_mora DECIMAL(10,2) DEFAULT 0.00,
    capital_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de capital en esta cuota',
    interes_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de interés en esta cuota (4/11 del interés)',
    gastos_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de gastos financieros en esta cuota (4/11 del interés)',
    comision_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de comisión de papelería en esta cuota (3/11 del interés)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    INDEX idx_prestamo (prestamo_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    UNIQUE KEY uk_prestamo_cuota (prestamo_id, numero_cuota)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: pagos
-- ============================================
CREATE TABLE IF NOT EXISTS pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cuota_id INT NOT NULL,
    prestamo_id INT NOT NULL,
    cliente_id INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    monto_mora DECIMAL(10,2) DEFAULT 0.00,
    fecha_pago DATE NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'deposito', 'otro') NOT NULL DEFAULT 'efectivo',
    comprobante_url VARCHAR(255) NULL,
    observaciones TEXT,
    cobrado_por INT NOT NULL COMMENT 'Usuario que registró el pago',
    estado ENUM('pendiente', 'confirmado', 'rechazado') NOT NULL DEFAULT 'confirmado',
    sincronizado BOOLEAN DEFAULT TRUE COMMENT 'Para PWA offline',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cuota_id) REFERENCES cuotas(id) ON DELETE RESTRICT,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (cobrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_cuota (cuota_id),
    INDEX idx_prestamo (prestamo_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_cobrado_por (cobrado_por),
    INDEX idx_sincronizado (sincronizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: configuraciones
-- ============================================
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    tipo ENUM('texto', 'numero', 'decimal', 'booleano', 'json') NOT NULL DEFAULT 'texto',
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: logs_actividad
-- ============================================
CREATE TABLE IF NOT EXISTS logs_actividad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (usuario, password, nombre_completo, email, rol, estado) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@sistema.com', 'admin', 'activo');

-- Configuraciones iniciales
INSERT INTO configuraciones (clave, valor, tipo, descripcion) VALUES
('tasa_interes_default', '5.00', 'decimal', 'Tasa de interés por defecto (%)'),
('mora_por_dia', '0.50', 'decimal', 'Mora por día de retraso (%)'),
('dias_gracia', '3', 'numero', 'Días de gracia antes de aplicar mora'),
('cloudinary_cloud_name', '', 'texto', 'Cloudinary Cloud Name'),
('cloudinary_api_key', '', 'texto', 'Cloudinary API Key'),
('cloudinary_api_secret', '', 'texto', 'Cloudinary API Secret'),
('nombre_empresa', 'Sistema Financiera', 'texto', 'Nombre de la empresa'),
('moneda', 'PEN', 'texto', 'Código de moneda (PEN, USD, etc.)'),
('simbolo_moneda', 'S/', 'texto', 'Símbolo de moneda');

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Resumen de préstamos por cliente
CREATE OR REPLACE VIEW v_prestamos_resumen AS
SELECT 
    p.id,
    p.numero_prestamo,
    p.cliente_id,
    c.nombre_completo AS cliente_nombre,
    c.codigo_cliente,
    p.monto_prestado,
    p.monto_total,
    p.estado,
    COUNT(cu.id) AS total_cuotas,
    SUM(CASE WHEN cu.estado = 'pagada' THEN 1 ELSE 0 END) AS cuotas_pagadas,
    SUM(CASE WHEN cu.estado = 'pendiente' THEN 1 ELSE 0 END) AS cuotas_pendientes,
    SUM(CASE WHEN cu.estado = 'en_mora' THEN 1 ELSE 0 END) AS cuotas_en_mora,
    SUM(cu.monto_pagado) AS monto_pagado_total,
    (p.monto_total - COALESCE(SUM(cu.monto_pagado), 0)) AS saldo_pendiente
FROM prestamos p
INNER JOIN clientes c ON p.cliente_id = c.id
LEFT JOIN cuotas cu ON p.id = cu.prestamo_id
GROUP BY p.id, p.numero_prestamo, p.cliente_id, c.nombre_completo, c.codigo_cliente, p.monto_prestado, p.monto_total, p.estado;

-- Vista: Resumen de cuotas pendientes
CREATE OR REPLACE VIEW v_cuotas_pendientes AS
SELECT 
    cu.id,
    cu.prestamo_id,
    cu.numero_cuota,
    cu.monto_cuota,
    cu.fecha_vencimiento,
    cu.estado,
    cu.dias_mora,
    cu.monto_mora,
    p.numero_prestamo,
    c.id AS cliente_id,
    c.nombre_completo AS cliente_nombre,
    c.codigo_cliente,
    c.telefono,
    u.id AS cobrador_id,
    u.nombre_completo AS cobrador_nombre
FROM cuotas cu
INNER JOIN prestamos p ON cu.prestamo_id = p.id
INNER JOIN clientes c ON p.cliente_id = c.id
LEFT JOIN usuarios u ON c.cobrador_id = u.id
WHERE cu.estado IN ('pendiente', 'en_mora')
ORDER BY cu.fecha_vencimiento ASC;

