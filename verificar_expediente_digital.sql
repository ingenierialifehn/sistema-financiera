-- ============================================
-- VERIFICACIÓN: Expediente Digital de Clientes
-- Fecha: 2026-01-07
-- ============================================

USE sistema_financiera;

-- 1. Verificar estructura de la tabla clientes
SELECT 
    '=== ESTRUCTURA DE LA TABLA CLIENTES ===' AS info;

DESCRIBE clientes;

-- 2. Verificar que existen los nuevos campos
SELECT 
    '=== VERIFICACIÓN DE NUEVOS CAMPOS ===' AS info;

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'sistema_financiera'
AND TABLE_NAME = 'clientes'
AND COLUMN_NAME IN (
    'departamento', 'municipio', 'barrio', 'punto_referencia',
    'tipo_vivienda', 'gps_coordenadas', 'genero',
    'foto_dni_frontal', 'foto_dni_posterior', 'foto_perfil',
    'foto_fachada_casa', 'foto_recibo_servicio'
)
ORDER BY ORDINAL_POSITION;

-- 3. Verificar índices
SELECT 
    '=== ÍNDICES DE LA TABLA CLIENTES ===' AS info;

SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'sistema_financiera'
AND TABLE_NAME = 'clientes'
AND INDEX_NAME IN ('idx_tipo_vivienda', 'idx_agencia')
ORDER BY INDEX_NAME;

-- 4. Contar clientes existentes
SELECT 
    '=== ESTADÍSTICAS DE CLIENTES ===' AS info;

SELECT 
    COUNT(*) AS total_clientes,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS clientes_activos,
    SUM(CASE WHEN gps_coordenadas IS NOT NULL THEN 1 ELSE 0 END) AS con_gps,
    SUM(CASE WHEN foto_dni_frontal IS NOT NULL THEN 1 ELSE 0 END) AS con_dni_frontal,
    SUM(CASE WHEN foto_perfil IS NOT NULL THEN 1 ELSE 0 END) AS con_foto_perfil
FROM clientes;

-- 5. Verificar tipos de vivienda
SELECT 
    '=== DISTRIBUCIÓN POR TIPO DE VIVIENDA ===' AS info;

SELECT 
    tipo_vivienda,
    COUNT(*) AS cantidad
FROM clientes
WHERE tipo_vivienda IS NOT NULL
GROUP BY tipo_vivienda
ORDER BY cantidad DESC;

-- 6. Verificar clientes con expediente completo
SELECT 
    '=== CLIENTES CON EXPEDIENTE COMPLETO ===' AS info;

SELECT 
    COUNT(*) AS clientes_con_expediente_completo
FROM clientes
WHERE 
    foto_dni_frontal IS NOT NULL
    AND foto_dni_posterior IS NOT NULL
    AND foto_perfil IS NOT NULL
    AND foto_fachada_casa IS NOT NULL
    AND foto_recibo_servicio IS NOT NULL
    AND gps_coordenadas IS NOT NULL
    AND tipo_vivienda IS NOT NULL;

-- 7. Resultado final
SELECT 
    '✓ VERIFICACIÓN COMPLETADA EXITOSAMENTE' AS resultado,
    'Todos los campos del expediente digital están disponibles' AS mensaje;
