<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    $sqls = [
        "ALTER TABLE prestamos ADD COLUMN comentario_analisis TEXT NULL DEFAULT NULL",
        "ALTER TABLE prestamos ADD COLUMN comentario_verificacion TEXT NULL DEFAULT NULL"
    ];

    foreach ($sqls as $sql) {
        try {
            $db->exec($sql);
            echo "Ejecutado: $sql <br>";
        } catch (PDOException $e) {
            // Ignore Duplicate column name error (Code 1060)
            if ($e->getCode() != '42S21' && $e->errorInfo[1] != 1060) {
                echo "Error: " . $e->getMessage() . "<br>";
            } else {
                echo "Columna ya existía (ignorando error).<br>";
            }
        }
    }

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
?>