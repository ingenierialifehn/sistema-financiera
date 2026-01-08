<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    $columnsToAdd = [
        'tasa_interes' => "DECIMAL(5, 2) DEFAULT 4.00 AFTER tasa_total",
        'tasa_gastos' => "DECIMAL(5, 2) DEFAULT 4.00 AFTER tasa_interes",
        'tasa_comision' => "DECIMAL(5, 2) DEFAULT 3.00 AFTER tasa_gastos"
    ];

    foreach ($columnsToAdd as $colName => $colDef) {
        // Check if column exists using information_schema
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prestamos' AND COLUMN_NAME = ?");
        $stmt->execute([$colName]);

        if ($stmt->fetchColumn() == 0) {
            $sql = "ALTER TABLE prestamos ADD COLUMN $colName $colDef";
            $db->exec($sql);
            echo "Columna '$colName' agregada.<br>";
        } else {
            echo "Columna '$colName' ya existe.<br>";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>