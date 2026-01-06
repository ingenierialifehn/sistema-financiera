<?php
/**
 * API: Eliminar pago
 * DELETE /app/api/pagos/delete.php?id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

try {
    // Solo admin puede eliminar
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : null);
    
    if (!$id) {
        Response::error('ID de pago es requerido', 400);
    }
    
    $db = getDB();
    
    // Verificar que existe y obtener información
    $stmt = $db->prepare("
        SELECT p.*, cu.id as cuota_id, cu.monto_pagado as cuota_monto_pagado, cu.monto_cuota
        FROM pagos p
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $pago = $stmt->fetch();
    
    if (!$pago) {
        Response::notFound('Pago no encontrado');
    }
    
    $db->beginTransaction();
    
    try {
        // Revertir pago en la cuota
        $nuevoMontoPagado = floatval($pago['cuota_monto_pagado']) - floatval($pago['monto_pagado']);
        $nuevoEstado = ($nuevoMontoPagado <= 0) ? 'pendiente' : 
                       (($nuevoMontoPagado >= floatval($pago['monto_cuota'])) ? 'pagada' : 'pendiente');
        
        $stmt = $db->prepare("
            UPDATE cuotas 
            SET monto_pagado = :monto_pagado,
                estado = :estado,
                fecha_pago = CASE WHEN :estado = 'pendiente' THEN NULL ELSE fecha_pago END,
                updated_at = NOW()
            WHERE id = :cuota_id
        ");
        
        $stmt->execute([
            'monto_pagado' => max(0, round($nuevoMontoPagado, 2)),
            'estado' => $nuevoEstado,
            'cuota_id' => $pago['cuota_id']
        ]);
        
        // Eliminar pago
        $db->prepare("DELETE FROM pagos WHERE id = :id")->execute(['id' => $id]);
        
        // Verificar estado del préstamo
        $stmt = $db->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas
            FROM cuotas 
            WHERE prestamo_id = :prestamo_id
        ");
        $stmt->execute(['prestamo_id' => $pago['prestamo_id']]);
        $cuotasInfo = $stmt->fetch();
        
        if ($cuotasInfo['total'] != $cuotasInfo['pagadas']) {
            $db->prepare("UPDATE prestamos SET estado = 'activo', updated_at = NOW() WHERE id = :id")
               ->execute(['id' => $pago['prestamo_id']]);
        }
        
        $db->commit();
        
        Auth::logActivity($user['id'], 'delete', 'pagos', "Pago eliminado: ID {$id}", $pago, null);
        
        Response::success(null, 'Pago eliminado exitosamente');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en pagos/delete.php: " . $e->getMessage());
    Response::serverError('Error al eliminar pago');
}

