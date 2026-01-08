<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    echo "Actualizando tabla cuotas...\n";

    // Add columns if not exist logic is hard in pure SQL without store procedures in MySQL sometimes, 
    // but simple ALTER IGNORE or catch exception works.

    try {
        $db->exec("ALTER TABLE cuotas ADD COLUMN fecha_pago_real DATETIME NULL");
        echo "Columna fecha_pago_real agregada.\n";
    } catch (PDOException $e) {
        echo "Columna fecha_pago_real ya existe o error: " . $e->getMessage() . "\n";
    }

    try {
        $db->exec("ALTER TABLE cuotas ADD COLUMN usuario_cobro_id INT NULL");
        echo "Columna usuario_cobro_id agregada.\n";
    } catch (PDOException $e) {
        echo "Columna usuario_cobro_id ya existe o error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
}
?>