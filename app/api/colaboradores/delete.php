<?php
/**
 * Eliminar/Desactivar colaborador
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
    Response::error('ID de colaborador requerido', 400);
}

$id = intval($_GET['id']);

// No permitir eliminar el propio usuario
if ($id == $user['id']) {
    Response::error('No puedes eliminar tu propio usuario', 400);
}

try {
    // Verificar que el colaborador existe
    $checkStmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id AND rol = 'colaborador'");
    $checkStmt->execute(['id' => $id]);
    $colaborador = $checkStmt->fetch();
    
    if (!$colaborador) {
        Response::notFound('Colaborador no encontrado');
    }
    
    // Verificar si tiene registros vinculados (prestamos creados o pagos cobrados)
    $prestamosStmt = $db->prepare("SELECT COUNT(*) as total FROM prestamos WHERE created_by = :id");
    $prestamosStmt->execute(['id' => $id]);
    $totalPrestamos = $prestamosStmt->fetch()['total'];

    $pagosStmt = $db->prepare("SELECT COUNT(*) as total FROM pagos WHERE cobrado_por = :id");
    $pagosStmt->execute(['id' => $id]);
    $totalPagos = $pagosStmt->fetch()['total'];
    
    $isReferenced = ($totalPrestamos > 0 || $totalPagos > 0);
    
    if ($isReferenced) {
        // Si tiene registros vinculados, solo desactivar en lugar de eliminar
        $stmt = $db->prepare("UPDATE usuarios SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Registrar actividad
        Auth::logActivity($user['id'], 'update', 'colaborador', "Colaborador desactivado (tiene referencias): {$colaborador['nombre_completo']}", $colaborador, ['estado' => 'inactivo']);
        
        Response::success(null, "Colaborador desactivado exitosamente (tiene registros asociados).");
    } else {
        // Si no tiene registros, eliminar completamente
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Registrar actividad
        Auth::logActivity($user['id'], 'delete', 'colaborador', "Colaborador eliminado: {$colaborador['nombre_completo']}", $colaborador, null);
        
        Response::success(null, 'Colaborador eliminado exitosamente');
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar colaborador: " . $e->getMessage());
    Response::serverError('Error al eliminar colaborador');
}
