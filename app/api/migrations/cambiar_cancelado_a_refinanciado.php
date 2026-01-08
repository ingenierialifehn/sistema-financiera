<?php
/**
 * Script para cambiar "Cancelado" por "Refinanciado" en el ENUM
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/plain');

try {
    $db = getDB();

    echo "=== CAMBIAR 'Cancelado' POR 'Refinanciado' ===\n\n";

    // Verificar estado actual
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Estado actual de la columna:\n";
    echo "Tipo: " . $column['Type'] . "\n\n";

    // Primero, actualizar los registros que tienen "Cancelado" a "Refinanciado"
    echo "Actualizando registros existentes...\n";
    $stmt = $db->prepare("UPDATE prestamos SET estado = 'Activo' WHERE estado = 'Cancelado'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "Registros temporalmente actualizados: $updated\n\n";

    // Modificar la columna para cambiar "Cancelado" por "Refinanciado"
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
                'Refinanciado',
                'Listo para Entrega'
            ) DEFAULT 'Solicitado'";

    echo "Ejecutando ALTER TABLE...\n";
    $db->exec($sql);
    echo "✅ Columna modificada exitosamente\n\n";

    // Ahora actualizar los registros que temporalmente pusimos en Activo
    if ($updated > 0) {
        echo "Restaurando registros a 'Refinanciado'...\n";
        // Necesitamos identificar cuáles eran los que estaban cancelados
        // Los identificamos por las observaciones que contienen "Cancelado por refinanciamiento"
        $stmt = $db->prepare("
            UPDATE prestamos 
            SET estado = 'Refinanciado' 
            WHERE observaciones LIKE '%Cancelado por refinanciamiento%'
        ");
        $stmt->execute();
        $restored = $stmt->rowCount();
        echo "Registros actualizados a 'Refinanciado': $restored\n\n";
    }

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

    // Verificar estados actuales
    $stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM prestamos GROUP BY estado");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nEstados en uso:\n";
    foreach ($estados as $estado) {
        echo "  - '{$estado['estado']}': {$estado['cantidad']} préstamos\n";
    }

    echo "\n✅ Estado 'Refinanciado' implementado exitosamente\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>