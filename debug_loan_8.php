<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$id = 8;
$stmt = $db->prepare("SELECT id, id_cliente, tipo_prestamo, estado FROM prestamos WHERE id = ?");
$stmt->execute([$id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($loan);
