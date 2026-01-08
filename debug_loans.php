<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$sql = "SELECT p.*, c.nombre_completo as cliente_nombre, c.numero_documento 
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        WHERE p.estado IN ('Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado')
        ORDER BY p.fecha_solicitud DESC LIMIT 1";
$stmt = $db->query($sql);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($row);
echo "</pre>";
?>