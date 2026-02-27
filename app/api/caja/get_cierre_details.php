<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    $idControl = $_GET['id_control'] ?? null;

    if (!$idControl) {
        throw new Exception("ID de control requerido");
    }

    $db = getDB();

    // 1. Obtener datos del cierre
    $stmtCierre = $db->prepare("
        SELECT c.*, 
               u.username as usuario_cierre,
               col.nombre_completo as nombre_oficial,
               a.nombre_agencia
        FROM control_caja_diaria c
        LEFT JOIN usuarios u ON c.id_usuario_cierre = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        JOIN agencias a ON c.id_agencia = a.id_agencia
        WHERE c.id_control = ?
    ");
    $stmtCierre->execute([$idControl]);
    $cierre = $stmtCierre->fetch(PDO::FETCH_ASSOC);

    if (!$cierre) {
        throw new Exception("Cierre no encontrado");
    }

    $fechaCierre = date('Y-m-d', strtotime($cierre['fecha_dia']));
    $idAgencia = $cierre['id_agencia'];

    // 2. Obtener Transacciones del Día (Similar a cierre.php)
    $stmtTrans = $db->prepare("
        SELECT 
            cl.nombre_completo as cliente,
            c.numero_cuota,
            c.monto_pagado,
            LEAST(c.monto_pagado, c.capital_cuota) as capital_pagado,
            (c.monto_pagado - LEAST(c.monto_pagado, c.capital_cuota)) as interes_pagado,
            DATE_FORMAT(c.fecha_pago_real, '%H:%i') as hora,
            COALESCE(col_cobro.nombre_completo, u_cobro.username) as cobrador
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        JOIN clientes cl ON p.id_cliente = cl.id
        LEFT JOIN usuarios u_cobro ON c.usuario_cobro_id = u_cobro.id_usuario
        LEFT JOIN colaboradores col_cobro ON u_cobro.id_colaborador = col_cobro.id_colaborador
        WHERE cl.id_agencia = ? 
        AND DATE(c.fecha_pago_real) = ?
        AND c.estado = 'pagada'
        ORDER BY c.fecha_pago_real ASC
    ");
    $stmtTrans->execute([$idAgencia, $fechaCierre]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener Supervisor (Asumimos actual si no guardado en historico)
    // O mejor, "Sin supervisor asignado" para evitar error
    $nombreSupervisor = "Sin supervisor asignado (Histórico)";

    // 4. Saldo Bóveda
    // Preferencia por el valor HISTÓRICO guardado al cierre
    $saldoBovedaHist = isset($cierre['saldo_boveda_cierre']) ? floatval($cierre['saldo_boveda_cierre']) : null;

    if ($saldoBovedaHist !== null) {
        $saldoReportar = $saldoBovedaHist;
        $labelBoveda = "Saldo en Bóveda (Cierre)";
    } else {
        // Fallback: Saldo actual (para cierres antiguos sin la columna llena)
        $stmtBoveda = $db->prepare("SELECT saldo_efectivo FROM cajas_agencias WHERE id_agencia = ?");
        $stmtBoveda->execute([$idAgencia]);
        $saldoReportar = $stmtBoveda->fetchColumn();
        $labelBoveda = "Saldo en Bóveda (Actual*)";
    }

    session_start();
    $usuarioImprime = $_SESSION['username'] ?? 'Sistema';

    echo json_encode([
        'success' => true,
        'data' => [
            'nombre_agencia' => $cierre['nombre_agencia'],
            'fecha' => date('d/m/Y', strtotime($fechaCierre)),
            'hora_cierre' => date('h:i A', strtotime($cierre['hora_cierre'])),
            'nombre_oficial' => $cierre['nombre_oficial'] ?? $cierre['usuario_cierre'],
            'nombre_supervisor' => $nombreSupervisor,
            'saldo_boveda' => $saldoReportar,
            'label_boveda' => $labelBoveda,
            'saldo_cierre_sistema' => $cierre['saldo_cierre_sistema'],
            'saldo_cierre_fisico' => $cierre['saldo_cierre_fisico'],
            'diferencia' => $cierre['diferencia_cierre'],
            'transacciones' => $transacciones,
            'usuario_imprime' => $usuarioImprime,
            'es_historico' => true
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>