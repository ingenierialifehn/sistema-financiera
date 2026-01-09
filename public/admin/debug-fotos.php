<?php
/**
 * Script de diagnóstico para verificar campos de fotos en clientes
 */

require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Obtener estructura de la tabla clientes
    $stmt = $db->query("DESCRIBE clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtrar solo campos que contengan 'foto'
    $fotoFields = array_filter($columns, function ($col) {
        return stripos($col['Field'], 'foto') !== false;
    });

    // Obtener un cliente de ejemplo
    $stmt = $db->query("SELECT * FROM clientes LIMIT 1");
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'campos_foto' => array_values($fotoFields),
        'cliente_ejemplo' => $cliente,
        'todos_los_campos' => array_column($columns, 'Field')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
