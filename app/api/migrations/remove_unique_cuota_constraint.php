<?php
/**
 * Migración: Eliminar restricción de unicidad en cuotas
 * Necesario para permitir la división de cuotas en pagos parciales
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Modificando tabla cuotas ===\n\n";

    // Intentar eliminar el índice unique
    // Nota: El nombre del índice suele ser 'uk_prestamo_cuota' o generado automáticamente.
    // Probaremos con el nombre reportado en el error.

    $sql = "ALTER TABLE cuotas DROP INDEX uk_prestamo_cuota";

    try {
        $db->exec($sql);
        echo "✓ Índice 'uk_prestamo_cuota' eliminado exitosamente.\n";
    } catch (PDOException $e) {
        // Si falla, intentamos ver si existe con otro nombre o si ya se borró
        echo "Nota: El índice 'uk_prestamo_cuota' no se pudo borrar directamente (quizás no existe o tiene otro nombre).\n";
        echo "Detalle: " . $e->getMessage() . "\n";

        // Listar índices para diagnóstico si falla
        $stmt = $db->query("SHOW INDEX FROM cuotas");
        $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nÍndices actuales en la tabla:\n";
        foreach ($indices as $idx) {
            echo "- " . $idx['Key_name'] . "\n";
        }
    }

    // Alternativamente, si el índice se llamaba diferente (común en creaciones automáticas)
    // A veces es una constraint unique, no solo un index.

    echo "\n=== Proceso finalizado ===\n";

} catch (Exception $e) {
    echo "✗ Error General: " . $e->getMessage() . "\n";
}
