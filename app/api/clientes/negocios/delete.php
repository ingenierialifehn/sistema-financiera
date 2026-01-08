<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();
    // Validate permission if needed, but for now allow logged in users (or restrict to admin/officer)

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        Response::error('ID del negocio es requerido', 400);
    }

    $db = getDB();
    $stmt = $db->prepare("DELETE FROM clientes_negocios WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() > 0) {
        Response::success([], 'Negocio eliminado correctamente');
    } else {
        Response::error('Negocio no encontrado o no se pudo eliminar', 404);
    }

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
