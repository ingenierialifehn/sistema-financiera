<?php
/**
 * Script de Diagnóstico de Pagos del Día
 * Muestra todos los pagos realizados hoy, independientemente del estado
 */

require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia) {
        throw new Exception('No se pudo determinar la agencia del usuario');
    }

    $db = getDB();
    $fechaHoy = date('Y-m-d');

    // Obtener TODOS los pagos de hoy (sin filtrar por estado)
    $sqlTodos = "SELECT 
                 cu.id,
                 cu.numero_cuota,
                 cu.monto_pagado,
                 cu.monto_cuota,
                 cu.fecha_pago,
                 cu.estado,
                 cu.capital_cuota,
                 cu.interes_cuota,
                 cu.gastos_cuota,
                 cu.comision_cuota,
                 c.nombre_completo,
                 p.id as prestamo_id
                 FROM cuotas cu
                 JOIN prestamos p ON cu.prestamo_id = p.id
                 JOIN clientes c ON p.id_cliente = c.id
                 WHERE DATE(cu.fecha_pago) = ?
                 AND c.id_agencia = ?
                 ORDER BY cu.fecha_pago DESC";

    $stmt = $db->prepare($sqlTodos);
    $stmt->execute([$fechaHoy, $idAgencia]);
    $todosPagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar por estado
    $porEstado = [];
    $totalPorEstado = [];

    foreach ($todosPagos as $pago) {
        $estado = $pago['estado'];
        if (!isset($porEstado[$estado])) {
            $porEstado[$estado] = 0;
            $totalPorEstado[$estado] = 0;
        }
        $porEstado[$estado]++;
        $totalPorEstado[$estado] += floatval($pago['monto_pagado']);
    }

    // Total general
    $totalGeneral = array_sum($totalPorEstado);

    // Pagos con estado 'pagada'
    $sqlPagadas = "SELECT COUNT(*) as total, IFNULL(SUM(monto_pagado), 0) as monto
                   FROM cuotas cu
                   JOIN prestamos p ON cu.prestamo_id = p.id
                   JOIN clientes c ON p.id_cliente = c.id
                   WHERE DATE(cu.fecha_pago) = ?
                   AND c.id_agencia = ?
                   AND cu.estado = 'pagada'";

    $stmt = $db->prepare($sqlPagadas);
    $stmt->execute([$fechaHoy, $idAgencia]);
    $pagadas = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'id_agencia' => $idAgencia,
            'total_registros' => count($todosPagos),
            'total_general' => $totalGeneral,
            'resumen_por_estado' => [
                'conteo' => $porEstado,
                'montos' => $totalPorEstado
            ],
            'pagadas_filtradas' => [
                'cantidad' => $pagadas['total'],
                'monto' => $pagadas['monto']
            ],
            'todos_los_pagos' => $todosPagos,
            'diagnostico' => [
                'problema_detectado' => count($todosPagos) != $pagadas['total'],
                'mensaje' => count($todosPagos) != $pagadas['total']
                    ? "Hay pagos con estados diferentes a 'pagada' que no se están contando"
                    : "Todos los pagos tienen estado 'pagada'"
            ]
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>