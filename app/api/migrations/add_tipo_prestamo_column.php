<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'tipo_prestamo'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE prestamos ADD COLUMN tipo_prestamo ENUM('Nuevo', 'Refinanciamiento', 'Readecuacion', 'Represtamo') DEFAULT 'Nuevo' AFTER modalidad");
        echo "Column 'tipo_prestamo' added successfully to 'prestamos'.\n";
    } else {
        echo "Column 'tipo_prestamo' already exists in 'prestamos'.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>