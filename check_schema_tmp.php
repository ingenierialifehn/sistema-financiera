<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $tables = ['gastos_operativos', 'movimientos_bancarios', 'movimientos_internos_agencia', 'usuarios'];

    foreach ($tables as $table) {
        echo "DESCRIBE $table:\n";
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " | " . $col['Type'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
