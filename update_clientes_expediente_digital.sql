-- ============================================
-- ACTUALIZACIÓN: Módulo de Clientes - Expediente Digital
-- Fecha: 2026-01-07
-- Descripción: Agrega campos para expediente digital completo
-- ============================================

USE sistema_financiera;

-- Agregar nuevos campos a la tabla clientes (solo si no existen)
-- Nota: Si algún campo ya existe, el script mostrará un error pero continuará

-- Agregar campos de ubicación detallada
ALTER TABLE clientes
ADD COLUMN departamento VARCHAR(100) NULL COMMENT 'Departamento de residencia' AFTER direccion;

ALTER TABLE clientes
ADD COLUMN municipio VARCHAR(100) NULL COMMENT 'Municipio de residencia' AFTER departamento;

ALTER TABLE clientes
ADD COLUMN barrio VARCHAR(100) NULL COMMENT 'Barrio o colonia de residencia' AFTER municipio;

ALTER TABLE clientes
ADD COLUMN punto_referencia VARCHAR(255) NULL COMMENT 'Punto de referencia para ubicar la vivienda' AFTER barrio;

-- Agregar campos de vivienda y GPS
ALTER TABLE clientes
ADD COLUMN tipo_vivienda ENUM('Propia', 'Alquilada', 'Familiar', 'Pagándola') NULL COMMENT 'Tipo de vivienda del cliente' AFTER punto_referencia;

ALTER TABLE clientes
ADD COLUMN gps_coordenadas VARCHAR(100) NULL COMMENT 'Coordenadas GPS (latitud,longitud)' AFTER tipo_vivienda;

-- Agregar campo de género
ALTER TABLE clientes
ADD COLUMN genero ENUM('M', 'F') NULL COMMENT 'Género del cliente' AFTER fecha_nacimiento;

-- Agregar campos de fotos del expediente digital
ALTER TABLE clientes
ADD COLUMN foto_dni_frontal VARCHAR(255) NULL COMMENT 'Ruta de la foto del DNI frontal' AFTER foto_documento;

ALTER TABLE clientes
ADD COLUMN foto_dni_posterior VARCHAR(255) NULL COMMENT 'Ruta de la foto del DNI posterior' AFTER foto_dni_frontal;

ALTER TABLE clientes
ADD COLUMN foto_perfil VARCHAR(255) NULL COMMENT 'Ruta de la foto de perfil del cliente' AFTER foto_dni_posterior;

ALTER TABLE clientes
ADD COLUMN foto_fachada_casa VARCHAR(255) NULL COMMENT 'Ruta de la foto de la fachada de la casa' AFTER foto_perfil;

ALTER TABLE clientes
ADD COLUMN foto_recibo_servicio VARCHAR(255) NULL COMMENT 'Ruta de la foto del recibo de servicio' AFTER foto_fachada_casa;

-- Agregar campo de agencia
ALTER TABLE clientes
ADD COLUMN id_agencia INT NULL COMMENT 'Agencia a la que pertenece el cliente' AFTER cobrador_id;

-- Agregar índices para mejorar el rendimiento
ALTER TABLE clientes
ADD INDEX idx_tipo_vivienda (tipo_vivienda);

ALTER TABLE clientes
ADD INDEX idx_agencia (id_agencia);

-- Agregar foreign key para agencia (si la tabla agencias existe)
-- ALTER TABLE clientes
-- ADD CONSTRAINT fk_clientes_agencia FOREIGN KEY (id_agencia) REFERENCES agencias(id) ON DELETE SET NULL;

-- Verificar la estructura actualizada
DESCRIBE clientes;

SELECT 'Tabla clientes actualizada exitosamente con campos de expediente digital' AS resultado;
