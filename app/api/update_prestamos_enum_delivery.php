<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Check if 'Listo para Entrega' exists
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $type = $row['Type'];
        if (!strpos($type, "'Listo para Entrega'")) {
            // Add 'Listo para Entrega' to ENUM
            // Current list based on previous file: 'Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado', 'Rechazado', 'Activo', 'Finalizado'
            // We append 'Listo para Entrega'
            $newEnum = "ENUM('Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado', 'Rechazado', 'Activo', 'Finalizado', 'Listo para Entrega')";

            $db->exec("ALTER TABLE prestamos MODIFY COLUMN estado $newEnum DEFAULT 'Solicitado'");
            echo "Estado 'Listo para Entrega' agregado al ENUM.";
        } else {
            echo "El estado 'Listo para Entrega' ya existe.";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>