<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: text/plain');

try {
    // We cannot use AuthMiddleware::requireAuth() easily if running from browser without cookie/token if we just want to debug DB.
    // But let's try to verify the session if the user runs this.

    $db = getDB();

    echo "--- ROLES ---\n";
    $stmt = $db->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($roles);

    echo "\n--- USUARIOS ---\n";
    $stmt = $db->query("SELECT id_usuario, username, id_rol, estado FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);

    echo "\n--- CLIENTES SAMPLE ---\n";
    $stmt = $db->query("SELECT id, nombre_completo, id_agencia, cobrador_id FROM clientes LIMIT 5");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($clientes);

    echo "\n--- SESSION DEBUG ---\n";
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    print_r($_SESSION);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
