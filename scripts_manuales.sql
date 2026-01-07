-- ============================================
-- Scripts SQL Útiles para Configuración Manual
-- Ejecutar estos comandos según sea necesario
-- ============================================

USE sistema_financiera;

-- ============================================
-- 1. ASIGNAR AGENCIAS A CLIENTES EXISTENTES
-- ============================================

-- Ver clientes sin agencia asignada
SELECT id, nombre_completo, cobrador_id, id_agencia 
FROM clientes 
WHERE id_agencia IS NULL 
LIMIT 10;

-- Opción A: Asignar todos los clientes a Sede Central (id_agencia = 1)
-- DESCOMENTAR PARA EJECUTAR:
-- UPDATE clientes 
-- SET id_agencia = 1 
-- WHERE id_agencia IS NULL;

-- Opción B: Asignar clientes basado en la agencia de su cobrador
-- DESCOMENTAR PARA EJECUTAR:
-- UPDATE clientes c
-- INNER JOIN colaboradores col ON c.cobrador_id = col.id_colaborador
-- SET c.id_agencia = col.id_agencia
-- WHERE c.id_agencia IS NULL AND col.id_agencia IS NOT NULL;

-- ============================================
-- 2. ASIGNAR AGENCIAS A PRÉSTAMOS EXISTENTES
-- ============================================

-- Ver préstamos sin agencia asignada
SELECT id, numero_prestamo, cliente_id, id_agencia 
FROM prestamos 
WHERE id_agencia IS NULL 
LIMIT 10;

-- Opción A: Asignar basado en la agencia del cliente
-- DESCOMENTAR PARA EJECUTAR:
-- UPDATE prestamos p
-- INNER JOIN clientes c ON p.cliente_id = c.id
-- SET p.id_agencia = c.id_agencia
-- WHERE p.id_agencia IS NULL AND c.id_agencia IS NOT NULL;

-- Opción B: Asignar todos a Sede Central
-- DESCOMENTAR PARA EJECUTAR:
-- UPDATE prestamos 
-- SET id_agencia = 1 
-- WHERE id_agencia IS NULL;

-- ============================================
-- 3. VERIFICAR USUARIOS Y COLABORADORES
-- ============================================

-- Ver usuarios sin agencia asignada
SELECT 
    u.id_usuario, 
    u.nombre_usuario, 
    c.nombre_completo, 
    c.id_agencia,
    a.nombre_agencia
FROM usuarios u
LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
ORDER BY c.id_agencia IS NULL DESC;

-- Asignar agencia a un colaborador específico
-- DESCOMENTAR Y AJUSTAR IDs:
-- UPDATE colaboradores 
-- SET id_agencia = 1  -- ID de la agencia
-- WHERE id_colaborador = X;  -- ID del colaborador

-- ============================================
-- 4. GESTIÓN DE BANCOS
-- ============================================

-- Ver todos los bancos
SELECT id, nombre_banco, numero_cuenta, tipo_cuenta, saldo_actual, estado 
FROM bancos;

-- Insertar un nuevo banco (ejemplo)
-- DESCOMENTAR Y AJUSTAR DATOS:
-- INSERT INTO bancos (nombre_banco, numero_cuenta, tipo_cuenta, saldo_actual, estado)
-- VALUES ('Banco Atlántida', '1010985151512', 'Ahorro', 10000000.00, 'activo');

-- Actualizar saldo de un banco
-- DESCOMENTAR Y AJUSTAR:
-- UPDATE bancos 
-- SET saldo_actual = 10000000.00 
-- WHERE id = 1;

-- ============================================
-- 5. GESTIÓN DE AGENCIAS
-- ============================================

-- Ver todas las agencias con su saldo de bóveda
SELECT 
    id_agencia, 
    nombre_agencia, 
    ciudad, 
    saldo_efectivo, 
    estado 
FROM agencias;

