<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    $db = getDB();

    // Filtro Base: Prestamos Activos
    $sql = "SELECT p.id, p.monto_capital, p.total_a_pagar, p.plazo_meses, p.modalidad,
            c.nombre_completo, c.numero_documento as dni, c.telefono,
            (SELECT COUNT(*) FROM cuotas WHERE prestamo_id = p.id) as total_cuotas,
            (SELECT COUNT(*) FROM cuotas WHERE prestamo_id = p.id AND estado = 'pagada') as cuotas_pagadas,
            (SELECT IFNULL(SUM(monto_pagado),0) FROM cuotas WHERE prestamo_id = p.id AND estado = 'pagada') as total_abonado,
            (SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as proximo_vencimiento
            FROM prestamos p
            JOIN clientes c ON p.id_cliente = c.id
            WHERE p.estado = 'Activo'";

    $params = [];

    // Si es Asesor u Oficial, filtramos por su ID
    if (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false) {
        $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
    }

    $sql .= " ORDER BY c.nombre_completo ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar Estado Mora
    $hoy = date('Y-m-d');
    foreach ($data as &$row) {
        $row['saldo_pendiente'] = floatval($row['total_a_pagar']) - floatval($row['total_abonado']);

        $prox = $row['proximo_vencimiento'];
        if ($prox && $prox < $hoy) {
            $row['estado_cartera'] = 'Mora';
            $dias = (strtotime($hoy) - strtotime($prox)) / 86400;
            $row['dias_mora'] = floor($dias);
        } else {
            $row['estado_cartera'] = 'Al Día';
            $row['dias_mora'] = 0;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>