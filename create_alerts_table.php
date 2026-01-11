<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$sql = "CREATE TABLE IF NOT EXISTS alertas_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'revisado') DEFAULT 'pendiente',
    agencia_id INT,
    usuario_id INT,
    FOREIGN KEY (agencia_id) REFERENCES agencias(id_agencia),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->exec($sql);
    echo "Tabla alertas_sistema creada correctamente.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