-- Actualizar saldo de bóveda de una agencia (solo para pruebas iniciales)
-- NOTA: Normalmente esto se hace a través del módulo de bóveda
-- DESCOMENTAR Y AJUSTAR:
-- UPDATE agencias 
-- SET saldo_efectivo = 0.00 
-- WHERE id_agencia = 1;

-- ============================================
-- 6. CREAR PRÉSTAMOS DE PRUEBA EN ESTADO 'APROBADO'
-- ============================================

-- Ver estados disponibles
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'prestamos' AND COLUMN_NAME = 'estado';

-- Crear un préstamo de prueba en estado 'aprobado'
-- NOTA: Necesitas tener un cliente creado primero
-- DESCOMENTAR Y AJUSTAR:
-- INSERT INTO prestamos (
--     cliente_id, 
--     id_agencia,
--     numero_prestamo, 
--     monto_prestado, 
--     tasa_interes, 
--     periodo_meses, 
--     monto_total, 
--     monto_cuota, 
--     fecha_desembolso, 
--     estado
-- ) VALUES (
--     1,                          -- ID del cliente
--     1,                          -- ID de la agencia
--     'PREST-20260106-TEST1',     -- Número de préstamo
--     5000.00,                    -- Monto prestado
--     15.00,                      -- Tasa de interés
--     12,                         -- Periodo en meses
--     5750.00,                    -- Monto total (con interés)
--     479.17,                     -- Monto de cuota mensual
--     CURDATE(),                  -- Fecha de desembolso
--     'aprobado'                  -- Estado
-- );

-- ============================================
-- 7. VERIFICACIÓN FINAL
-- ============================================

-- Resumen general del sistema
SELECT 
    'Agencias' as tabla,
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Activa' THEN 1 ELSE 0 END) as activos
FROM agencias
UNION ALL
SELECT 
    'Bancos',
    COUNT(*),
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END)
FROM bancos
UNION ALL
SELECT 
    'Clientes',
    COUNT(*),
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END)
FROM clientes
UNION ALL
SELECT 
    'Préstamos',
    COUNT(*),
    SUM(CASE WHEN estado IN ('aprobado', 'activo') THEN 1 ELSE 0 END)
FROM prestamos
UNION ALL
SELECT 
    'Usuarios',
    COUNT(*),
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END)
FROM usuarios;

-- Verificar clientes sin agencia
SELECT 
    'Clientes sin agencia' as verificacion,
    COUNT(*) as cantidad
FROM clientes 
WHERE id_agencia IS NULL;

-- Verificar préstamos sin agencia
SELECT 
    'Préstamos sin agencia' as verificacion,
    COUNT(*) as cantidad
FROM prestamos 
WHERE id_agencia IS NULL;

-- Verificar colaboradores sin agencia
SELECT 
    'Colaboradores sin agencia' as verificacion,
    COUNT(*) as cantidad
FROM colaboradores 
WHERE id_agencia IS NULL;

-- Ver saldos de bóvedas por agencia
SELECT 
    a.nombre_agencia,
    a.ciudad,
    a.saldo_efectivo,
    COUNT(DISTINCT c.id) as total_clientes,
    COUNT(DISTINCT p.id) as total_prestamos
FROM agencias a
LEFT JOIN clientes c ON a.id_agencia = c.id_agencia
LEFT JOIN prestamos p ON a.id_agencia = p.id_agencia
GROUP BY a.id_agencia
ORDER BY a.nombre_agencia;

-- Ver préstamos aprobados pendientes de desembolso
SELECT 
    p.numero_prestamo,
    c.nombre_completo as cliente,
    a.nombre_agencia as agencia,
    p.monto_prestado,
    p.created_at as fecha_aprobacion
FROM prestamos p
INNER JOIN clientes c ON p.cliente_id = c.id
LEFT JOIN agencias a ON p.id_agencia = a.id_agencia
WHERE p.estado = 'aprobado'
ORDER BY p.created_at DESC;

SELECT '✓ Verificación completada' as resultado;
