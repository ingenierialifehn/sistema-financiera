-- ============================================
-- Script de Verificación y Corrección Rápida
-- Ejecutar este script para verificar y corregir todo
-- ============================================

USE sistema_financiera;

-- ============================================
-- 1. VERIFICAR USUARIOS Y AGENCIAS
-- ============================================
SELECT '=== USUARIOS Y AGENCIAS ===' as seccion;

SELECT 
    u.id_usuario,
    u.username,
    r.nombre_rol,
    c.nombre_completo,
    c.id_agencia,
    a.nombre_agencia,
    CASE 
        WHEN c.id_agencia IS NULL THEN '❌ SIN AGENCIA'
        ELSE '✓ OK'
    END as estado_agencia
FROM usuarios u
LEFT JOIN roles r ON u.id_rol = r.id_rol
LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
WHERE u.estado = 'Activo'
ORDER BY u.id_usuario;

-- ============================================
-- 2. VERIFICAR PERMISOS DE ROLES
-- ============================================
SELECT '=== PERMISOS DE ROLES ===' as seccion;

SELECT 
    nombre_rol,
    JSON_EXTRACT(permisos, '$.boveda') as permisos_boveda,
    JSON_EXTRACT(permisos, '$.operaciones') as permisos_operaciones,
    CASE 
        WHEN JSON_EXTRACT(permisos, '$.boveda') IS NOT NULL 
         AND JSON_EXTRACT(permisos, '$.operaciones') IS NOT NULL 
        THEN '✓ OK'
        ELSE '❌ FALTA CONFIGURAR'
    END as estado
FROM roles
WHERE estado = 'Activo'
ORDER BY nombre_rol;

-- ============================================
-- 3. VERIFICAR BANCOS
-- ============================================
SELECT '=== BANCOS DISPONIBLES ===' as seccion;

SELECT 
    id,
    nombre_banco,
    numero_cuenta,
    tipo_cuenta,
    saldo_actual,
    estado,
    CASE 
        WHEN saldo_actual <= 0 THEN '⚠️ SIN FONDOS'
        ELSE '✓ OK'
    END as estado_saldo
FROM bancos
WHERE estado = 'activo'
ORDER BY id;

-- ============================================
-- 4. VERIFICAR AGENCIAS
-- ============================================
SELECT '=== AGENCIAS ===' as seccion;

SELECT 
    id_agencia,
    nombre_agencia,
    ciudad,
    saldo_efectivo,
    estado
FROM agencias
WHERE estado = 'Activa'
ORDER BY id_agencia;

-- ============================================
-- 5. ASIGNAR AGENCIA A USUARIOS SIN AGENCIA
-- (DESCOMENTAR PARA EJECUTAR)
-- ============================================
-- Asignar Sede Central (id_agencia = 1) a todos los colaboradores sin agencia
-- UPDATE colaboradores 
-- SET id_agencia = 1 
-- WHERE id_agencia IS NULL;

-- ============================================
-- 6. ACTUALIZAR SALDO DE BANCOS SI ESTÁN EN 0
-- (DESCOMENTAR PARA EJECUTAR)
-- ============================================
-- UPDATE bancos 
-- SET saldo_actual = 100000.00 
-- WHERE estado = 'activo' AND saldo_actual = 0;

-- ============================================
-- 7. VERIFICAR HISTORIAL DE INGRESOS
-- ============================================
SELECT '=== HISTORIAL DE INGRESOS ===' as seccion;

SELECT 
    i.id,
    i.fecha_hora,
    b.nombre_banco,
    a.nombre_agencia,
    i.monto,
    i.referencia,
    u.username as realizado_por
FROM ingresos_bancos_agencia i
INNER JOIN bancos b ON i.banco_id = b.id
INNER JOIN agencias a ON i.agencia_id = a.id_agencia
INNER JOIN usuarios u ON i.realizado_por = u.id_usuario
ORDER BY i.fecha_hora DESC
LIMIT 10;

-- ============================================
-- RESUMEN FINAL
-- ============================================
SELECT '=== RESUMEN ===' as seccion;

SELECT 
    'Usuarios activos' as item,
    COUNT(*) as cantidad
FROM usuarios 
WHERE estado = 'Activo'
UNION ALL
SELECT 
    'Usuarios sin agencia',
    COUNT(*)
FROM usuarios u
LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
WHERE u.estado = 'Activo' AND c.id_agencia IS NULL
UNION ALL
SELECT 
    'Bancos activos',
    COUNT(*)
FROM bancos 
WHERE estado = 'activo'
UNION ALL
SELECT 
    'Bancos sin fondos',
    COUNT(*)
FROM bancos 
WHERE estado = 'activo' AND saldo_actual <= 0
UNION ALL
SELECT 
    'Agencias activas',
    COUNT(*)
FROM agencias 
WHERE estado = 'Activa'
UNION ALL
SELECT 
    'Total ingresos registrados',
    COUNT(*)
FROM ingresos_bancos_agencia;

SELECT '✓ Verificación completada' as resultado;
