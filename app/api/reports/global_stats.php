<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
session_start();
header('Content-Type: application/json');

try {
    Auth::checkSession();
    $user = Auth::getCurrentUser();

    // Optional: Restrict to Admins/Gerentes
    // if (!in_array($user['rol_nombre'], ['Administrador', 'Gerente General', 'Gerente'])) {
    //    throw new Exception("Acceso denegado");
    // }

    $db = getDB();
    $agenciaId = $_GET['agencia_id'] ?? 'todas';

    $params = [];
    $whereClause = "";

    if ($agenciaId !== 'todas') {
        $whereClause = " AND c.id_agencia = ?";
        $params[] = $agenciaId;
    }

    // 1. Capital en la Calle (Cartera Activa - Capital Pagado)
    // y Mora Total (B-E) = Saldo Capital de préstamos con atraso > 0 días (Categoria B empieza en 1 día segun logica simple, o 1-30. El prompt dice B a E. ClienteHelper dice A=0dias. B=1-30. Entonces Atraso > 0).

    // Nota: Usamos una subquery para 'pagos' para evitar cartesiano si hay multiples pagos (aunque aqui consultamos cuotas, asi que sumamos lo pagado en cuotas).

    $sqlStats = "
        SELECT 
            -- Capital en la Calle (Total Cartera Activa)
            SUM(p.monto_capital - IFNULL(pagado.capital_amortizado, 0)) as capital_en_calle,
            
            -- Mora Total (Saldo Capital de Categorias B-E, es decir, con atraso > 0)
            SUM(CASE 
                WHEN DATEDIFF(NOW(), overdue.fecha_mas_antigua) > 0 THEN (p.monto_capital - IFNULL(pagado.capital_amortizado, 0))
                ELSE 0 
            END) as mora_total
            
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        
        -- Subquery para calcular cuánto capital se ha amortizado ya
        LEFT JOIN (
            SELECT prestamo_id, SUM(monto_pagado * (NULLIF(capital_cuota,0) / NULLIF(monto_cuota,0))) as capital_amortizado
            FROM cuotas 
            WHERE estado IN ('pagada', 'parcial') AND monto_cuota > 0
            GROUP BY prestamo_id
        ) pagado ON p.id = pagado.prestamo_id
        
        -- Subquery para encontrar la fecha de vencimiento más antigua impaga (para determinar mora)
        LEFT JOIN (
            SELECT prestamo_id, MIN(fecha_vencimiento) as fecha_mas_antigua
            FROM cuotas 
            WHERE estado != 'pagada'
            GROUP BY prestamo_id
        ) overdue ON p.id = overdue.prestamo_id
        
        WHERE p.estado = 'Activo'
        $whereClause
    ";

    $stmt = $db->prepare($sqlStats);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Intereses Ganados (Total Histórico)
    // Suma de la parte de interés de todas las cuotas pagadas (o abonos)
    // Se usa 'cuotas' porque ahí se registra 'monto_pagado'.
    // Asumimos proporcionalidad si hubo pago parcial.

    $sqlInteres = "
        SELECT SUM(cu.monto_pagado * (NULLIF(cu.interes_cuota,0) / NULLIF(cu.monto_cuota,0))) as intereses_ganados
        FROM cuotas cu
        JOIN prestamos p ON cu.prestamo_id = p.id
        JOIN clientes c ON p.id_cliente = c.id
        WHERE cu.monto_pagado > 0
        $whereClause
    ";

    $stmtInt = $db->prepare($sqlInteres);
    $stmtInt->execute($params);
    $interes = $stmtInt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'capital_en_calle' => floatval($stats['capital_en_calle'] ?? 0),
        'mora_total' => floatval($stats['mora_total'] ?? 0),
        'intereses_ganados' => floatval($interes['intereses_ganados'] ?? 0)
    ];

    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
