<?php
/**
 * Agregar campos para el flujo de desembolsos
 */
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "Agregando campos para desembolsos...\n";

    // Agregar campos a la tabla prestamos
    $db->exec("
        ALTER TABLE prestamos 
        ADD COLUMN IF NOT EXISTS id_desembolsador INT NULL COMMENT 'Usuario asignado para entregar el dinero',
        ADD COLUMN IF NOT EXISTS id_asesor INT NULL COMMENT 'Asesor de créditos asignado',
        ADD COLUMN IF NOT EXISTS fecha_asignacion_desembolso DATETIME NULL COMMENT 'Fecha de asignación al desembolsador',
        ADD COLUMN IF NOT EXISTS fecha_entrega_fisica DATETIME NULL COMMENT 'Fecha de entrega física del dinero',
        ADD COLUMN IF NOT EXISTS id_usuario_entrega INT NULL COMMENT 'Usuario que confirmó la entrega'
    ");

    echo "✓ Campos agregados exitosamente\n";

    // Agregar índices
    $db->exec("
        ALTER TABLE prestamos 
        ADD INDEX IF NOT EXISTS idx_desembolsador (id_desembolsador),
        ADD INDEX IF NOT EXISTS idx_asesor (id_asesor)
    ");

    echo "✓ Índices creados\n";
    echo "\n¡Actualización completada!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
