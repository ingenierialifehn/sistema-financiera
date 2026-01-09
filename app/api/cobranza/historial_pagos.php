<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    // Filtros
    $fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    // Seguridad: Gestores solo ven HOY
    $isGestor = (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false);
    if ($isGestor) {
        $fechaFiltro = date('Y-m-d');
    }

    $db = getDB();

    // Consulta: Cuotas pagadas o parciales
    // Se asume que fecha_pago_real indica cuándo se recibió el dinero
    // Consulta: Agrupada por Transacción (Fecha exacta + Préstamo)
    // Para evitar mostrar múltiples filas por un solo pago dividido
    $sql = "SELECT 
                GROUP_CONCAT(c.id) as cuota_ids,
                c.fecha_pago_real,
                SUM(c.monto_pagado) as monto_pagado_total,
                cl.nombre_completo, 
                cl.numero_documento,
                p.id as prestamo_id,
                GROUP_CONCAT(c.numero_cuota ORDER BY c.numero_cuota ASC) as cuotas_nums
            FROM cuotas c
            JOIN prestamos p ON c.prestamo_id = p.id
            JOIN clientes cl ON p.id_cliente = cl.id
            WHERE (c.monto_pagado > 0)
              AND DATE(c.fecha_pago_real) = ?";

    $params = [$fechaFiltro];

    // Filtros de Rol (Asesor ve solo lo suyo)
    if (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false) {
        $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
    }

    // Filtro Agencia (Lógica de Privacidad)
    $canViewAll = (stripos($rol, 'Administrador') !== false || stripos($rol, 'Gerente') !== false);
    if (!$canViewAll) {
        $sessionAgencia = $_SESSION['id_agencia'] ?? 0;
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $sessionAgencia;
    } else {
        if ($agenciaId && $agenciaId !== 'todas') {
            $sql .= " AND cl.id_agencia = ?";
            $params[] = $agenciaId;
        }
    }

    $sql .= " GROUP BY p.id, c.fecha_pago_real ORDER BY c.fecha_pago_real DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formateo
    // Formateo
    foreach ($data as &$row) {
        $montoTotal = floatval($row['monto_pagado_total']);
        $row['monto_fmt'] = number_format($montoTotal, 2);

        $row['fecha_fmt'] = date('d/m/Y H:i', strtotime($row['fecha_pago_real']));

        // Aliases para compatibilidad con el frontend
        $row['id'] = $row['cuota_ids'];  // ID ahora contiene "1,5,6" (CSV)
        $row['fecha_hora'] = $row['fecha_pago_real'];
        $row['cliente'] = $row['nombre_completo'];
        $row['monto'] = $montoTotal;

        // Generar Concepto Amigable
        $nums = explode(',', $row['cuotas_nums']);
        $hasAbono = in_array('0', $nums);
        $cleanNums = array_filter($nums, fn($n) => $n != '0');

        $conceptParts = [];
        if (!empty($cleanNums)) {
            $conceptParts[] = "Cuota(s): " . implode(', ', $cleanNums);
        }
        if ($hasAbono) {
            $conceptParts[] = "Abono Capital";
        }
        $conceptTxt = implode(' + ', $conceptParts);
        if (empty($conceptTxt))
            $conceptTxt = "Pago General";

        $row['concepto'] = "$conceptTxt - Préstamo #{$row['prestamo_id']}";
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>