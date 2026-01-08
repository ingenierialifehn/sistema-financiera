<?php
require_once __DIR__ . '/../../app/config/database.php';

$db = getDB();

// Test simple insert
try {
    $testData = [
        'nombre_completo' => 'Test Cliente ' . time(),
        'numero_documento' => '0801' . rand(100000, 999999),
        'telefono' => '99887766',
        'tipo_documento' => 'DNI',
        'id_agencia' => 1
    ];

    $sql = "INSERT INTO clientes (nombre_completo, numero_documento, telefono, tipo_documento, id_agencia, estado) 
            VALUES (?, ?, ?, ?, ?, 'activo')";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $testData['nombre_completo'],
        $testData['numero_documento'],
        $testData['telefono'],
        $testData['tipo_documento'],
        $testData['id_agencia']
    ]);

    if ($result) {
        echo "✓ Test INSERT exitoso. ID: " . $db->lastInsertId() . "\n";
        echo "Datos insertados:\n";
        print_r($testData);
    } else {
        echo "✗ Error en INSERT\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>