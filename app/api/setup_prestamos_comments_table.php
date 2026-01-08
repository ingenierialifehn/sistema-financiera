<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    $sql = "CREATE TABLE IF NOT EXISTS prestamos_comentarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prestamo_id INT NOT NULL,
        usuario_id INT NOT NULL,
        comentario TEXT NOT NULL,
        etapa_flujo VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'prestamos_comentarios' creada exitosamente.";

} catch (PDOException $e) {
    echo "Error Creating Table: " . $e->getMessage();
}
?>