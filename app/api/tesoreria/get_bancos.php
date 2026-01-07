<?php
require_once '../../config/database.php';

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('tesoreria');

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM bancos ORDER BY id DESC");
    $bancos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $bancos]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
