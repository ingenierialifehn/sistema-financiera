<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Modify the 'estado' column to include 'Solicitado'
    // We keep existing values to avoid data loss, just adding 'Solicitado'
    $sql = "ALTER TABLE prestamos MODIFY COLUMN estado ENUM('Solicitado', 'Pendiente', 'Activo', 'Finalizado', 'Rechazado') DEFAULT 'Solicitado'";

    $db->exec($sql);
    echo "Columna 'estado' actualizada exitosamente para incluir 'Solicitado'.<br>";

} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>