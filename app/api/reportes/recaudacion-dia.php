<?php
/**
 * API: Recaudación del Día
 * GET /api/reportes/recaudacion-dia.php
 * Filtra automáticamente por agencia del usuario
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

// Verificar autenticación
$user = Auth::requireAuth();

// Obtener parámetros
$idAgencia = $_GET['id_agencia'] ?? null;

// Si no es administrador, forzar su agencia
$rolNombre = $user['rol_nombre'] ?? '';
$esAdministrador = in_array($rolNombre, ['Administrador', 'Gerente']);

if (!$esAdministrador) {
    $idAgencia = $user['id_agencia'];
}

if (!$idAgencia) {
    Response::error('ID de agencia es requerido', 400);
}

try {
    $db = getDB();

    // Obtener fecha actual
    $fechaHoy = date('Y-m-d');

    // Consulta para obtener cobros del día
    // Nota: Los pagos están en la tabla 'pagos' relacionados con cuotas
    // Cada pago tiene un desglose del 11% (4% interés, 4% gastos, 3% comisión)

    $sql = "
        SELECT 
            p.id as id_pago,
            p.monto_pagado,
            p.fecha_pago,
            TIME(p.created_at) as hora_pago,
            cl.nombre_completo as cliente,
            pr.id as id_prestamo,
            pr.monto_prestado as monto_capital,
            cu.capital_cuota,
            cu.interes_cuota,
            cu.gastos_cuota,
            cu.comision_cuota,
            -- Calcular desglose basado en el esquema 11%
            -- Del monto pagado, el 89% es capital y el 11% son intereses/gastos/comisiones
            (p.monto_pagado * 0.89) as capital_pagado,
            (p.monto_pagado * 0.04) as interes_4,
            (p.monto_pagado * 0.04) as gastos_4,
            (p.monto_pagado * 0.03) as comision_3
        FROM pagos p
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        INNER JOIN prestamos pr ON p.prestamo_id = pr.id
        INNER JOIN clientes cl ON p.cliente_id = cl.id
        WHERE DATE(p.fecha_pago) = :fecha
        AND cl.id_agencia = :id_agencia
        AND p.estado = 'confirmado'
        ORDER BY p.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'fecha' => $fechaHoy,
        'id_agencia' => $idAgencia
    ]);

    $cobros = $stmt->fetchAll();

    // Calcular resumen
    $totalRecaudado = 0;
    $capitalTotal = 0;
    $interesTotal = 0;
    $gastosTotal = 0;
    $comisionTotal = 0;

    $cobrosFormateados = [];

    foreach ($cobros as $cobro) {
        $totalRecaudado += $cobro['monto_pagado'];
        $capitalTotal += $cobro['capital_pagado'];
        $interesTotal += $cobro['interes_4'];
        $gastosTotal += $cobro['gastos_4'];
        $comisionTotal += $cobro['comision_3'];

        $cobrosFormateados[] = [
            'cliente' => $cobro['cliente'],
            'capital' => round($cobro['capital_pagado'], 2),
            'interes' => round($cobro['interes_4'], 2),
            'gastos_comision' => round($cobro['gastos_4'] + $cobro['comision_3'], 2),
            'total' => round($cobro['monto_pagado'], 2),
            'hora' => date('h:i A', strtotime($cobro['hora_pago']))
        ];
    }

    Response::success([
        'resumen' => [
            'total_recaudado' => round($totalRecaudado, 2),
            'capital_total' => round($capitalTotal, 2),
            'interes_total' => round($interesTotal, 2),
            'gastos_total' => round($gastosTotal, 2),
            'comision_total' => round($comisionTotal, 2),
            'gastos_comision_total' => round($gastosTotal + $comisionTotal, 2)
        ],
        'cobros' => $cobrosFormateados,
        'fecha' => $fechaHoy
    ]);

} catch (Exception $e) {
    error_log("Error en recaudacion-dia.php: " . $e->getMessage());
    Response::error('Error al obtener recaudación del día: ' . $e->getMessage(), 500);
}
