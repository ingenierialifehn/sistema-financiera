-- ============================================
-- CONSULTAS SQL PARA REPORTES DE RECAUDACIÓN
-- Con Desglose Detallado de Cuotas
-- ============================================

-- ============================================
-- 1. REPORTE DE RECAUDACIÓN DIARIA
-- ============================================
-- Muestra el desglose completo de lo recaudado en un día específico

SELECT 
    DATE(c.fecha_pago) as fecha_recaudacion,
    COUNT(DISTINCT c.prestamo_id) as total_prestamos,
    COUNT(c.id) as total_cuotas_pagadas,
    SUM(c.monto_cuota) as total_recaudado,
    SUM(c.capital_cuota) as total_capital,
    SUM(c.interes_cuota) as total_interes,
    SUM(c.gastos_cuota) as total_gastos,
    SUM(c.comision_cuota) as total_comision
FROM cuotas c
WHERE c.estado = 'pagada'
AND DATE(c.fecha_pago) = CURDATE()  -- Cambiar por la fecha deseada
GROUP BY DATE(c.fecha_pago);

-- ============================================
-- 2. REPORTE DE RECAUDACIÓN POR RANGO DE FECHAS
-- ============================================

SELECT 
    DATE(c.fecha_pago) as fecha,
    COUNT(c.id) as cuotas_pagadas,
    SUM(c.monto_cuota) as total_dia,
    SUM(c.capital_cuota) as capital_dia,
    SUM(c.interes_cuota) as interes_dia,
    SUM(c.gastos_cuota) as gastos_dia,
    SUM(c.comision_cuota) as comision_dia
FROM cuotas c
WHERE c.estado = 'pagada'
AND DATE(c.fecha_pago) BETWEEN '2026-01-01' AND '2026-01-31'
GROUP BY DATE(c.fecha_pago)
ORDER BY fecha DESC;

-- ============================================
-- 3. DESGLOSE DETALLADO POR PRÉSTAMO
-- ============================================
-- Muestra el calendario de pagos con desglose de un préstamo específico

SELECT 
    c.numero_cuota,
    c.fecha_vencimiento,
    c.monto_cuota,
    c.capital_cuota,
    c.interes_cuota,
    c.gastos_cuota,
    c.comision_cuota,
    c.estado,
    c.fecha_pago,
    c.monto_pagado,
    CASE 
        WHEN c.estado = 'pagada' THEN 'Pagada'
        WHEN c.estado = 'pendiente' AND c.fecha_vencimiento < CURDATE() THEN 'Vencida'
        WHEN c.estado = 'pendiente' THEN 'Pendiente'
        ELSE c.estado
    END as estado_visual
FROM cuotas c
WHERE c.prestamo_id = 1  -- Cambiar por el ID del préstamo
ORDER BY c.numero_cuota;

-- ============================================
-- 4. RESUMEN TOTAL DE RECAUDACIÓN
-- ============================================
-- Totales generales de todo lo recaudado

SELECT 
    COUNT(DISTINCT c.prestamo_id) as total_prestamos_con_pagos,
    COUNT(c.id) as total_cuotas_pagadas,
    SUM(c.monto_cuota) as gran_total_recaudado,
    SUM(c.capital_cuota) as gran_total_capital,
    SUM(c.interes_cuota) as gran_total_interes,
    SUM(c.gastos_cuota) as gran_total_gastos,
    SUM(c.comision_cuota) as gran_total_comision,
    -- Porcentajes
    ROUND((SUM(c.capital_cuota) / SUM(c.monto_cuota)) * 100, 2) as porcentaje_capital,
    ROUND((SUM(c.interes_cuota) / SUM(c.monto_cuota)) * 100, 2) as porcentaje_interes,
    ROUND((SUM(c.gastos_cuota) / SUM(c.monto_cuota)) * 100, 2) as porcentaje_gastos,
    ROUND((SUM(c.comision_cuota) / SUM(c.monto_cuota)) * 100, 2) as porcentaje_comision
FROM cuotas c
WHERE c.estado = 'pagada';

-- ============================================
-- 5. RECAUDACIÓN POR CLIENTE
-- ============================================

SELECT 
    cl.id,
    cl.nombre_completo,
    cl.numero_documento,
    COUNT(DISTINCT c.prestamo_id) as prestamos_activos,
    COUNT(cu.id) as cuotas_pagadas,
    SUM(cu.monto_cuota) as total_pagado,
    SUM(cu.capital_cuota) as capital_pagado,
    SUM(cu.interes_cuota) as interes_pagado,
    SUM(cu.gastos_cuota) as gastos_pagados,
    SUM(cu.comision_cuota) as comision_pagada
FROM clientes cl
INNER JOIN prestamos p ON cl.id = p.id_cliente
INNER JOIN cuotas cu ON p.id = cu.prestamo_id
WHERE cu.estado = 'pagada'
GROUP BY cl.id, cl.nombre_completo, cl.numero_documento
ORDER BY total_pagado DESC;

