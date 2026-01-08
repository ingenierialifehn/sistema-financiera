<?php
/**
 * Agregar campos para asignación de personal en Operaciones
 * - asesor_creditos_id: Usuario que hará el cobro
 * - oficial_desembolsos_id: Usuario que hará la entrega
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Check if columns exist
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'asesor_creditos_id'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Agregando campos de asignación de personal...\n";

        $sql = "ALTER TABLE prestamos 
                ADD COLUMN asesor_creditos_id INT NULL COMMENT 'ID del asesor de créditos asignado para cobro' AFTER ruta_usuario_id,
                ADD COLUMN oficial_desembolsos_id INT NULL COMMENT 'ID del oficial de desembolsos asignado para entrega' AFTER asesor_creditos_id,
                ADD CONSTRAINT fk_prestamos_asesor FOREIGN KEY (asesor_creditos_id) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
                ADD CONSTRAINT fk_prestamos_oficial FOREIGN KEY (oficial_desembolsos_id) REFERENCES usuarios(id_usuario) ON DELETE SET NULL";

        $db->exec($sql);
        echo "✓ Campos agregados exitosamente.\n";
    } else {
        echo "Los campos ya existen.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>