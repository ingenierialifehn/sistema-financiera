<?php
/**
 * API: Crear pago
 * POST /app/api/pagos/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Admin o cobrador pueden crear pagos
    $user = AuthMiddleware::requireCobradorOrAdmin();
    
    $input = getJsonInput();
    
    // Validar datos
    $validation = Validator::validate($input, [
        'cuota_id' => [
            'type' => 'integer',
            'required' => true,
            'message' => 'ID de cuota es requerido'
        ],
        'monto_pagado' => [
            'type' => 'number',
            'required' => true,
            'min' => 0.01,
            'message' => 'Monto pagado es requerido (mínimo 0.01)'
        ],
        'fecha_pago' => [
            'type' => 'date',
            'required' => true,
            'message' => 'Fecha de pago es requerida'
        ],
        'metodo_pago' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Método de pago inválido'
        ],
        'comprobante_url' => [
            'type' => 'string',
            'required' => false,
            'max' => 255,
            'message' => 'URL de comprobante inválida'
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
    
    // Verificar que la cuota existe y obtener información
    $stmt = $db->prepare("
        SELECT cu.*, p.cliente_id, p.id as prestamo_id, p.numero_prestamo
        FROM cuotas cu
        INNER JOIN prestamos p ON cu.prestamo_id = p.id
        WHERE cu.id = :cuota_id
    ");
    $stmt->execute(['cuota_id' => $data['cuota_id']]);
    $cuota = $stmt->fetch();
    
    if (!$cuota) {
        Response::error('Cuota no encontrada', 404);
    }
    
    // Verificar que la cuota no esté pagada completamente
    $montoPendiente = floatval($cuota['monto_cuota']) - floatval($cuota['monto_pagado']);
    $montoPagado = floatval($data['monto_pagado']);
    
    if ($montoPendiente <= 0) {
        Response::error('La cuota ya está pagada completamente', 409);
    }
    
    // Calcular mora si aplica
    $mora = calculateMora($cuota['fecha_vencimiento'], $cuota['monto_cuota']);
    $montoMora = $mora['monto'];
    
    // Validar método de pago
    $metodoPago = isset($data['metodo_pago']) ? $data['metodo_pago'] : 'efectivo';
    if (!in_array($metodoPago, ['efectivo', 'transferencia', 'deposito', 'otro'])) {
        $metodoPago = 'efectivo';
    }
    
    $db->beginTransaction();
    
    try {
        // Insertar pago
        $stmt = $db->prepare("
            INSERT INTO pagos (
                cuota_id, prestamo_id, cliente_id, monto_pagado, monto_mora,
                fecha_pago, metodo_pago, comprobante_url, observaciones,
                cobrado_por, estado
            ) VALUES (
                :cuota_id, :prestamo_id, :cliente_id, :monto_pagado, :monto_mora,
                :fecha_pago, :metodo_pago, :comprobante_url, :observaciones,
                :cobrado_por, 'confirmado'
            )
        ");
        
        $stmt->execute([
            'cuota_id' => $data['cuota_id'],
            'prestamo_id' => $cuota['prestamo_id'],
            'cliente_id' => $cuota['cliente_id'],
            'monto_pagado' => $montoPagado,
            'monto_mora' => round($montoMora, 2),
            'fecha_pago' => $data['fecha_pago'],
            'metodo_pago' => $metodoPago,
            'comprobante_url' => !empty($data['comprobante_url']) ? Validator::sanitize($data['comprobante_url']) : null,
            'observaciones' => !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : null,
            'cobrado_por' => $user['id']
        ]);
        
        $pagoId = $db->lastInsertId();
        
        // Actualizar cuota
        $nuevoMontoPagado = floatval($cuota['monto_pagado']) + $montoPagado;
        $nuevoEstado = ($nuevoMontoPagado >= floatval($cuota['monto_cuota'])) ? 'pagada' : $cuota['estado'];
        
        $stmt = $db->prepare("
            UPDATE cuotas 
            SET monto_pagado = :monto_pagado,
                estado = :nuevo_estado,
                dias_mora = :dias_mora,
                monto_mora = :monto_mora,
                updated_at = NOW()
            WHERE id = :cuota_id
        ");
        
        $stmt->execute([
            'monto_pagado' => round($nuevoMontoPagado, 2),
            'nuevo_estado' => $nuevoEstado,
            'dias_mora' => $mora['dias'],
            'monto_mora' => round($montoMora, 2),
            'cuota_id' => $data['cuota_id']
        ]);
        
        // Si la cuota fue pagada completamente, actualizar la fecha de pago
        if ($nuevoEstado === 'pagada') {
            $stmt = $db->prepare("
                UPDATE cuotas 
                SET fecha_pago = :fecha_pago
                WHERE id = :cuota_id
            ");
            $stmt->execute([
                'fecha_pago' => $data['fecha_pago'],
                'cuota_id' => $data['cuota_id']
            ]);
        }
        
        // Verificar si todas las cuotas están pagadas para actualizar estado del préstamo
        $stmt = $db->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas
            FROM cuotas 
            WHERE prestamo_id = :prestamo_id
        ");
        $stmt->execute(['prestamo_id' => $cuota['prestamo_id']]);
        $cuotasInfo = $stmt->fetch();
        
        if ($cuotasInfo['total'] == $cuotasInfo['pagadas']) {
            $db->prepare("UPDATE prestamos SET estado = 'completado', updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $cuota['prestamo_id']]);
        }
        
        $db->commit();
        
        // Obtener pago creado
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.nombre_completo as cliente_nombre,
                c.codigo_cliente,
                pr.numero_prestamo,
                cu.numero_cuota,
                u.nombre_completo as cobrador_nombre
            FROM pagos p
            INNER JOIN clientes c ON p.cliente_id = c.id
            INNER JOIN prestamos pr ON p.prestamo_id = pr.id
            INNER JOIN cuotas cu ON p.cuota_id = cu.id
            LEFT JOIN usuarios u ON p.cobrado_por = u.id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $pagoId]);
        $pago = $stmt->fetch();
        
        // Registrar log
        Auth::logActivity($user['id'], 'create', 'pagos', "Pago registrado: S/ {$montoPagado}", null, $pago);
        
        Response::success($pago, 'Pago registrado exitosamente', 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en pagos/create.php: " . $e->getMessage());
    Response::serverError('Error al registrar pago: ' . $e->getMessage());
}

