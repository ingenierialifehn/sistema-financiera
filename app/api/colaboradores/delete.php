<?php
/**
 * Eliminar colaborador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = AuthMiddleware::requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

if (!isset($_GET['id'])) {
    Response::error('ID inválido', 400);
}

$id = intval($_GET['id']);

try {
    $db->beginTransaction();

    // Check dependency
    $stmt = $db->prepare("SELECT u.id as usuario_id, u.saldo_caja_virtual FROM colaboradores c LEFT JOIN usuarios u ON u.id_colaborador = c.id WHERE c.id = :id");
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data && $data['usuario_id']) {
        if ($data['saldo_caja_virtual'] > 0) {
            throw new Exception("No se puede eliminar. El usuario vinculado tiene saldo en caja: " . $data['saldo_caja_virtual']);
        }
        // Delete or deactivate user first
        $db->prepare("DELETE FROM usuarios WHERE id = :uid")->execute(['uid' => $data['usuario_id']]);
    }

    $db->prepare("DELETE FROM colaboradores WHERE id = :id")->execute(['id' => $id]);

    $db->commit();
    Auth::logActivity($user['id'], 'delete', 'colaborador', "Colaborador eliminado ID: $id");

    Response::success(null, 'Colaborador eliminado');

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    Response::error($e->getMessage(), 400);
}
