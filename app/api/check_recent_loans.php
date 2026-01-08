<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, id_cliente, monto_capital, fecha_solicitud, tipo_prestamo, observaciones FROM prestamos ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "ID: " . $row['id'] . " | Tipo: " . ($row['tipo_prestamo'] ?? 'NULL') . " | Obs: " . $row['observaciones'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>