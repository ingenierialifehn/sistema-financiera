<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    // Check if columns already exist to avoid error
    $stmt = $db->query("DESCRIBE plantillas_documentos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $queries = [];

    if (!in_array('margen_top', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN margen_top INT DEFAULT 20";
    }
    if (!in_array('margen_bottom', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN margen_bottom INT DEFAULT 20";
    }
    if (!in_array('margen_left', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN margen_left INT DEFAULT 25";
    }
    if (!in_array('margen_right', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN margen_right INT DEFAULT 25";
    }
    if (!in_array('orientacion', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN orientacion VARCHAR(20) DEFAULT 'portrait'";
    }
    if (!in_array('logo_ancho', $columns)) {
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN logo_ancho INT DEFAULT 150";
    }
    if (!in_array('logo_posicion', $columns)) {
        // 'left', 'center', 'right'
        $queries[] = "ALTER TABLE plantillas_documentos ADD COLUMN logo_posicion VARCHAR(20) DEFAULT 'right'";
    }

    foreach ($queries as $sql) {
        $db->exec($sql);
        echo "Executed: $sql\n";
    }

    echo "Database updated successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>