<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

$stmt = $db->prepare("SELECT id, fecha_desembolso, frecuencia_pago, plazo, monto_aprobado, tasa_interes FROM prestamos WHERE id = 12");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($row);
