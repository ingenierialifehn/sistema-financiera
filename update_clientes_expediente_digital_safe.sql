-- ============================================
-- ACTUALIZACIÓN: Módulo de Clientes - Expediente Digital
-- Fecha: 2026-01-07
-- Descripción: Agrega campos para expediente digital completo (versión segura)
-- ============================================

USE sistema_financiera;

-- Procedimiento para agregar columnas solo si no existen
DELIMITER $$

DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(100),
    IN columnName VARCHAR(100),
    IN columnDefinition VARCHAR(500)
)
BEGIN
    DECLARE columnExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO columnExists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'sistema_financiera'
    AND TABLE_NAME = tableName
    AND COLUMN_NAME = columnName;
    
    IF columnExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', columnName, ' ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('✓ Columna ', columnName, ' agregada exitosamente') AS resultado;
    ELSE
        SELECT CONCAT('⚠ Columna ', columnName, ' ya existe, omitiendo...') AS resultado;
    END IF;
END$$

DELIMITER ;

-- Agregar campos de ubicación detallada
CALL AddColumnIfNotExists('clientes', 'departamento', 'VARCHAR(100) NULL COMMENT "Departamento de residencia" AFTER direccion');
CALL AddColumnIfNotExists('clientes', 'municipio', 'VARCHAR(100) NULL COMMENT "Municipio de residencia" AFTER departamento');
CALL AddColumnIfNotExists('clientes', 'barrio', 'VARCHAR(100) NULL COMMENT "Barrio o colonia de residencia" AFTER municipio');
CALL AddColumnIfNotExists('clientes', 'punto_referencia', 'VARCHAR(255) NULL COMMENT "Punto de referencia para ubicar la vivienda" AFTER barrio');

-- Agregar campos de vivienda y GPS
CALL AddColumnIfNotExists('clientes', 'tipo_vivienda', 'ENUM("Propia", "Alquilada", "Familiar", "Pagándola") NULL COMMENT "Tipo de vivienda del cliente" AFTER punto_referencia');
CALL AddColumnIfNotExists('clientes', 'gps_coordenadas', 'VARCHAR(100) NULL COMMENT "Coordenadas GPS (latitud,longitud)" AFTER tipo_vivienda');

-- Agregar campo de género
CALL AddColumnIfNotExists('clientes', 'genero', 'ENUM("M", "F") NULL COMMENT "Género del cliente" AFTER fecha_nacimiento');

-- Agregar campos de fotos del expediente digital
CALL AddColumnIfNotExists('clientes', 'foto_dni_frontal', 'VARCHAR(255) NULL COMMENT "Ruta de la foto del DNI frontal" AFTER foto_documento');
CALL AddColumnIfNotExists('clientes', 'foto_dni_posterior', 'VARCHAR(255) NULL COMMENT "Ruta de la foto del DNI posterior" AFTER foto_dni_frontal');
CALL AddColumnIfNotExists('clientes', 'foto_perfil', 'VARCHAR(255) NULL COMMENT "Ruta de la foto de perfil del cliente" AFTER foto_dni_posterior');
CALL AddColumnIfNotExists('clientes', 'foto_fachada_casa', 'VARCHAR(255) NULL COMMENT "Ruta de la foto de la fachada de la casa" AFTER foto_perfil');
CALL AddColumnIfNotExists('clientes', 'foto_recibo_servicio', 'VARCHAR(255) NULL COMMENT "Ruta de la foto del recibo de servicio" AFTER foto_fachada_casa');

-- Agregar campo de agencia
CALL AddColumnIfNotExists('clientes', 'id_agencia', 'INT NULL COMMENT "Agencia a la que pertenece el cliente" AFTER cobrador_id');

-- Agregar índices si no existen
SET @indexExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                    WHERE TABLE_SCHEMA = 'sistema_financiera' 
                    AND TABLE_NAME = 'clientes' 
                    AND INDEX_NAME = 'idx_tipo_vivienda');

SET @sql = IF(@indexExists = 0,
    'ALTER TABLE clientes ADD INDEX idx_tipo_vivienda (tipo_vivienda)',
    'SELECT "⚠ Índice idx_tipo_vivienda ya existe" AS resultado');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexExists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                    WHERE TABLE_SCHEMA = 'sistema_financiera' 
                    AND TABLE_NAME = 'clientes' 
                    AND INDEX_NAME = 'idx_agencia');

SET @sql = IF(@indexExists = 0,
    'ALTER TABLE clientes ADD INDEX idx_agencia (id_agencia)',
    'SELECT "⚠ Índice idx_agencia ya existe" AS resultado');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpiar procedimiento temporal
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Verificar la estructura actualizada
DESCRIBE clientes;

SELECT '✓ Tabla clientes actualizada exitosamente con campos de expediente digital' AS resultado;