-- ============================================
-- 6. PROYECCIÓN DE INGRESOS PENDIENTES
-- ============================================
-- Muestra cuánto se espera recaudar de las cuotas pendientes

SELECT 
    'Pendientes' as tipo,
    COUNT(c.id) as cantidad_cuotas,
    SUM(c.monto_cuota) as total_esperado,
    SUM(c.capital_cuota) as capital_esperado,
    SUM(c.interes_cuota) as interes_esperado,
    SUM(c.gastos_cuota) as gastos_esperados,
    SUM(c.comision_cuota) as comision_esperada
FROM cuotas c
WHERE c.estado IN ('pendiente', 'parcial')

UNION ALL

SELECT 
    'Vencidas' as tipo,
    COUNT(c.id) as cantidad_cuotas,
    SUM(c.monto_cuota) as total_esperado,
    SUM(c.capital_cuota) as capital_esperado,
    SUM(c.interes_cuota) as interes_esperado,
    SUM(c.gastos_cuota) as gastos_esperados,
    SUM(c.comision_cuota) as comision_esperada
FROM cuotas c
WHERE c.estado IN ('pendiente', 'parcial')
AND c.fecha_vencimiento < CURDATE();

-- ============================================
-- 7. RECAUDACIÓN POR MODALIDAD DE PRÉSTAMO
-- ============================================

SELECT 
    p.modalidad,
    COUNT(DISTINCT p.id) as total_prestamos,
    COUNT(cu.id) as cuotas_pagadas,
    SUM(cu.monto_cuota) as total_recaudado,
    SUM(cu.capital_cuota) as capital_recaudado,
    SUM(cu.interes_cuota) as interes_recaudado,
    SUM(cu.gastos_cuota) as gastos_recaudados,
    SUM(cu.comision_cuota) as comision_recaudada,
    AVG(cu.monto_cuota) as promedio_cuota
FROM prestamos p
INNER JOIN cuotas cu ON p.id = cu.prestamo_id
WHERE cu.estado = 'pagada'
GROUP BY p.modalidad
ORDER BY total_recaudado DESC;

-- ============================================
-- 8. VERIFICACIÓN DE INTEGRIDAD
-- ============================================
-- Verifica que la suma de componentes coincida con el monto de la cuota

SELECT 
    c.id,
    c.prestamo_id,
    c.numero_cuota,
    c.monto_cuota,
    (c.capital_cuota + c.interes_cuota + c.gastos_cuota + c.comision_cuota) as suma_componentes,
    ABS(c.monto_cuota - (c.capital_cuota + c.interes_cuota + c.gastos_cuota + c.comision_cuota)) as diferencia
FROM cuotas c
WHERE ABS(c.monto_cuota - (c.capital_cuota + c.interes_cuota + c.gastos_cuota + c.comision_cuota)) > 0.01
ORDER BY diferencia DESC;

-- ============================================
-- 9. REPORTE MENSUAL CONSOLIDADO
-- ============================================

SELECT 
    YEAR(c.fecha_pago) as anio,
    MONTH(c.fecha_pago) as mes,
    DATE_FORMAT(c.fecha_pago, '%Y-%m') as periodo,
    COUNT(c.id) as cuotas_pagadas,
    SUM(c.monto_cuota) as total_mes,
    SUM(c.capital_cuota) as capital_mes,
    SUM(c.interes_cuota) as interes_mes,
    SUM(c.gastos_cuota) as gastos_mes,
    SUM(c.comision_cuota) as comision_mes
FROM cuotas c
WHERE c.estado = 'pagada'
GROUP BY YEAR(c.fecha_pago), MONTH(c.fecha_pago), DATE_FORMAT(c.fecha_pago, '%Y-%m')
ORDER BY anio DESC, mes DESC;

-- ============================================
-- 10. TOP 10 MEJORES PAGADORES
-- ============================================

SELECT 
    cl.nombre_completo,
    cl.numero_documento,
    COUNT(cu.id) as cuotas_pagadas_a_tiempo,
    SUM(cu.monto_cuota) as total_pagado,
    SUM(cu.capital_cuota) as capital_pagado,
    SUM(cu.interes_cuota + cu.gastos_cuota + cu.comision_cuota) as intereses_pagados
FROM clientes cl
INNER JOIN prestamos p ON cl.id = p.id_cliente
INNER JOIN cuotas cu ON p.id = cu.prestamo_id
WHERE cu.estado = 'pagada'
AND cu.fecha_pago <= cu.fecha_vencimiento  -- Pagadas a tiempo
GROUP BY cl.id, cl.nombre_completo, cl.numero_documento
ORDER BY total_pagado DESC
LIMIT 10;
