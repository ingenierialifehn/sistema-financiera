<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    $sql = "CREATE TABLE IF NOT EXISTS clientes_negocios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        nombre_negocio VARCHAR(255) NOT NULL,
        rubro VARCHAR(255) NOT NULL,
        foto_negocio_1 VARCHAR(255),
        foto_negocio_2 VARCHAR(255),
        foto_negocio_3 VARCHAR(255),
        foto_negocio_4 VARCHAR(255),
        foto_negocio_5 VARCHAR(255),
        doc_permiso_operaciones VARCHAR(255),
        garantia_descripcion VARCHAR(100),
        garantia_valor DECIMAL(12, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'clientes_negocios' creada exitosamente.";

} catch (PDOException $e) {
    echo "Error Creating Table: " . $e->getMessage();
}
?>