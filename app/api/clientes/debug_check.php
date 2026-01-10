<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$db = getDB();

echo "<h1>Debug Info</h1>";

// Check Users
$stmt = $db->query("SELECT id_usuario, nombre, usuario, rol, id_agencia FROM usuarios");
echo "<h2>Users</h2><pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

// Check Clients
$stmt = $db->query("SELECT id, nombre_completo, cobrador_id, id_agencia, usuario_id FROM clientes LIMIT 10");
echo "<h2>Clients (First 10)</h2><pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

// Check Total Clients
$stmt = $db->query("SELECT COUNT(*) as total FROM clientes");
echo "<h2>Total Clients</h2>";
echo $stmt->fetch()['total'];
