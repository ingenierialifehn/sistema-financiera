<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

try {
    $db = getDB();
    $report = [];

    // --- 1. AUDITORÍA DE BANCOS ---
    $bancos = $db->query("SELECT * FROM bancos")->fetchAll(PDO::FETCH_ASSOC);
    $report['bancos'] = [];

    foreach ($bancos as $b) {
        $stmtCalc = $db->prepare("
            SELECT SUM(CASE 
                WHEN tipo_transaccion = 'ingreso' THEN monto 
                ELSE -monto 
            END) as calculated
            FROM movimientos_bancarios 
            WHERE banco_id = ?
        ");
        $stmtCalc->execute([$b['id']]);
        $calc = floatval($stmtCalc->fetchColumn() ?? 0);
        $actual = floatval($b['saldo_actual']);
        $diff = round($calc - $actual, 2);

        $report['bancos'][] = [
            'id' => $b['id'],
            'nombre' => $b['nombre_banco'] . ' (' . $b['numero_cuenta'] . ')',
            'saldo_actual' => $actual,
            'saldo_historico' => $calc,
            'diferencia' => $diff,
            'status' => ($diff == 0) ? 'OK' : 'ERROR'
        ];
    }

    // --- 2. AUDITORÍA DE AGENCIAS (CAJA OPERATIVA) ---
    // Nota: Esta lógica asume tipos de movimientos estándar.
    $agencias = $db->query("SELECT a.id_agencia, a.nombre_agencia, c.saldo_caja_operativa, c.saldo_efectivo 
                            FROM agencias a 
                            LEFT JOIN cajas_agencias c ON a.id_agencia = c.id_agencia")->fetchAll(PDO::FETCH_ASSOC);
    $report['agencias'] = [];

    foreach ($agencias as $a) {
        // Calcular Flujo Caja Operativa
        $sqlMovs = "SELECT tipo_movimiento, SUM(monto) as total 
                    FROM movimientos_internos_agencia 
                    WHERE id_agencia = ? 
                    GROUP BY tipo_movimiento";
        $stmtMovs = $db->prepare($sqlMovs);
        $stmtMovs->execute([$a['id_agencia']]);
        $movs = $stmtMovs->fetchAll(PDO::FETCH_KEY_PAIR); // [Tipo => Monto]

        $calcOp = 0;
        // Definir lógica de signos
        foreach ($movs as $tipo => $monto) {
            $monto = floatval($monto);
            // Entradas
            if (
                stripos($tipo, 'Ingreso') !== false ||
                stripos($tipo, 'Recaudo') !== false ||
                stripos($tipo, 'Boveda a Caja') !== false ||
                stripos($tipo, 'Aporte') !== false
            ) {
                $calcOp += $monto;
            }
            // Salidas
            else if (
                stripos($tipo, 'Banco') !== false || // Caja a Banco
                stripos($tipo, 'Ruta') !== false || // Caja a Ruta (Desembolso)
                stripos($tipo, 'Gasto') !== false ||
                stripos($tipo, 'Retiro') !== false ||
                stripos($tipo, 'Caja a Boveda') !== false
            ) {
                $calcOp -= $monto;
            }
        }

        $actualOp = floatval($a['saldo_caja_operativa']);
        $diffOp = round($calcOp - $actualOp, 2);

        $report['agencias'][] = [
            'nombre' => $a['nombre_agencia'],
            'saldo_caja_operativa' => $actualOp,
            'saldo_historico_calculado' => $calcOp,
            'diferencia' => $diffOp,
            'status' => ($diffOp == 0) ? 'OK' : 'REVISAR',
            'detalle_movs' => $movs // Para debug en frontend
        ];
    }

    // --- 3. AUDITORÍA DE CARTERA ---
    // Capital Prestado - Capital Pagado = Saldo Cartera
    $stmtCartera = $db->query("
        SELECT 
            SUM(monto_capital) as total_prestado,
            (SELECT SUM(IF(monto_pagado >= capital_cuota, capital_cuota, monto_pagado)) FROM cuotas WHERE estado='pagada') as total_capital_pagado
        FROM prestamos 
        WHERE estado != 'Anulado' AND estado != 'Rechazado'
    ");
    $carteraData = $stmtCartera->fetch(PDO::FETCH_ASSOC);

    $totalPrestado = floatval($carteraData['total_prestado'] ?? 0);
    $totalPagado = floatval($carteraData['total_capital_pagado'] ?? 0);
    $saldoEsperado = $totalPrestado - $totalPagado;

    // Comparar con saldo real en préstamos (si existiera campo saldo_actual, si no, este es el calculado principal)
    // Asumiremos que este es el valor de "Activo Cartera"

    $report['cartera'] = [
        'total_prestado' => $totalPrestado,
        'total_pagado' => $totalPagado,
        'saldo_pendiente_real' => $saldoEsperado
    ];

    // --- 4. ALERTAS SILENCIOSAS (BANDEJA DE ENTRADA) ---
    $stmtAlerts = $db->query("SELECT * FROM alertas_sistema WHERE estado = 'pendiente' ORDER BY fecha_generacion DESC");
    $alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);
    $report['alertas'] = $alerts;

    echo json_encode(['success' => true, 'data' => $report, 'fecha' => date('d/m/Y H:i:s')]);

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
