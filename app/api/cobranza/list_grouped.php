<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    $fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    $db = getDB();

    // Consulta: Prestamos Activos + Info de la Cuota Mas Antigua Pendiente
    $sql = "SELECT 
                p.id as prestamo_id, 
                p.id_cliente,
                p.monto_capital, 
                p.total_a_pagar,
                p.modalidad,
                cl.nombre_completo, 
                cl.numero_documento,
                u.username as asesor,
                (SELECT COUNT(*) FROM cuotas c WHERE c.prestamo_id = p.id AND c.estado = 'pagada') as pagadas,
                (SELECT COUNT(*) FROM cuotas c WHERE c.prestamo_id = p.id) as total_cuotas,
                (SELECT IFNULL(SUM(monto_pagado), 0) FROM cuotas WHERE prestamo_id = p.id) as total_pagado_real,
                -- Info Proxima Cuota
                c.id as cuota_id,
                c.numero_cuota,
                c.fecha_vencimiento,
                c.monto_cuota,
                c.estado as estado_cuota,
                DATEDIFF(?, c.fecha_vencimiento) as dias_atraso
            FROM prestamos p
            JOIN clientes cl ON p.id_cliente = cl.id
            LEFT JOIN usuarios u ON p.asesor_creditos_id = u.id_usuario
            -- Join magico para obtener SOLO la cuota pendiente mas antigua
            LEFT JOIN cuotas c ON c.id = (
                SELECT c2.id FROM cuotas c2 
                WHERE c2.prestamo_id = p.id AND c2.estado != 'pagada'
                ORDER BY c2.fecha_vencimiento ASC, c2.numero_cuota ASC
                LIMIT 1
            )
            WHERE p.estado = 'Activo'";

    $params = [$fechaFiltro]; // Para DATEDIFF

    // Filtros de Rol
    if (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false) {
        $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
    }

    // Filtro Agencia (si aplica)
    if ($agenciaId) {
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY (c.fecha_vencimiento IS NULL), c.fecha_vencimiento ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamiento visual
    foreach ($data as &$row) {
        // Finance Calculations
        $montoCap = floatval($row['monto_capital']);
        $totalCuotas = intval($row['total_cuotas']);
        $pagadas = intval($row['pagadas']);

        // Linear Capital Amortization Estimate (Si es sistema Flat)
        if ($totalCuotas > 0) {
            $row['saldo_capital'] = $montoCap * (1 - ($pagadas / $totalCuotas));
        } else {
            $row['saldo_capital'] = $montoCap;
        }

        // Balance Total (Deuda total restante)
        $totalPagar = floatval($row['total_a_pagar']);
        $pagado = floatval($row['total_pagado_real']);
        $row['saldo_balance'] = max(0, $totalPagar - $pagado);

        // Estado Visual
        if (!$row['cuota_id']) {
            $row['estado_visual'] = 'Al Día (Finalizando)';
            $row['class_visual'] = 'bg-green-100 text-green-800';
            $row['urgencia'] = 0;
        } else {
            $dias = intval($row['dias_atraso']);
            if ($dias > 0) {
                $row['estado_visual'] = "Mora ($dias días)";
                $row['class_visual'] = 'bg-red-100 text-red-800';
                $row['urgencia'] = 2; // Alta prioridad
            } elseif ($dias == 0) {
                $row['estado_visual'] = "Vence Hoy";
                $row['class_visual'] = 'bg-yellow-100 text-yellow-800';
                $row['urgencia'] = 1;
            } else {
                $row['estado_visual'] = "Futuro (" . abs($dias) . " días)";
                $row['class_visual'] = 'bg-blue-100 text-blue-800';
                $row['urgencia'] = 0;
            }
        }

        // Formato fechas
        if ($row['fecha_vencimiento']) {
            $row['fecha_fmt'] = date('d/m/Y', strtotime($row['fecha_vencimiento']));
        } else {
            $row['fecha_fmt'] = 'N/A';
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>