<?php
/**
 * Script: Verificar estructura de tablas
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Verificando estructura de tablas ===\n\n";

    // Verificar tabla usuarios
    echo "→ Tabla 'usuarios':\n";
    try {
        $stmt = $db->query("DESCRIBE usuarios");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n→ Tabla 'prestamos':\n";
    try {
        $stmt = $db->query("DESCRIBE prestamos");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n→ Tablas existentes:\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "   - $table\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
