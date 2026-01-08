<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Update ENUM with new workflow states
    // We strictly follow the User's requested flow: 
    // Solicitado -> En Análisis -> Verificación de Campo -> Pendiente de Operaciones -> Aprobado
    // Plus existing: Activo (Disbursed), Finalizado (Paid), Rechazado

    // Note: 'Pendiente' was used before, keeping it just in case, but 'Solicitado' is the start now.
    $sql = "ALTER TABLE prestamos MODIFY COLUMN estado 
            ENUM('Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado', 'Pendiente', 'Activo', 'Finalizado', 'Rechazado') 
            DEFAULT 'Solicitado'";

    $db->exec($sql);
    echo "Estados de flujo de trabajo actualizados correctamente.<br>";

} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>