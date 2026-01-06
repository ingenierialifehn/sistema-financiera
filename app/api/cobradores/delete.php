<?php
/**
 * Eliminar/Desactivar cobrador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error('ID de cobrador requerido', 400);
}

$id = intval($_GET['id']);

// No permitir eliminar el propio usuario
if ($id == $user['id']) {
    Response::error('No puedes eliminar tu propio usuario', 400);
}

try {
    // Verificar que el cobrador existe
    $checkStmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id AND rol = 'cobrador'");
    $checkStmt->execute(['id' => $id]);
    $cobrador = $checkStmt->fetch();
    
    if (!$cobrador) {
        Response::notFound('Cobrador no encontrado');
    }
    
    // Verificar si tiene clientes asignados
    $clientesStmt = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE cobrador_id = :id");
    $clientesStmt->execute(['id' => $id]);
    $totalClientes = $clientesStmt->fetch()['total'];
    
    if ($totalClientes > 0) {
        // Si tiene clientes, solo desactivar en lugar de eliminar
        $stmt = $db->prepare("UPDATE usuarios SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Registrar actividad
        Auth::logActivity($user['id'], 'update', 'cobrador', "Cobrador desactivado (tiene {$totalClientes} clientes asignados): {$cobrador['nombre_completo']}", $cobrador, ['estado' => 'inactivo']);
        
        Response::success(null, "Cobrador desactivado exitosamente. Tiene {$totalClientes} clientes asignados.");
    } else {
        // Si no tiene clientes, eliminar completamente
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Registrar actividad
        Auth::logActivity($user['id'], 'delete', 'cobrador', "Cobrador eliminado: {$cobrador['nombre_completo']}", $cobrador, null);
        
        Response::success(null, 'Cobrador eliminado exitosamente');
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar cobrador: " . $e->getMessage());
    Response::serverError('Error al eliminar cobrador');
}

