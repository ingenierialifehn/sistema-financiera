<?php
/**
 * API: Obtener préstamos de un cliente
 * GET /app/api/clientes/prestamos/list.php?cliente_id=1
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();

    if (!isset($_GET['cliente_id']) || empty($_GET['cliente_id'])) {
        Response::error('ID de cliente es requerido', 400);
    }

    $clienteId = intval($_GET['cliente_id']);
    $db = getDB();

    // Obtener préstamos del cliente con información completa
    $sql = "
        SELECT 
            p.id,
            p.monto_capital,
            p.neto_entregar,
            p.modalidad,
            p.plazo_meses,
            p.tasa_total,
            p.tasa_interes,
            p.tasa_gastos,
            p.tasa_comision,
            p.valor_cuota,
            p.total_a_pagar,
            p.estado,
            p.fecha_solicitud,
            p.fecha_desembolso,
            p.created_at,
            
            -- Calcular cuotas
            (SELECT COUNT(*) FROM cuotas WHERE prestamo_id = p.id) as total_cuotas,
            (SELECT COUNT(*) FROM cuotas WHERE prestamo_id = p.id AND estado = 'pagada') as cuotas_pagadas,
            (SELECT COUNT(*) FROM cuotas WHERE prestamo_id = p.id AND estado IN ('pendiente', 'parcial')) as cuotas_pendientes,
            (SELECT SUM(monto_pagado) FROM cuotas WHERE prestamo_id = p.id) as total_pagado,
            
            -- Calcular capital restante
            (p.monto_capital - IFNULL((SELECT SUM(monto_pagado * (capital_cuota / monto_cuota)) 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pagada', 'parcial')
                AND monto_cuota > 0), 0)) as capital_restante,
            
            -- Calcular balance (total a pagar - total pagado)
            (p.total_a_pagar - IFNULL((SELECT SUM(monto_pagado) FROM cuotas WHERE prestamo_id = p.id), 0)) as balance_pendiente,
            
            -- Días en mora (de la cuota más atrasada)
            (SELECT MAX(DATEDIFF(CURDATE(), fecha_vencimiento))
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pendiente', 'parcial')
                AND fecha_vencimiento < CURDATE()) as dias_mora,
            
            -- Monto total vencido
            (SELECT SUM(monto_cuota - IFNULL(monto_pagado, 0))
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pendiente', 'parcial')
                AND fecha_vencimiento < CURDATE()) as monto_mora_total,
            
            -- Próxima cuota
            (SELECT MIN(fecha_vencimiento) 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pendiente', 'parcial')) as proxima_cuota_fecha,
            
            (SELECT monto_cuota 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pendiente', 'parcial')
                ORDER BY fecha_vencimiento ASC 
                LIMIT 1) as proxima_cuota_monto
            
        FROM prestamos p
        WHERE p.id_cliente = :cliente_id
        ORDER BY p.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['cliente_id' => $clienteId]);
    $prestamos = $stmt->fetchAll();

    // Formatear datos
    foreach ($prestamos as &$prestamo) {
        $prestamo['monto_capital'] = floatval($prestamo['monto_capital']);
        $prestamo['neto_entregar'] = floatval($prestamo['neto_entregar']);
        $prestamo['total_a_pagar'] = floatval($prestamo['total_a_pagar']);
        $prestamo['total_pagado'] = floatval($prestamo['total_pagado'] ?? 0);
        $prestamo['capital_restante'] = floatval($prestamo['capital_restante'] ?? 0);
        $prestamo['balance_pendiente'] = floatval($prestamo['balance_pendiente'] ?? 0);
        $prestamo['dias_mora'] = intval($prestamo['dias_mora'] ?? 0);
        $prestamo['monto_mora_total'] = floatval($prestamo['monto_mora_total'] ?? 0);
        $prestamo['proxima_cuota_monto'] = floatval($prestamo['proxima_cuota_monto'] ?? 0);

        // Calcular progreso
        $prestamo['progreso'] = $prestamo['total_cuotas'] > 0
            ? round(($prestamo['cuotas_pagadas'] / $prestamo['total_cuotas']) * 100, 2)
            : 0;
    }

    Response::success($prestamos, 'Préstamos obtenidos exitosamente');

} catch (Exception $e) {
    error_log("Error en clientes/prestamos/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener préstamos: ' . $e->getMessage());
}
