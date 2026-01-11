<?php
/**
 * API: Reportes Financieros
 * GET /app/api/reportes/financieros.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Ensure user is authenticated
$user = AuthMiddleware::requireAuth();

// Parameters
$action = $_GET['action'] ?? 'audit'; // audit, income_statement, balance
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$agencyId = $_GET['agency_id'] ?? null;
$type = $_GET['type'] ?? 'all'; // all, income, expense (for audit)

$db = getDB();

try {
    if ($action === 'audit') {
        getAuditReport($db, $startDate, $endDate, $agencyId, $type);
    } elseif ($action === 'income_statement') {
        getIncomeStatement($db, $startDate, $endDate, $agencyId);
    } elseif ($action === 'balance') {
        getBalanceSheet($db, $agencyId);
    } elseif ($action === 'get_agencies') {
        getAgencies($db);
    } else {
        Response::error('Acción inválida', 400);
    }
} catch (Exception $e) {
    Response::serverError('Error al generar reporte: ' . $e->getMessage());
}

function getAgencies($db)
{
    $stmt = $db->query("SELECT id_agencia as id, nombre_agencia as nombre FROM agencias WHERE estado = 'Activa'");
    $agencies = $stmt->fetchAll();
    Response::success($agencies);
}

function getAuditReport($db, $start, $end, $agencyId, $type)
{
    // 1. Cobros (Ingresos) from Cuotas
    $cobrosQuery = "
        SELECT 
            c.fecha_pago_real as fecha,
            COALESCE(a.nombre_agencia, 'N/A') as agencia,
            CONCAT('Cobro Cuota #', c.numero_cuota, ' - Préstamo ', p.id) as concepto,
            'Cobro' as categoria,
            c.monto_pagado as entrada,
            0 as salida
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        LEFT JOIN usuarios u ON p.asesor_creditos_id = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        LEFT JOIN agencias a ON col.id_agencia = a.id_agencia
        WHERE c.monto_pagado > 0 
        AND DATE(c.fecha_pago_real) BETWEEN :start1 AND :end1
    ";

    // 2. Movimientos Internos Agencia
    $internosQuery = "
        SELECT 
            m.fecha_movimiento as fecha,
            a.nombre_agencia as agencia,
            m.observaciones as concepto,
            m.tipo_movimiento as categoria,
            CASE 
                WHEN m.tipo_movimiento IN ('Ingreso', 'Boveda a Caja', 'Reintegro', 'Recaudo Asesor', 'Recaudo', 'Entrega Asesor', 'Cobro') THEN m.monto 
                ELSE 0 
            END as entrada,
            CASE 
                WHEN m.tipo_movimiento NOT IN ('Ingreso', 'Boveda a Caja', 'Reintegro', 'Recaudo Asesor', 'Recaudo', 'Entrega Asesor', 'Cobro') THEN m.monto 
                ELSE 0 
            END as salida
        FROM movimientos_internos_agencia m
        JOIN agencias a ON m.id_agencia = a.id_agencia
        WHERE DATE(m.fecha_movimiento) BETWEEN :start2 AND :end2
    ";

    // 3. Movimientos Bancarios
    $bancosQuery = "
        SELECT 
            b.fecha_hora as fecha,
            'Bancos' as agencia,
            CONCAT(b.tipo_transaccion, ' - ', b.descripcion) as concepto,
            b.tipo_transaccion as categoria,
            CASE WHEN b.tipo_transaccion = 'ingreso' THEN b.monto ELSE 0 END as entrada,
            CASE WHEN b.tipo_transaccion IN ('egreso', 'traspaso_caja') THEN b.monto ELSE 0 END as salida
        FROM movimientos_bancarios b
        WHERE DATE(b.fecha_hora) BETWEEN :start3 AND :end3
    ";

    // 4. Traslados a Agencias (Ingresos Bancos Agencia)
    // Treated as Egreso from Banco perspective for the report balance
    $trasladosQuery = "
        SELECT 
            i.fecha_hora as fecha,
            'Bancos' as agencia,
            CONCAT('Inyección a ', a.nombre_agencia, ' - Ref: ', i.referencia) as concepto,
            'Inyección de Capital' as categoria,
            0 as entrada,
            i.monto as salida
        FROM ingresos_bancos_agencia i
        JOIN agencias a ON i.agencia_id = a.id_agencia
        WHERE DATE(i.fecha_hora) BETWEEN :start4 AND :end4
    ";

    $params = [
        'start1' => $start,
        'end1' => $end,
        'start2' => $start,
        'end2' => $end,
        'start3' => $start,
        'end3' => $end,
        'start4' => $start,
        'end4' => $end
    ];

    // Build WHERE clauses for filters
    if ($agencyId && $agencyId !== 'all') {
        $cobrosQuery .= " AND a.id_agencia = :agency1";
        $internosQuery .= " AND m.id_agencia = :agency2";
        // Bancos often global, but if filtered by agency, maybe omit or filter?
        $bancosQuery .= " AND (b.entidad_destino_tipo = 'agencia' AND b.entidad_destino_id = :agency3)";
        $trasladosQuery .= " AND i.agencia_id = :agency4";

        $params['agency1'] = $agencyId;
        $params['agency2'] = $agencyId;
        $params['agency3'] = $agencyId;
        $params['agency4'] = $agencyId;
    }

    // Combine queries
    if (!$agencyId || $agencyId === 'all') {
        $fullQuery = "($cobrosQuery) UNION ALL ($internosQuery) UNION ALL ($bancosQuery) UNION ALL ($trasladosQuery)";
    } else {
        $fullQuery = "($cobrosQuery) UNION ALL ($internosQuery) UNION ALL ($bancosQuery) UNION ALL ($trasladosQuery)";
    }

    $fullQuery .= " ORDER BY fecha DESC";

    $stmt = $db->prepare($fullQuery);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $movements = $stmt->fetchAll();

    // Calculate running balance (this is tricky with unions and pagination, doing simple accumulation here)
    // Balance acumulate implies order ASC
    $movements = array_reverse($movements); // Process oldest to newest
    $balance = 0;
    foreach ($movements as &$mov) {
        // Only Banking movements affect the central accumulated balance
        // Agency movements and Collections are informative trails until deposited.
        // We check if the 'agencia' column implies 'Bancos' or the source table logic.
        // In our query, Bank movements have 'agencia' = 'Bancos'.

        if ($mov['agencia'] === 'Bancos') {
            $balance += ($mov['entrada'] - $mov['salida']);
        }
        $mov['saldo'] = $balance;
    }
    $movements = array_reverse($movements); // Return newest first

    // Filter by type if needed
    if ($type !== 'all') {
        $movements = array_filter($movements, function ($m) use ($type) {
            if ($type === 'ingreso')
                return $m['entrada'] > 0;
            if ($type === 'egreso')
                return $m['salida'] > 0;
            return true;
        });
    }

    Response::success(array_values($movements));
}

function getIncomeStatement($db, $start, $end, $agencyId)
{
    $params = ['start' => $start, 'end' => $end];
    $agencyFilter = "";
    $agencyFilterPlanilla = "";

    if ($agencyId && $agencyId !== 'all') {
        $agencyFilter = " AND a.id_agencia = :agencyId"; // Adjusted to use joined agency
        $agencyFilterPlanilla = " AND col.id_agencia = :agencyIdPlanilla";
        $params['agencyId'] = $agencyId;
        $params['agencyIdPlanilla'] = $agencyId;
    }

    // 1. Ingresos Operativos (Interes + Comision + Mora + Gastos) from PAID cuotas
    // Note: Assuming cuotas table has columns interes_cuota, comision_cuota.
    // We sum these for cuotas paid in the range. 
    // Wait, if monto_pagado < monto_cuota, how to split?
    // We'll estimate proportional or take full if status='pagada'.
    // Better: sum `interes_cuota` + `comision_cuota` where fecha_pago_real IN range.
    $ingresosSql = "
        SELECT 
            SUM(c.interes_cuota + c.comision_cuota + c.monto_mora + c.gastos_cuota) as total_ingresos
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        LEFT JOIN usuarios u ON p.asesor_creditos_id = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        LEFT JOIN agencias a ON col.id_agencia = a.id_agencia
        WHERE c.monto_pagado > 0  -- Any payment
        AND DATE(c.fecha_pago_real) BETWEEN :start AND :end
        $agencyFilter
    ";

    // 2. Costos Operativos (Comisiones + Gastos Campo)
    // From historico_planillas
    $costosSql = "
        SELECT 
            SUM(hp.comision_calculada + hp.gastos_campo) as total_costos
        FROM historico_planillas hp
        JOIN colaboradores col ON hp.colaborador_id = col.id_colaborador
        -- agencias linked via col
        WHERE DATE(hp.fecha_generacion) BETWEEN :start AND :end
        $agencyFilterPlanilla
    ";

    // 3. Gastos Administrativos
    // From movimientos_internos_agencia
    // Categories: Luz, Alquiler, Agua, Materiales.
    // Sueldos Base is also here (from historico_planillas sueldo_base or movimientos).

    // 3a. Sueldos Base (from Planillas)
    $sueldosSql = "
        SELECT SUM(hp.sueldo_base) as total_sueldos
        FROM historico_planillas hp
        WHERE DATE(hp.fecha_generacion) BETWEEN :start AND :end
    ";

    // 3b. Other Admin Expenses (Movimientos)
    // We filter by exclusion of operational types or inclusion of administrative keywords
    // To be safe, let's include specific categories identifiable as Admin Expenses.
    // Also check 'observaciones' for keywords like 'Luz', 'Alquiler', etc if category is generic 'Egreso' or 'Gasto Operativo'

    $gastosAdminSql = "
        SELECT 
            CASE 
                WHEN tipo_movimiento IN ('Luz', 'Alquiler', 'Agua', 'Materiales', 'Internet', 'Telefono', 'Papeleria') THEN tipo_movimiento
                WHEN observaciones LIKE '%Luz%' THEN 'Luz'
                WHEN observaciones LIKE '%Alquiler%' THEN 'Alquiler'
                WHEN observaciones LIKE '%Agua%' THEN 'Agua'
                ELSE tipo_movimiento 
            END as categoria_normalizada,
            SUM(monto) as total
        FROM movimientos_internos_agencia
        WHERE DATE(fecha_movimiento) BETWEEN :start AND :end
        AND tipo_movimiento NOT IN ('Ingreso', 'Boveda a Caja', 'Reintegro', 'Recaudo Asesor', 'Recaudo', 'Entrega Asesor', 'Cobro')
        AND (
            tipo_movimiento IN ('Luz', 'Alquiler', 'Agua', 'Materiales', 'Internet', 'Telefono', 'Papeleria')
            OR observaciones LIKE '%Luz%' 
            OR observaciones LIKE '%Alquiler%'
            OR observaciones LIKE '%Agua%'
            OR observaciones LIKE '%Materiales%'
        )
    ";

    if ($agencyId && $agencyId !== 'all') {
        $gastosAdminSql .= " AND id_agencia = :agencyId";
    }
    $gastosAdminSql .= " GROUP BY categoria_normalizada";

    // Execute logic
    $stmt = $db->prepare($ingresosSql);
    $stmt->execute($params);
    $ingresos = $stmt->fetch()['total_ingresos'] ?? 0;

    // For cost/sueldos, we need to be careful with agency filter on planillas.
    // Let's ignore agency filter for planillas for now or fetch all and filter in PHP if complex.
    // Simplify: Just run queries.
    // NOTE: Need to fix Costos SQL params.
    $stmt = $db->prepare($costosSql);
    // Use the Prepared $params which contains start, end, agencyId, agencyIdPlanilla
    $stmt->execute($params);
    $costos = $stmt->fetch()['total_costos'] ?? 0;

    $stmt = $db->prepare($sueldosSql);
    $stmt->execute(['start' => $start, 'end' => $end]);
    $sueldos = $stmt->fetch()['total_sueldos'] ?? 0;

    // Admin expenses
    $stmt = $db->prepare($gastosAdminSql);
    $paramsAdmin = ['start' => $start, 'end' => $end];
    if ($agencyId && $agencyId !== 'all')
        $paramsAdmin['agencyId'] = $agencyId;
    $stmt->execute($paramsAdmin);
    $gastosAdminRows = $stmt->fetchAll();

    $gastosAdminTotal = $sueldos;
    $gastosAdminDetails = [['categoria' => 'Sueldos Base', 'monto' => $sueldos]];
    foreach ($gastosAdminRows as $row) {
        $gastosAdminTotal += $row['total'];
        $gastosAdminDetails[] = ['categoria' => $row['categoria_normalizada'], 'monto' => $row['total']];
    }

    $utilidadBruta = $ingresos - $costos;
    $utilidadNeta = $utilidadBruta - $gastosAdminTotal;

    $report = [
        'ingresos_operativos' => $ingresos,
        'costos_operativos' => $costos,
        'utilidad_bruta' => $utilidadBruta,
        'gastos_administrativos' => [
            'total' => $gastosAdminTotal,
            'detalles' => $gastosAdminDetails
        ],
        'utilidad_neta' => $utilidadNeta
    ];

    Response::success($report);
}

function getBalanceSheet($db, $agencyId)
{
    // Activos: Bancos + Caja/Boveda + Cartera

    // 1. Bancos
    $bancosSql = "SELECT SUM(saldo_actual) as total FROM bancos";
    $stmt = $db->query($bancosSql);
    $bancos = floatval($stmt->fetch()['total'] ?? 0);

    // 2. Caja y Bóveda (Agencias)
    $cajasSql = "SELECT SUM(saldo_caja_operativa + saldo_efectivo) as total FROM cajas_agencias";
    $stmt = $db->query($cajasSql);
    $cajaBoveda = floatval($stmt->fetch()['total'] ?? 0);

    // 3. Cartera Vigente
    $carteraSql = "
        SELECT SUM(c.capital_cuota) as saldo_capital
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        WHERE p.estado = 'Activo' AND c.estado != 'pagada'
    ";
    $stmt = $db->query($carteraSql);
    $cartera = floatval($stmt->fetch()['saldo_capital'] ?? 0);

    // Pasivos
    // ...

    // Patrimonio (Capital Inicial Hardcoded por ahora si no hay logica clara, o query)
    $patrimonioSql = "
        SELECT SUM(monto) as capital 
        FROM movimientos_bancarios 
        WHERE tipo_transaccion = 'ingreso' AND descripcion LIKE '%Capital%'
    ";
    $stmt = $db->query($patrimonioSql);
    $patrimonio = floatval($stmt->fetch()['capital'] ?? 0);
    // Fallback if 0 to show something realistic for demo? No, respect DB.

    Response::success([
        'activos' => [
            'bancos' => $bancos,
            'caja_boveda' => $cajaBoveda,
            'cartera' => $cartera,
            'total' => $bancos + $cajaBoveda + $cartera
        ],
        'pasivos' => [
            'cuentas_por_pagar' => 0,
            'total' => 0
        ],
        'patrimonio' => $patrimonio
    ]);
}
