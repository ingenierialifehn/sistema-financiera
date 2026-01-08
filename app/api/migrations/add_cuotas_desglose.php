<?php
/**
 * Migración: Agregar campos de desglose a la tabla cuotas
 * Fecha: 2026-01-08
 * Descripción: Agrega capital_cuota, interes_cuota, gastos_cuota y comision_cuota
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Iniciando migración: Agregar campos de desglose a cuotas ===\n\n";

    // Verificar si las columnas ya existen
    $stmt = $db->query("SHOW COLUMNS FROM cuotas LIKE 'capital_cuota'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✓ Los campos ya existen en la tabla cuotas.\n";
    } else {
        echo "→ Agregando campos de desglose a la tabla cuotas...\n";

        $sql = "
            ALTER TABLE cuotas
            ADD COLUMN capital_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de capital en esta cuota',
            ADD COLUMN interes_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de interés en esta cuota',
            ADD COLUMN gastos_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de gastos financieros en esta cuota',
            ADD COLUMN comision_cuota DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de comisión de papelería en esta cuota'
        ";

        $db->exec($sql);
        echo "✓ Campos agregados exitosamente.\n";
    }

    echo "\n=== Migración completada exitosamente ===\n";

} catch (Exception $e) {
    echo "✗ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
