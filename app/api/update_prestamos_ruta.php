<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Add columns for delivery tracking
    $columns = [
        'en_ruta_desembolso' => "TINYINT(1) DEFAULT 0 COMMENT 'Si está en ruta para desembolso'",
        'ruta_usuario_id' => "INT NULL COMMENT 'ID del usuario que lleva el dinero'",
        'ruta_fecha_salida' => "DATETIME NULL COMMENT 'Fecha/hora de salida a ruta'",
        'fecha_desembolso' => "DATETIME NULL COMMENT 'Fecha/hora de desembolso efectivo'"
    ];

    foreach ($columns as $colName => $definition) {
        $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE '$colName'");
        if (!$stmt->fetch()) {
            $sql = "ALTER TABLE prestamos ADD COLUMN $colName $definition";
            $db->exec($sql);
            echo "Columna '$colName' agregada.<br>";
        } else {
            echo "Columna '$colName' ya existe.<br>";
        }
    }

    echo "<br>Tabla actualizada correctamente.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>