<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, nombre_completo, usuario_id, cobrador_id FROM clientes LIMIT 20");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total clients found: " . count($clients) . "\n";
    foreach ($clients as $c) {
        echo "ID: " . $c['id'] .
            " | Name: " . $c['nombre_completo'] .
            " | UserID: " . ($c['usuario_id'] ?? 'NULL') .
            " | CobradorID: " . ($c['cobrador_id'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
