-- ============================================
-- Script de Configuración Manual
-- Módulo de Bóveda y Operaciones
-- ============================================

USE sistema_financiera;

-- 1. Renombrar saldo_caja a saldo_efectivo en agencias
-- (Solo si aún no se ha hecho)
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

-- 4. Agregar columna id_agencia a préstamos (si no existe)
-- Verificar primero si existe antes de ejecutar
-- Si ya existe, comentar estas líneas
ALTER TABLE prestamos 
ADD COLUMN IF NOT EXISTS id_agencia INT NULL AFTER cliente_id;

ALTER TABLE prestamos 
ADD FOREIGN KEY IF NOT EXISTS (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL;

ALTER TABLE prestamos 
ADD INDEX IF NOT EXISTS idx_agencia (id_agencia);

-- 5. Agregar columna id_agencia a clientes (si no existe)
-- Verificar primero si existe antes de ejecutar
-- Si ya existe, comentar estas líneas
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS id_agencia INT NULL AFTER cobrador_id;

ALTER TABLE clientes 
ADD FOREIGN KEY IF NOT EXISTS (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL;

ALTER TABLE clientes 
ADD INDEX IF NOT EXISTS idx_agencia_cliente (id_agencia);

-- Verificar los cambios
SELECT 'Verificando estructura de agencias:' as mensaje;
DESCRIBE agencias;

SELECT 'Verificando estructura de prestamos:' as mensaje;
DESCRIBE prestamos;

SELECT 'Verificando estructura de clientes:' as mensaje;
DESCRIBE clientes;

SELECT 'Verificando tabla ingresos_bancos_agencia:' as mensaje;
DESCRIBE ingresos_bancos_agencia;

SELECT '✓ Configuración completada' as resultado;
