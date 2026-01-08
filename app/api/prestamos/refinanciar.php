<?php
/**
 * API: Refinanciar préstamo al 50%
 * POST /app/api/prestamos/refinanciar.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/PrestamoHelper.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Solo admin
    $user = AuthMiddleware::requireAdmin();

    $input = getJsonInput();

    $validation = Validator::validate($input, [
        'prestamo_id' => [
            'type' => 'integer',
            'required' => true,
            'message' => 'ID de préstamo es requerido'
        ],
        // Parámetros del nuevo préstamo
        'modalidad' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Modalidad inválida'
        ],
        'tasa_interes' => [
            'type' => 'number',
            'required' => false,
            'min' => 0,
            'max' => 100,
            'message' => 'Tasa inválida'
        ],
        'periodo_meses' => [
            'type' => 'integer',
            'required' => false,
            'min' => 1,
            'max' => 120,
            'message' => 'Periodo inválido (1-120)'
        ],
        'fecha_desembolso' => [
            'type' => 'date',
            'required' => false,
            'message' => 'Fecha de desembolso inválida'
        ],
        'dia_pago' => [
            'type' => 'integer',
            'required' => false,
            'min' => 1,
            'max' => 28,
            'message' => 'Día de pago inválido (1-28)'
        ],
        'observaciones' => [
            'type' => 'string',
            'required' => false,
            'max' => 500,
            'message' => 'Observaciones inválidas'
        ]
    ]);

    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }

    $data = $validation['data'];
    $db = getDB();

    // Obtener préstamo original
    // Use correct columns: id_cliente, monto_capital, total_a_pagar, modalidad, plazo_meses, tasa_total
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
    $stmt->execute(['id' => $data['prestamo_id']]);
    $original = $stmt->fetch();
    if (!$original) {
        Response::error('Préstamo original no encontrado', 404);
    }

    // Calcular saldo pendiente
    // Logic: Capital Restante = monto_capital - amortized_capital_from_cuotas
    $stmt = $db->prepare("
        SELECT 
            p.monto_capital,
            IFNULL((
                SELECT SUM(c.monto_pagado * (c.capital_cuota / c.monto_cuota)) 
                FROM cuotas c
                WHERE c.prestamo_id = p.id 
                AND c.estado IN ('pagada', 'parcial')
                AND c.monto_cuota > 0
            ), 0) as capital_amortizado,
             IFNULL((
                SELECT SUM(c.monto_pagado) 
                FROM cuotas c
                WHERE c.prestamo_id = p.id 
            ), 0) as total_pagado_real
        FROM prestamos p
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $original['id']]);
    $calc = $stmt->fetch();

    $saldo = max(0.0, floatval($calc['monto_capital']) - floatval($calc['capital_amortizado']));

    if ($saldo <= 0) {
        Response::error('El préstamo original no tiene saldo pendiente', 409);
    }

    // Validar porcentaje mínimo pagado
    $minPorcentaje = getConfig('refinanciamiento_min_pagado_porcentaje', 50);
    $totalAPagar = floatval($original['total_a_pagar']);
    $totalPagadoReal = floatval($calc['total_pagado_real'] ?? 0);

    $porcentajePagado = ($totalAPagar > 0) ? ($totalPagadoReal / $totalAPagar) * 100 : 0;

    if ($porcentajePagado < $minPorcentaje) {
        Response::error("No cumple con el requisito de refinanciamiento. Se requiere haber pagado el {$minPorcentaje}% del total del crédito. (Actual: " . number_format($porcentajePagado, 2) . "%)", 400);
    }

    $montoRef = round($saldo * 0.5, 2); // 50%

    // Parámetros del nuevo préstamo
    $modalidadNueva = isset($data['modalidad']) && in_array($data['modalidad'], ['Diario', 'Semanal', 'Catorcenal', 'Mensual']) ? $data['modalidad'] : ($original['modalidad'] ?? 'Mensual');

    // Tasa Handling
    // If input tasa_interes is provided, we assume it overrides the TOTAL rate logic or just the base interest?
    // Matching create.php logic: tasa_total is the main driver.
    // If user sends tasa_interes, we update tasa_interes component and recalculate tasa_total?
    // For simplicity, we assume input 'tasa_interes' maps to 'tasa_total' if simplified, OR we keep original structure.
    // Let's copy structure.
    $tasaInteresBase = floatval($original['tasa_interes']);
    $tasaGastos = floatval($original['tasa_gastos']);
    $tasaComision = floatval($original['tasa_comision']);

    // Check if user provided a new rate (we assume it replaces base interest)
    if (isset($data['tasa_interes'])) {
        $tasaInteresBase = floatval($data['tasa_interes']);
    }

    $tasaTotal = $tasaInteresBase + $tasaGastos + $tasaComision;

    $plazoNuevo = isset($data['periodo_meses']) ? intval($data['periodo_meses']) : intval($original['plazo_meses']);
    $fechaDesembolso = !empty($data['fecha_desembolso']) ? $data['fecha_desembolso'] : date('Y-m-d');
    $diaPago = isset($data['dia_pago']) ? intval($data['dia_pago']) : date('d', strtotime($fechaDesembolso));

    // Calculate Totals using create.php logic (Simple Interest per month logic implied)
    // create.php: $totalInteresMonto = $monto * ($tasaTotal / 100) * $plazoMeses;
    $totalInteresMonto = $montoRef * ($tasaTotal / 100) * $plazoNuevo;
    $montoTotalNuevo = $montoRef + $totalInteresMonto;

    // Calculate Cuota
    $numCuotas = PrestamoHelper::calculateNumeroCuotas($plazoNuevo, $modalidadNueva);
    $montoCuotaNueva = $montoTotalNuevo / $numCuotas;

    $db->beginTransaction();
    try {
        // Insert new loan
        $sql = "INSERT INTO prestamos (
            id_cliente, 
            asesor_creditos_id, 
            monto_capital, 
            neto_entregar,
            modalidad, 
            plazo_meses, 
            tasa_total, 
            tasa_interes, 
            tasa_gastos, 
            tasa_comision,
            valor_cuota, 
            total_a_pagar, 
            estado, 
            fecha_solicitud, 
            fecha_desembolso,
            observaciones,
            tipo_prestamo
        ) VALUES (
            :id_cliente, 
            :asesor_id, 
            :monto_capital, 
            :neto_entregar,
            :modalidad, 
            :plazo_meses, 
            :tasa_total, 
            :tasa_interes, 
            :tasa_gastos, 
            :tasa_comision,
            :valor_cuota, 
            :total_a_pagar, 
            'Activo', 
            NOW(), 
            :fecha_desembolso,
            :observaciones,
            'Refinanciamiento'
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_cliente' => $original['id_cliente'],
            'asesor_id' => $user['id'], // Assuming current user is the advisor/creator
            'monto_capital' => $montoRef,
            'neto_entregar' => $montoRef, // Refinance amount is fully credited/disbursed logically
            'modalidad' => $modalidadNueva,
            'plazo_meses' => $plazoNuevo,
            'tasa_total' => $tasaTotal,
            'tasa_interes' => $tasaInteresBase,
            'tasa_gastos' => $tasaGastos,
            'tasa_comision' => $tasaComision,
            'valor_cuota' => round($montoCuotaNueva, 2),
            'total_a_pagar' => round($montoTotalNuevo, 2),
            'fecha_desembolso' => $fechaDesembolso,
            'observaciones' => !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : 'Refinanciamiento 50% del préstamo #' . $original['id']
        ]);

        $nuevoPrestamoId = $db->lastInsertId();

        // Generar cuotas del nuevo préstamo
        PrestamoHelper::generateCuotasModalidad(
            $db,
            $nuevoPrestamoId,
            round($montoCuotaNueva, 2),
            $plazoNuevo,
            $fechaDesembolso,
            $diaPago,
            $modalidadNueva
        );

        // Abonar al préstamo original 50%
        // We record this as an abono_capital
        $stmt = $db->prepare("INSERT INTO abonos_capital (prestamo_id, cliente_id, monto, fecha, observaciones, registrado_por) VALUES (:prestamo_id, :cliente_id, :monto, :fecha, :observaciones, :registrado_por)");
        $stmt->execute([
            'prestamo_id' => $original['id'],
            'cliente_id' => $original['id_cliente'],
            'monto' => round($montoRef, 2),
            'fecha' => $fechaDesembolso,
            'observaciones' => 'Abono por refinanciamiento 50% (Nuevo Préstamo #' . $nuevoPrestamoId . ')',
            'registrado_por' => $user['id']
        ]);

        // Distribution of payment to pending cuotas
        $stmt = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = :id AND estado IN ('pendiente','en_mora') ORDER BY numero_cuota ASC");
        $stmt->execute(['id' => $original['id']]);
        $cuotasPend = $stmt->fetchAll();

        $restante = $montoRef;

        foreach ($cuotasPend as $cuota) {
            if ($restante <= 0)
                break;
            $pagado = floatval($cuota['monto_pagado']);
            $cuotaMonto = floatval($cuota['monto_cuota']);
            $faltante = max(0, $cuotaMonto - $pagado);
            if ($faltante <= 0)
                continue;

            $aplicar = min($faltante, $restante);
            $nuevoPagado = round($pagado + $aplicar, 2);
            $nuevoEstado = ($nuevoPagado >= $cuotaMonto) ? 'pagada' : $cuota['estado'];

            $stmtUp = $db->prepare("UPDATE cuotas SET monto_pagado = :monto_pagado, estado = :estado, fecha_pago = CASE WHEN :estado = 'pagada' THEN :fecha ELSE fecha_pago END, updated_at = NOW() WHERE id = :id");
            $stmtUp->execute([
                'monto_pagado' => $nuevoPagado,
                'estado' => $nuevoEstado,
                'fecha' => $fechaDesembolso, // Use disbursement date as payment date
                'id' => $cuota['id']
            ]);

            $restante = round($restante - $aplicar, 2);
        }

        // Si el préstamo original se paga completo
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas FROM cuotas WHERE prestamo_id = :pid");
        $stmt->execute(['pid' => $original['id']]);
        $info = $stmt->fetch();
        if ($info && $info['total'] == $info['pagadas']) {
            $db->prepare("UPDATE prestamos SET estado = 'Finalizado', updated_at = NOW() WHERE id = :id")->execute(['id' => $original['id']]);
        }
        // Original stays 'Activo' or whatever status it was if not fully paid

        // Logs
        Auth::logActivity($user['id'], 'create', 'prestamos', 'Préstamo por refinanciamiento 50% creado: #' . $nuevoPrestamoId, null, ['nuevo_prestamo_id' => $nuevoPrestamoId]);
        Auth::logActivity($user['id'], 'update', 'prestamos', 'Abono aplicado por refinanciamiento 50% al préstamo #' . $original['id'], null, ['prestamo_id' => $original['id'], 'monto' => $montoRef]);

        $db->commit();

        // Fetch new loan for response
        $stmt = $db->prepare("SELECT p.*, c.nombre_completo as cliente_nombre FROM prestamos p INNER JOIN clientes c ON p.id_cliente = c.id WHERE p.id = :id");
        $stmt->execute(['id' => $nuevoPrestamoId]);
        $nuevo = $stmt->fetch();

        Response::success([
            'prestamo_nuevo' => $nuevo,
            'monto_refinanciado' => round($montoRef, 2),
            'saldo_original_antes' => round($saldo, 2)
        ], 'Refinanciamiento 50% realizado exitosamente');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error en prestamos/refinanciar.php: ' . $e->getMessage());
    Response::serverError('Error en refinanciamiento: ' . $e->getMessage());
}
