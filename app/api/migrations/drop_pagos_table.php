<?php
/**
 * Script: Eliminar tabla pagos
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Eliminando tabla pagos ===\n\n";

    $db->exec("DROP TABLE IF EXISTS pagos");

    echo "✓ Tabla 'pagos' eliminada.\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
