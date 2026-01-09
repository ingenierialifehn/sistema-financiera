<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID de préstamo requerido");
    }

    $prestamoId = intval($_GET['id']);
    $db = getDB();

    // 1. Obtener Info del Préstamo y Cliente
    $stmt = $db->prepare("
        SELECT p.*, cl.nombre_completo 
        FROM prestamos p 
        JOIN clientes cl ON p.id_cliente = cl.id
        WHERE p.id = ?
    ");
    $stmt->execute([$prestamoId]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        throw new Exception("Préstamo no encontrado");
    }

    // 2. Definir "1 Mes" según modalidad (Normalizado)
    $modNorm = ucfirst(strtolower(trim($prestamo['modalidad'])));
    $cuotasPorMes = 1;

    if ($modNorm === 'Diario' || strpos(strtoupper($modNorm), 'DIARIO') !== false) {
        $cuotasPorMes = 20;
    } elseif ($modNorm === 'Semanal') {
        $cuotasPorMes = 4;
    } elseif ($modNorm === 'Catorcenal') {
        $cuotasPorMes = 2;
    } elseif ($modNorm === 'Mensual') {
        $cuotasPorMes = 1;
    }


    // 3. Obtener Cuotas y Estado
    // Necesitamos saber qué cuotas ya se pagaron y cuáles tocan
    $stmtCuotas = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
    $stmtCuotas->execute([$prestamoId]);
    $cuotas = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

    $totalCuotas = count($cuotas);
    $hoy = date('Y-m-d');

    // Contar vencidas teóricas (Hasta hoy)
    $cuotasVencidasCount = 0;
    $cuotasPagadasCount = 0;

    $saldoCapitalReal = 0;
    $interesExigible = 0;

    foreach ($cuotas as $c) {
        // Calcular Capital Pendiente Real (Total)
        if ($c['estado'] !== 'pagada') {
            // Si es parcial, restar lo pagado proporcional al capital?? 
            // Simplificación: Asumimos 'monto_pagado' cubre primero interes.
            // Pero para 'SaldoCapitalReal', usamos la lógica de list_grouped:
            // Capital Original - Capital Amortizado.
            // Vamos a recalcular el capital amortizado exacto abajo.
        }

        if ($c['fecha_vencimiento'] <= $hoy) {
            $cuotasVencidasCount++;
        }
        if ($c['estado'] === 'pagada') {
            $cuotasPagadasCount++;
        }
    }

    // 3.1 Calculo preciso de Capital Pendiente
    // Capital Original - Suma(Capital cubierto por pagos)
    // Capital cubierto por un pago = (monto_pagado) * (capital_cuota / monto_cuota_teorico) estimate?
    // Mejor: Usar el campo `capital_cuota` completo si está pagada.
    // Si está 'parcial', es complejo. Asumiremos para simplificar que en 'Cancelación' recalculamos todo el capital pendiente.

    // Método Seguro: Capital Total - Capital ya pagado en cuotas completas.
    // (Las parciales en este sistema suelen cubrir cuotas viejas, no capital futuro).
    // Revisar logica de abonos.
    // Vamos a usar la lógica de `list_grouped.php` para Saldo Capital:
    /*
    (p.monto_capital - IFNULL((
        SELECT SUM(c_inner.monto_pagado * (c_inner.capital_cuota / c_inner.monto_cuota))
        FROM cuotas c_inner
        WHERE c_inner.prestamo_id = p.id 
        AND c_inner.estado IN ('pagada', 'parcial')
        AND c_inner.monto_cuota > 0
    ), 0))
    */
    $stmtCap = $db->prepare("
        SELECT (IFNULL(SUM(CASE 
            WHEN monto_cuota > 0 THEN monto_pagado * (capital_cuota / monto_cuota)
            ELSE 0 END), 0)) as capital_pagado
        FROM cuotas 
        WHERE prestamo_id = ? AND estado IN ('pagada', 'parcial')
    ");
    $stmtCap->execute([$prestamoId]);
    $capitalPagado = floatval($stmtCap->fetchColumn());
    $saldoCapitalReal = floatval($prestamo['monto_capital']) - $capitalPagado;
    if ($saldoCapitalReal < 0)
        $saldoCapitalReal = 0;

    // 4. Calcular Interés/Cargos a Cobrar (Regla del Máximo)
    // Regla: Cobrar interés hasta la fecha (Vencidas) O Mínimo 1 mes.
    // "El interés más alto entre: si toca decidir entre si cobrar el interes del mes... o si lo lleva en atraso"
    // Interpretación: Max(Cuotas Vencidas, 1 Mes de Cuotas).
    // OJO: Si el préstamo tiene MENOS cuotas que 1 mes (raro, prestamo de 1 semana?), Cap at TotalCuotas.

    $targetCount = max($cuotasVencidasCount, $cuotasPorMes);
    $targetCount = min($targetCount, $totalCuotas); // No cobrar más de lo que existe

    // 4. Calcular Interés/Cargos a Cobrar (Regla del Máximo)
    $targetCount = max($cuotasVencidasCount, $cuotasPorMes);
    $targetCount = min($targetCount, $totalCuotas);

    // CÁLCULO GLOBAL CORREGIDO (Agrupado por Número de Cuota)
    // El problema anterior: Pagos parciales generan múltiples filas para el mismo 'numero_cuota'.
    // Iterar por índice ($i < 20) tomaba solo 20 filas, quedándose corto si había divisiones.

    // Solución: Sumar el interés teórico de TODAS las filas cuyo numero_cuota <= 20.

    $interesRequeridoTotal = 0;
    foreach ($cuotas as $c) {
        // Asumimos que numero_cuota es numérico y secuencial (1..N)
        if (intval($c['numero_cuota']) <= $targetCount) {
            $totalNoCapital = floatval($c['interes_cuota']) + floatval($c['gastos_cuota']) + floatval($c['comision_cuota']);
            $interesRequeridoTotal += $totalNoCapital;
        }
    }

    // 2. Calcular cuánto INTERÉS ya ha pagado el cliente en TOTAL (histórico)
    $totalDineroPagado = 0;
    foreach ($cuotas as $c) {
        $totalDineroPagado += floatval($c['monto_pagado'] ?? 0);
    }

    $interesYaPagado = $totalDineroPagado - $capitalPagado;
    if ($interesYaPagado < 0)
        $interesYaPagado = 0;

    // 3. Diferencia
    $interesPendienteCobrar = $interesRequeridoTotal - $interesYaPagado;
    if ($interesPendienteCobrar < 0)
        $interesPendienteCobrar = 0;

    // 5. Total Cancelación
    // El cliente debe pagar: El Capital que falta + El Interés que falta para cubrir la meta.
    $totalCancelacion = $saldoCapitalReal + $interesPendienteCobrar;

    echo json_encode([
        'success' => true,
        'data' => [
            'saldo_capital' => $saldoCapitalReal,
            'interes_requerido' => $interesRequeridoTotal,
            'interes_pagado' => $interesYaPagado,
            'interes_pendiente' => $interesPendienteCobrar,
            'total_cancelacion' => $totalCancelacion,
            'explicacion' => "Meta: $targetCount cuotas ($modNorm). Interés Requerido: " . number_format($interesRequeridoTotal, 2) . " - Pagado: " . number_format($interesYaPagado, 2)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
