<?php
/**
 * API: Eliminar cliente
 * DELETE /app/api/clientes/delete.php?id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación (solo admin puede eliminar)
    $user = AuthMiddleware::requireAdmin();

    // Obtener ID del query string o del body
    $input = getJsonInput();
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : null);

    if (!$id) {
        Response::error('ID de cliente es requerido', 400);
    }

    $db = getDB();

    // Verificar que el cliente existe
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        Response::notFound('Cliente no encontrado');
    }

    // Verificar si tiene préstamos activos
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM prestamos 
        WHERE cliente_id = :id 
        AND estado IN ('activo', 'pendiente')
    ");
    $stmt->execute(['id' => $id]);
    $prestamosActivos = $stmt->fetch()['total'];

    if ($prestamosActivos > 0) {
        Response::error('No se puede eliminar el cliente porque tiene préstamos activos', 409);
    }

    // Eliminar cliente
    $stmt = $db->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $id]);

    // Registrar log
    Auth::logActivity($user['id_usuario'], 'delete', 'clientes', "Cliente eliminado: {$cliente['nombre_completo']}", $cliente, null);

    Response::success(null, 'Cliente eliminado exitosamente');

} catch (Exception $e) {
    error_log("Error en clientes/delete.php: " . $e->getMessage());
    Response::serverError('Error al eliminar cliente');
}

