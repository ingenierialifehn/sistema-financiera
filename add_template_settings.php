<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

try {
    // Check if columns exist first
    $stmt = $db->query("SHOW COLUMNS FROM plantillas_documentos LIKE 'centrado'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE plantillas_documentos ADD COLUMN centrado TINYINT(1) DEFAULT 0");
        echo "✅ Columna 'centrado' agregada\n";
    } else {
        echo "ℹ️  Columna 'centrado' ya existe\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM plantillas_documentos LIKE 'tamano_papel'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE plantillas_documentos ADD COLUMN tamano_papel VARCHAR(20) DEFAULT 'carta'");
        echo "✅ Columna 'tamano_papel' agregada\n";
    } else {
        echo "ℹ️  Columna 'tamano_papel' ya existe\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>