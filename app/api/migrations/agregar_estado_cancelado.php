<?php
/**
 * Script para agregar el estado "Cancelado" al ENUM de la tabla prestamos
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/plain');

try {
    $db = getDB();

    echo "=== AGREGAR ESTADO 'Cancelado' AL ENUM ===\n\n";

    // Verificar estado actual
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Estado actual de la columna:\n";
    echo "Tipo: " . $column['Type'] . "\n\n";

    // Modificar la columna para agregar "Cancelado"
    $sql = "ALTER TABLE prestamos 
            MODIFY COLUMN estado ENUM(
                'Solicitado',
                'En Análisis',
                'Verificación de Campo',
                'Pendiente de Operaciones',
                'Aprobado',
                'Rechazado',
                'Activo',
                'Finalizado',
                'Cancelado',
                'Listo para Entrega'
            ) DEFAULT 'Solicitado'";

    echo "Ejecutando ALTER TABLE...\n";
    $db->exec($sql);
    echo "✅ Columna modificada exitosamente\n\n";

    // Verificar el cambio
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Nuevo estado de la columna:\n";
    echo "Tipo: " . $column['Type'] . "\n\n";

    // Extraer y mostrar valores
    if (preg_match("/^enum\((.+)\)$/i", $column['Type'], $matches)) {
        $values = str_getcsv($matches[1], ',', "'");
        echo "Valores permitidos:\n";
        foreach ($values as $value) {
            echo "  - '$value'\n";
        }
    }

    echo "\n✅ Estado 'Cancelado' agregado exitosamente al ENUM\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>