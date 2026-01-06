<?php
/**
 * API: Crear abono a capital
 * POST /app/api/prestamos/abonos_capital/create.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Validator.php';
require_once __DIR__ . '/../../../core/Helpers.php';
require_once __DIR__ . '/../../../core/Auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Admin o cobrador
    $user = AuthMiddleware::requireCobradorOrAdmin();

    $input = getJsonInput();

    $validation = Validator::validate($input, [
        'prestamo_id' => [
            'type' => 'integer',
            'required' => true,
            'message' => 'ID de préstamo es requerido'
        ],
        'monto' => [
            'type' => 'number',
            'required' => true,
            'min' => 0.01,
            'message' => 'Monto es requerido (mínimo 0.01)'
        ],
        'fecha' => [
            'type' => 'date',
            'required' => true,
            'message' => 'Fecha es requerida'
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

    // Verificar préstamo
    $stmt = $db->prepare("SELECT p.*, c.id as cliente_id FROM prestamos p INNER JOIN clientes c ON p.cliente_id = c.id WHERE p.id = :id");
    $stmt->execute(['id' => $data['prestamo_id']]);
    $prestamo = $stmt->fetch();
    if (!$prestamo) {
        Response::error('Préstamo no encontrado', 404);
    }

    // Obtener cuotas pendientes ordenadas por numero_cuota ASC
    $stmt = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = :id AND estado IN ('pendiente','en_mora') ORDER BY numero_cuota ASC");
    $stmt->execute(['id' => $data['prestamo_id']]);
    $cuotas = $stmt->fetchAll();

    if (!$cuotas) {
        Response::error('No hay cuotas pendientes para aplicar el abono', 409);
    }

    $monto = round(floatval($data['monto']), 2);

    $db->beginTransaction();
    try {
        // Insertar abono
        $stmt = $db->prepare("INSERT INTO abonos_capital (prestamo_id, cliente_id, monto, fecha, observaciones, registrado_por) VALUES (:prestamo_id, :cliente_id, :monto, :fecha, :observaciones, :registrado_por)");
        $stmt->execute([
            'prestamo_id' => $prestamo['id'],
            'cliente_id' => $prestamo['cliente_id'],
            'monto' => $monto,
            'fecha' => $data['fecha'],
            'observaciones' => !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : null,
            'registrado_por' => $user['id']
        ]);
        $abonoId = $db->lastInsertId();

        // Aplicar abono incrementando monto_pagado de cuotas futuras
        $restante = $monto;
        foreach ($cuotas as $cuota) {
            if ($restante <= 0) break;
            $pagado = floatval($cuota['monto_pagado']);
            $cuotaMonto = floatval($cuota['monto_cuota']);
            $faltante = max(0, $cuotaMonto - $pagado);
            if ($faltante <= 0) continue;

            $aplicar = min($faltante, $restante);
            $nuevoPagado = round($pagado + $aplicar, 2);
            $nuevoEstado = ($nuevoPagado >= $cuotaMonto) ? 'pagada' : $cuota['estado'];

            $stmtUp = $db->prepare("UPDATE cuotas SET monto_pagado = :monto_pagado, estado = :estado, updated_at = NOW() WHERE id = :id");
            $stmtUp->execute([
                'monto_pagado' => $nuevoPagado,
                'estado' => $nuevoEstado,
                'id' => $cuota['id']
            ]);
            
            // Si la cuota fue pagada completamente, actualizar la fecha de pago
            if ($nuevoEstado === 'pagada') {
                $stmtFecha = $db->prepare("UPDATE cuotas SET fecha_pago = :fecha_pago WHERE id = :id");
                $stmtFecha->execute([
                    'fecha_pago' => $data['fecha'],
                    'id' => $cuota['id']
                ]);
            }

            $restante = round($restante - $aplicar, 2);
        }

        // Verificar si todas las cuotas están pagadas
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas FROM cuotas WHERE prestamo_id = :pid");
        $stmt->execute(['pid' => $prestamo['id']]);
        $info = $stmt->fetch();
        if ($info && $info['total'] == $info['pagadas']) {
            $db->prepare("UPDATE prestamos SET estado = 'completado', updated_at = NOW() WHERE id = :id")->execute(['id' => $prestamo['id']]);
        }

        $db->commit();

        // Obtener abono creado
        $stmt = $db->prepare("SELECT * FROM abonos_capital WHERE id = :id");
        $stmt->execute(['id' => $abonoId]);
        $abono = $stmt->fetch();

        Auth::logActivity($user['id'], 'create', 'abonos_capital', "Abono a capital registrado: S/ {$monto}", null, $abono);
        Response::success($abono, 'Abono a capital registrado exitosamente', 201);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en abonos_capital/create.php: " . $e->getMessage());
    Response::serverError('Error al registrar abono a capital: ' . $e->getMessage());
}
