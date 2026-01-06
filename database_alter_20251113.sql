-- ALTERACIONES 2025-11-13: modalidades, tasas sugeridas, agencias, garantias, referencias, ubicacion, abonos a capital

USE sistema_financiera;

-- prestamos: modalidad y tasa sugerida
ALTER TABLE prestamos 
  ADD COLUMN IF NOT EXISTS modalidad ENUM('diario','semanal','catorcenal','mensual') NOT NULL DEFAULT 'mensual' AFTER dia_pago,
  ADD COLUMN IF NOT EXISTS tasa_interes_sugerida DECIMAL(5,2) NULL AFTER tasa_interes;

-- configuraciones: tasas por modalidad y mes laboral
INSERT INTO configuraciones (clave, valor, tipo, descripcion) VALUES
('tasa_diario', '6.00', 'decimal', 'Tasa sugerida modalidad diario (%)'),
('tasa_semanal', '5.00', 'decimal', 'Tasa sugerida modalidad semanal (%)'),
('tasa_catorcenal', '4.50', 'decimal', 'Tasa sugerida modalidad catorcenal (%)'),
('tasa_mensual', '4.00', 'decimal', 'Tasa sugerida modalidad mensual (%)'),
('mes_laboral_dias', '20', 'numero', 'Cantidad de dias laborables por mes (L-V)')
ON DUPLICATE KEY UPDATE valor=VALUES(valor), tipo=VALUES(tipo), descripcion=VALUES(descripcion);

-- agencias
CREATE TABLE IF NOT EXISTS agencias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  pais VARCHAR(50) NOT NULL,
  direccion TEXT NULL,
  estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_nombre (nombre),
  INDEX idx_pais (pais),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- clientes: negocio, pais, agencia, ubicacion, documentos
ALTER TABLE clientes 
  ADD COLUMN IF NOT EXISTS pais VARCHAR(50) NULL AFTER direccion,
  ADD COLUMN IF NOT EXISTS agencia_id INT NULL AFTER pais,
  ADD COLUMN IF NOT EXISTS lat DECIMAL(10,7) NULL AFTER telefono_referencia,
  ADD COLUMN IF NOT EXISTS lng DECIMAL(10,7) NULL AFTER lat,
  ADD COLUMN IF NOT EXISTS rtn VARCHAR(30) NULL AFTER ocupacion,
  ADD COLUMN IF NOT EXISTS direccion_negocio TEXT NULL AFTER direccion,
  ADD COLUMN IF NOT EXISTS foto_identidad VARCHAR(255) NULL AFTER foto_documento,
  ADD COLUMN IF NOT EXISTS foto_negocio VARCHAR(255) NULL AFTER foto_identidad,
  ADD COLUMN IF NOT EXISTS recibo_publico VARCHAR(255) NULL AFTER foto_negocio,
  ADD CONSTRAINT IF NOT EXISTS fk_clientes_agencia FOREIGN KEY (agencia_id) REFERENCES agencias(id) ON DELETE SET NULL;

-- garantias
CREATE TABLE IF NOT EXISTS garantias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  prestamo_id INT NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  descripcion TEXT NULL,
  monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
  INDEX idx_prestamo (prestamo_id),
  INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- referencias personales
CREATE TABLE IF NOT EXISTS referencias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  cliente_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  parentesco VARCHAR(50) NULL,
  direccion TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  INDEX idx_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- abonos a capital
CREATE TABLE IF NOT EXISTS abonos_capital (
  id INT PRIMARY KEY AUTO_INCREMENT,
  prestamo_id INT NOT NULL,
  cliente_id INT NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  fecha DATE NOT NULL,
  observaciones TEXT NULL,
  registrado_por INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
  INDEX idx_prestamo (prestamo_id),
  INDEX idx_cliente (cliente_id),
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
