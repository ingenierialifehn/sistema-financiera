<?php
// Debug script to verify insert into comments
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    // Assuming loan ID 1 exists from previous steps
    $prestamoId = 1;
    $usuarioId = 1; // Assuming admin user id 1
    $comentario = "Test comment from debug script";
    $etapa = 'En Análisis';

    $sql = "INSERT INTO prestamos_comentarios (prestamo_id, usuario_id, comentario, etapa_flujo) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$prestamoId, $usuarioId, $comentario, $etapa]);

    if ($result) {
        echo "Insert SUCCESS. ID: " . $db->lastInsertId();
    } else {
        echo "Insert FAILED.";
        print_r($stmt->errorInfo());
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>