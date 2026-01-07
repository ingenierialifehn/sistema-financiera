<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Create negocios_garantias table
    $sql = "CREATE TABLE IF NOT EXISTS negocios_garantias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        negocio_id INT NOT NULL,
        descripcion VARCHAR(255),
        valor DECIMAL(12, 2) DEFAULT 0,
        foto VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (negocio_id) REFERENCES clientes_negocios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'negocios_garantias' creada.\n";

    // Migrate existing data if needed (optional, but good practice if not empty)
    // We can move data from clientes_negocios columns to this new table, but let's assume it's fresh enough or we leave old data there.
    // Actually, user wants "each guarantee has its own description", so the old single description is obsolete for new entries.

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
