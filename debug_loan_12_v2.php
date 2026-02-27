<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->prepare("SELECT id, fecha_desembolso, modalidad, plazo_meses, monto_capital FROM prestamos WHERE id = 12");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
