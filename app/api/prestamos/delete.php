<?php
/**
 * API: Eliminar/cancelar préstamo
 * DELETE /app/api/prestamos/delete.php?id=1
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
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : null);
    
    if (!$id) {
        Response::error('ID de préstamo es requerido', 400);
    }
    
    $db = getDB();
    
    // Verificar que existe
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $prestamo = $stmt->fetch();
    
    if (!$prestamo) {
        Response::notFound('Préstamo no encontrado');
    }
    
    // Verificar si tiene pagos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM pagos WHERE prestamo_id = :id");
    $stmt->execute(['id' => $id]);
    $tienePagos = $stmt->fetch()['total'] > 0;
    
    if ($tienePagos) {
        // Solo cancelar, no eliminar
        $db->prepare("UPDATE prestamos SET estado = 'cancelado', updated_at = NOW() WHERE id = :id")
           ->execute(['id' => $id]);
        
        Auth::logActivity($user['id'], 'cancel', 'prestamos', "Préstamo cancelado: {$prestamo['numero_prestamo']}", $prestamo, null);
        
        Response::success(null, 'Préstamo cancelado exitosamente (no se puede eliminar porque tiene pagos)');
    } else {
        // Eliminar cuotas primero
        $db->prepare("DELETE FROM cuotas WHERE prestamo_id = :id")->execute(['id' => $id]);
        
        // Eliminar préstamo
        $db->prepare("DELETE FROM prestamos WHERE id = :id")->execute(['id' => $id]);
        
        Auth::logActivity($user['id'], 'delete', 'prestamos', "Préstamo eliminado: {$prestamo['numero_prestamo']}", $prestamo, null);
        
        Response::success(null, 'Préstamo eliminado exitosamente');
    }
    
} catch (Exception $e) {
    error_log("Error en prestamos/delete.php: " . $e->getMessage());
    Response::serverError('Error al eliminar préstamo');
}

