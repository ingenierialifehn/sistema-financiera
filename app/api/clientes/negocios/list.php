<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();
    $db = getDB();

    if (empty($_GET['cliente_id'])) {
        Response::error('ID de cliente requerido', 400);
    }

    $clienteId = $_GET['cliente_id'];

    // Obtener Negocios
    $stmt = $db->prepare("SELECT * FROM clientes_negocios WHERE cliente_id = ? ORDER BY created_at DESC");
    $stmt->execute([$clienteId]);
    $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener Garantías para cada negocio
    foreach ($negocios as &$negocio) {
        $stmtG = $db->prepare("SELECT * FROM negocios_garantias WHERE negocio_id = ?");
        $stmtG->execute([$negocio['id']]);
        $negocio['garantias'] = $stmtG->fetchAll(PDO::FETCH_ASSOC);

        // Calcular total
        $total = 0;
        foreach ($negocio['garantias'] as $g) {
            $total += floatval($g['valor']);
        }
        $negocio['total_garantias'] = $total;
    }

    Response::success($negocios);

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
