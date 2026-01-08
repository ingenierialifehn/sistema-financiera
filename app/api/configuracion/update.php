<?php
/**
 * API: Actualizar configuración
 * POST /app/api/configuracion/update.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Solo admin
    $user = AuthMiddleware::requireAdmin();
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['configs']) || !is_array($input['configs'])) {
        Response::error('Formato inválido', 400);
    }

    $db->beginTransaction();

    foreach ($input['configs'] as $key => $value) {
        // Validar existencia
        $stmt = $db->prepare("SELECT id FROM configuraciones WHERE clave = :clave");
        $stmt->execute(['clave' => $key]);
        if (!$stmt->fetch()) {
            continue; // Ignorar claves desconocidas
        }

        $stmt = $db->prepare("UPDATE configuraciones SET valor = :valor, updated_at = NOW() WHERE clave = :clave");
        $stmt->execute(['valor' => $value, 'clave' => $key]);

        Auth::logActivity($user['id'], 'update', 'configuracion', "Actualizó configuración: $key a $value");
    }

    $db->commit();
    Response::success([], 'Configuración actualizada');

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction())
        $db->rollBack();
    error_log('Error en configuracion/update.php: ' . $e->getMessage());
    Response::serverError('Error al actualizar: ' . $e->getMessage());
}
