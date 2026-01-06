<?php
/**
 * Reporte de Ingresos
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
    $agruparPor = isset($_GET['agrupar_por']) ? $_GET['agrupar_por'] : 'dia'; // dia, semana, mes
    
    // Query para ingresos por período
    $sql = "
        SELECT 
            DATE(p.fecha_pago) as fecha,
            COUNT(*) as total_cobros,
            COALESCE(SUM(p.monto_pagado), 0) as total_monto,
            COALESCE(SUM(p.monto_mora), 0) as total_mora,
            COALESCE(SUM(CASE WHEN p.estado = 'confirmado' THEN p.monto_pagado ELSE 0 END), 0) as monto_confirmado
        FROM pagos p
        WHERE DATE(p.fecha_pago) BETWEEN :fecha_desde AND :fecha_hasta
        AND p.estado = 'confirmado'
        GROUP BY DATE(p.fecha_pago)
        ORDER BY fecha ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta
    ]);
    $ingresosDiarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar según parámetro
    $ingresosAgrupados = [];
    foreach ($ingresosDiarios as $ingreso) {
        $fecha = new DateTime($ingreso['fecha']);
        $key = '';
        
        switch ($agruparPor) {
            case 'semana':
                $key = $fecha->format('Y-W'); // Año-Semana
                break;
            case 'mes':
                $key = $fecha->format('Y-m'); // Año-Mes
                break;
            default: // dia
                $key = $ingreso['fecha'];
        }
        
        if (!isset($ingresosAgrupados[$key])) {
            $ingresosAgrupados[$key] = [
                'periodo' => $key,
                'fecha_inicio' => $ingreso['fecha'],
                'fecha_fin' => $ingreso['fecha'],
                'total_cobros' => 0,
                'total_monto' => 0,
                'total_mora' => 0,
                'monto_confirmado' => 0
            ];
        }
        
        $ingresosAgrupados[$key]['total_cobros'] += $ingreso['total_cobros'];
        $ingresosAgrupados[$key]['total_monto'] += $ingreso['total_monto'];
        $ingresosAgrupados[$key]['total_mora'] += $ingreso['total_mora'];
        $ingresosAgrupados[$key]['monto_confirmado'] += $ingreso['monto_confirmado'];
        
        if ($ingreso['fecha'] < $ingresosAgrupados[$key]['fecha_inicio']) {
            $ingresosAgrupados[$key]['fecha_inicio'] = $ingreso['fecha'];
        }
        if ($ingreso['fecha'] > $ingresosAgrupados[$key]['fecha_fin']) {
            $ingresosAgrupados[$key]['fecha_fin'] = $ingreso['fecha'];
        }
    }
    
    $ingresosAgrupados = array_values($ingresosAgrupados);
    
    // Estadísticas generales
    $totalIngresos = array_sum(array_column($ingresosAgrupados, 'monto_confirmado'));
    $totalMora = array_sum(array_column($ingresosAgrupados, 'total_mora'));
    $totalCobros = array_sum(array_column($ingresosAgrupados, 'total_cobros'));
    $promedioDiario = count($ingresosAgrupados) > 0 ? $totalIngresos / count($ingresosAgrupados) : 0;
    
    Response::success([
        'ingresos' => $ingresosAgrupados,
        'estadisticas' => [
            'total_ingresos' => (float)$totalIngresos,
            'total_mora' => (float)$totalMora,
            'total_cobros' => (int)$totalCobros,
            'promedio_diario' => (float)$promedioDiario,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'agrupar_por' => $agruparPor
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reporte de ingresos: " . $e->getMessage());
    Response::serverError('Error al generar reporte de ingresos');
}

