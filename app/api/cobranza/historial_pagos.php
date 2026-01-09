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

    // ==========================================
    // SEGURIDAD ESTRICTA: SOLO MI HISTORIAL
    // ==========================================

    // Regla: Veo el pago SI:
    // 1. Soy quien cobró el dinero (c.usuario_cobro_id)
    // 2. O soy el Asesor de Crédito del préstamo (p.asesor_creditos_id)
    // 3. O soy el Oficial de Desembolso (p.oficial_desembolsos_id)
    // 4. O soy el Cobrador asignado al cliente (cl.cobrador_id)

    $idFiltro = (isset($userId) && intval($userId) > 0) ? intval($userId) : -1;

    $sql .= " AND (c.usuario_cobro_id = ? OR p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ? OR cl.cobrador_id = ?)";
    $params[] = $idFiltro;
    $params[] = $idFiltro;
    $params[] = $idFiltro;
    $params[] = $idFiltro;

    // Filtro de Agencia: Aunque ya filtramos por usuario, reforzamos (opcional)
    // Si el usuario tiene sesión de agencia, filtramos por ella también para evitar mezclas raras
    if (isset($_SESSION['id_agencia']) && $_SESSION['id_agencia']) {
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $_SESSION['id_agencia'];
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