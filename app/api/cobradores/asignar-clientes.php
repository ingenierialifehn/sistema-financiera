<?php
/**
 * Asignar clientes a un cobrador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (!isset($data['cobrador_id']) || empty($data['cobrador_id'])) {
    Response::error('ID de cobrador requerido', 400);
}

if (!isset($data['cliente_ids']) || !is_array($data['cliente_ids'])) {
    Response::error('IDs de clientes requeridos (array)', 400);
}

$cobradorId = intval($data['cobrador_id']);
$clienteIds = array_map('intval', $data['cliente_ids']);

try {
    // Verificar que el cobrador existe
    $checkStmt = $db->prepare("SELECT id, nombre_completo FROM usuarios WHERE id = :id AND rol = 'cobrador'");
    $checkStmt->execute(['id' => $cobradorId]);
    $cobrador = $checkStmt->fetch();
    
    if (!$cobrador) {
        Response::notFound('Cobrador no encontrado');
    }
    
    // Verificar que los clientes existen
    if (!empty($clienteIds)) {
        $placeholders = implode(',', array_fill(0, count($clienteIds), '?'));
        $clientesStmt = $db->prepare("SELECT id, nombre_completo FROM clientes WHERE id IN ($placeholders)");
        $clientesStmt->execute($clienteIds);
        $clientesExistentes = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($clientesExistentes) !== count($clienteIds)) {
            Response::error('Algunos clientes no existen', 400);
        }
    }
    
    // Actualizar clientes
    $placeholders = implode(',', array_fill(0, count($clienteIds), '?'));
    $updateStmt = $db->prepare("UPDATE clientes SET cobrador_id = ? WHERE id IN ($placeholders)");
    $updateParams = array_merge([$cobradorId], $clienteIds);
    $updateStmt->execute($updateParams);
    
    $totalAsignados = $updateStmt->rowCount();
    
    // Registrar actividad
    Auth::logActivity(
        $user['id'], 
        'update', 
        'cobrador', 
        "{$totalAsignados} clientes asignados a cobrador: {$cobrador['nombre_completo']}", 
        null, 
        ['cobrador_id' => $cobradorId, 'cliente_ids' => $clienteIds]
    );
    
    Response::success([
        'cobrador_id' => $cobradorId,
        'clientes_asignados' => $totalAsignados
    ], "{$totalAsignados} clientes asignados exitosamente");
    
} catch (Exception $e) {
    error_log("Error al asignar clientes: " . $e->getMessage());
    Response::serverError('Error al asignar clientes');
}

