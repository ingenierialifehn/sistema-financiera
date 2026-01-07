-- ============================================
-- Agregar columnas id_agencia faltantes
-- ============================================

USE sistema_financiera;

-- Agregar id_agencia a prestamos
ALTER TABLE prestamos 
ADD COLUMN id_agencia INT NULL AFTER cliente_id;

ALTER TABLE prestamos 
ADD INDEX idx_agencia (id_agencia);

ALTER TABLE prestamos 
ADD CONSTRAINT fk_prestamos_agencia 
FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL;

-- Agregar id_agencia a clientes
ALTER TABLE clientes 
ADD COLUMN id_agencia INT NULL;

ALTER TABLE clientes 
ADD INDEX idx_agencia_cliente (id_agencia);

ALTER TABLE clientes 
ADD CONSTRAINT fk_clientes_agencia 
FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL;

SELECT '✓ Columnas id_agencia agregadas correctamente' as resultado;
