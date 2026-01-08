<?php
/**
 * Script para verificar los valores permitidos en la columna estado
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/plain');

try {
    $db = getDB();

    echo "=== VERIFICACIÓN DE COLUMNA ESTADO ===\n\n";

    // Obtener información de la columna estado
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Información de la columna 'estado':\n";
    echo "- Tipo: " . $column['Type'] . "\n";
    echo "- Null: " . $column['Null'] . "\n";
    echo "- Default: " . $column['Default'] . "\n";
    echo "\n";

    // Extraer valores del ENUM
    if (preg_match("/^enum\((.+)\)$/i", $column['Type'], $matches)) {
        $values = str_getcsv($matches[1], ',', "'");
        echo "Valores permitidos en ENUM:\n";
        foreach ($values as $value) {
            echo "  - '$value'\n";
        }
    }

    echo "\n";

    // Ver estados actuales en uso
    $stmt = $db->query("SELECT DISTINCT estado, COUNT(*) as cantidad FROM prestamos GROUP BY estado ORDER BY cantidad DESC");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Estados actualmente en uso:\n";
    foreach ($estados as $estado) {
        echo "  - '{$estado['estado']}': {$estado['cantidad']} préstamos\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>