<?php
/**
 * Test de login - Para diagnosticar errores
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    echo json_encode(['step' => 'config loaded']);
    
    require_once __DIR__ . '/../../config/database.php';
    echo json_encode(['step' => 'database loaded']);
    
    $db = getDB();
    echo json_encode(['step' => 'db connection OK']);
    
    // Test de query
    $stmt = $db->prepare("SELECT id, usuario FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(['usuario' => 'admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['step' => 'user found', 'user_id' => $user['id']]);
    } else {
        echo json_encode(['step' => 'user NOT found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

