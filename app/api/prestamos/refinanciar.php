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
        // Parámetros del nuevo préstamo (permitimos override o usamos por defecto del original)
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
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
    $stmt->execute(['id' => $data['prestamo_id']]);
    $original = $stmt->fetch();
    if (!$original) {
        Response::error('Préstamo original no encontrado', 404);
    }

    // Calcular saldo pendiente del original a partir de cuotas
    $stmt = $db->prepare("SELECT SUM(monto_cuota) AS total_cuotas, SUM(monto_pagado) AS total_pagado FROM cuotas WHERE prestamo_id = :pid");
    $stmt->execute(['pid' => $original['id']]);
    $sum = $stmt->fetch();
    $totalCuotas = floatval($sum['total_cuotas'] ?? 0);
    $totalPagado = floatval($sum['total_pagado'] ?? 0);
    $saldo = max(0.0, $totalCuotas - $totalPagado);

    if ($saldo <= 0) {
        Response::error('El préstamo original no tiene saldo pendiente', 409);
    }

    $montoRef = round($saldo * 0.5, 2); // 50%

    // Parámetros del nuevo préstamo
    $modalidadNueva = isset($data['modalidad']) && in_array($data['modalidad'], ['diario','semanal','catorcenal','mensual']) ? $data['modalidad'] : ($original['modalidad'] ?? 'mensual');
    $tasaNueva = isset($data['tasa_interes']) ? floatval($data['tasa_interes']) : floatval($original['tasa_interes']);
    $periodoNuevo = isset($data['periodo_meses']) ? intval($data['periodo_meses']) : intval($original['periodo_meses']);
    $fechaDesembolso = !empty($data['fecha_desembolso']) ? $data['fecha_desembolso'] : date('Y-m-d');
    $diaPago = isset($data['dia_pago']) ? intval($data['dia_pago']) : intval($original['dia_pago']);

    // Calcular montos del nuevo préstamo
    $montoTotalNuevo = PrestamoHelper::calculateMontoTotal($montoRef, $tasaNueva, $periodoNuevo);
    $numeroCuotasNuevo = PrestamoHelper::calculateNumeroCuotas($periodoNuevo, $modalidadNueva);
    $montoCuotaNueva = ($modalidadNueva === 'mensual')
        ? PrestamoHelper::calculateMontoCuota($montoTotalNuevo, $periodoNuevo)
        : PrestamoHelper::calculateMontoCuotaPorCuotas($montoTotalNuevo, $numeroCuotasNuevo);

    $fechaVencimientoNueva = PrestamoHelper::calcularUltimaFechaVencimiento(
        $periodoNuevo,
        $fechaDesembolso,
        $diaPago,
        $modalidadNueva
    );

    $db->beginTransaction();
    try {
        // Crear nuevo préstamo (similar a create.php), con columnas opcionales
        $colModalidad = false; $colTasaSug = false;
        $check = $db->query("SHOW COLUMNS FROM prestamos LIKE 'modalidad'");
        if ($check && $check->fetch()) { $colModalidad = true; }
        $check = $db->query("SHOW COLUMNS FROM prestamos LIKE 'tasa_interes_sugerida'");
        if ($check && $check->fetch()) { $colTasaSug = true; }

        $numeroPrestamo = generatePrestamoNumber();
        $stmt = $db->prepare("SELECT id FROM prestamos WHERE numero_prestamo = :numero");
        $stmt->execute(['numero' => $numeroPrestamo]);
        $tries = 0; while ($stmt->fetch() && $tries < 10) { $numeroPrestamo = generatePrestamoNumber(); $stmt->execute(['numero' => $numeroPrestamo]); $tries++; }

        $tasaSugerida = null;
        $map = [ 'diario' => 'tasa_diario', 'semanal' => 'tasa_semanal', 'catorcenal' => 'tasa_catorcenal', 'mensual' => 'tasa_mensual' ];
        $cfgKey = isset($map[$modalidadNueva]) ? $map[$modalidadNueva] : null;
        if ($cfgKey) { $tasaSugerida = getConfig($cfgKey, getConfig('tasa_interes_default', $tasaNueva)); }

        $cols = ['cliente_id','numero_prestamo','monto_prestado','tasa_interes','periodo_meses','monto_total','monto_cuota','fecha_desembolso','fecha_vencimiento','dia_pago','estado','observaciones','created_by'];
        $vals = [':cliente_id',':numero_prestamo',':monto_prestado',':tasa_interes',':periodo_meses',':monto_total',':monto_cuota',':fecha_desembolso',':fecha_vencimiento',':dia_pago',"'pendiente'",':observaciones',':created_by'];
        if ($colModalidad) { $cols[] = 'modalidad'; $vals[] = ':modalidad'; }
        if ($colTasaSug) { $cols[] = 'tasa_interes_sugerida'; $vals[] = ':tasa_interes_sugerida'; }

        $sql = "INSERT INTO prestamos (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
        $stmt = $db->prepare($sql);
        $params = [
            'cliente_id' => $original['cliente_id'],
            'numero_prestamo' => $numeroPrestamo,
            'monto_prestado' => $montoRef,
            'tasa_interes' => $tasaNueva,
            'periodo_meses' => $periodoNuevo,
            'monto_total' => round($montoTotalNuevo, 2),
            'monto_cuota' => round($montoCuotaNueva, 2),
            'fecha_desembolso' => $fechaDesembolso,
            'fecha_vencimiento' => $fechaVencimientoNueva,
            'dia_pago' => $diaPago,
            'observaciones' => !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : 'Refinanciamiento 50% del préstamo '.$original['numero_prestamo'],
            'created_by' => $user['id']
        ];
        if ($colModalidad) { $params['modalidad'] = $modalidadNueva; }
        if ($colTasaSug) { $params['tasa_interes_sugerida'] = $tasaSugerida; }
        $stmt->execute($params);

        $nuevoPrestamoId = $db->lastInsertId();

        // Generar cuotas del nuevo préstamo
        PrestamoHelper::generateCuotasModalidad(
            $nuevoPrestamoId,
            round($montoCuotaNueva, 2),
            $periodoNuevo,
            $fechaDesembolso,
            $diaPago,
            $modalidadNueva
        );

        // Cambiar estado a activo
        $db->prepare("UPDATE prestamos SET estado = 'activo' WHERE id = :id")->execute(['id' => $nuevoPrestamoId]);

        // Abonar al préstamo original el 50% como abono a capital distribuyendo a cuotas pendientes
        $stmt = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = :id AND estado IN ('pendiente','en_mora') ORDER BY numero_cuota ASC");
        $stmt->execute(['id' => $original['id']]);
        $cuotasPend = $stmt->fetchAll();

        $restante = $montoRef; // el nuevo préstamo cubre 50% del saldo

        // Registrar en abonos_capital
        $stmt = $db->prepare("INSERT INTO abonos_capital (prestamo_id, cliente_id, monto, fecha, observaciones, registrado_por) VALUES (:prestamo_id, :cliente_id, :monto, :fecha, :observaciones, :registrado_por)");
        $stmt->execute([
            'prestamo_id' => $original['id'],
            'cliente_id' => $original['cliente_id'],
            'monto' => round($montoRef, 2),
            'fecha' => $fechaDesembolso,
            'observaciones' => 'Abono por refinanciamiento 50%',
            'registrado_por' => $user['id']
        ]);

        foreach ($cuotasPend as $cuota) {
            if ($restante <= 0) break;
            $pagado = floatval($cuota['monto_pagado']);
            $cuotaMonto = floatval($cuota['monto_cuota']);
            $faltante = max(0, $cuotaMonto - $pagado);
            if ($faltante <= 0) continue;

            $aplicar = min($faltante, $restante);
            $nuevoPagado = round($pagado + $aplicar, 2);
            $nuevoEstado = ($nuevoPagado >= $cuotaMonto) ? 'pagada' : $cuota['estado'];

            $stmtUp = $db->prepare("UPDATE cuotas SET monto_pagado = :monto_pagado, estado = :estado, fecha_pago = CASE WHEN :estado = 'pagada' THEN :fecha ELSE fecha_pago END, updated_at = NOW() WHERE id = :id");
            $stmtUp->execute([
                'monto_pagado' => $nuevoPagado,
                'estado' => $nuevoEstado,
                'fecha' => $fechaDesembolso,
                'id' => $cuota['id']
            ]);

            $restante = round($restante - $aplicar, 2);
        }

        // Si original queda completamente pagado, marcar completado; si no, sigue activo
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas FROM cuotas WHERE prestamo_id = :pid");
        $stmt->execute(['pid' => $original['id']]);
        $info = $stmt->fetch();
        if ($info && $info['total'] == $info['pagadas']) {
            $db->prepare("UPDATE prestamos SET estado = 'completado', updated_at = NOW() WHERE id = :id")->execute(['id' => $original['id']]);
        } else {
            $db->prepare("UPDATE prestamos SET estado = 'activo', updated_at = NOW() WHERE id = :id")->execute(['id' => $original['id']]);
        }

        // Logs
        Auth::logActivity($user['id'], 'create', 'prestamos', 'Préstamo por refinanciamiento 50% creado: '.$numeroPrestamo, null, ['nuevo_prestamo_id' => $nuevoPrestamoId]);
        Auth::logActivity($user['id'], 'update', 'prestamos', 'Abono aplicado por refinanciamiento 50% al préstamo '.$original['numero_prestamo'], null, ['prestamo_id' => $original['id'], 'monto' => $montoRef]);

        $db->commit();

        // Respuesta
        $stmt = $db->prepare("SELECT p.*, c.nombre_completo as cliente_nombre, c.codigo_cliente FROM prestamos p INNER JOIN clientes c ON p.cliente_id = c.id WHERE p.id = :id");
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
