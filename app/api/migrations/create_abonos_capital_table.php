<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    $sql = "CREATE TABLE IF NOT EXISTS abonos_capital (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prestamo_id INT NOT NULL,
        cliente_id INT NOT NULL,
        monto DECIMAL(12, 2) NOT NULL,
        fecha DATE NOT NULL,
        observaciones TEXT DEFAULT NULL,
        registrado_por INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Table 'abonos_capital' created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>