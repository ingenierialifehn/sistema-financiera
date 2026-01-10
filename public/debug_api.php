<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/config/database.php';

echo "<h3>Debug API Clientes</h3>";

$db = getDB();
$userId = 1; // Admin

// 1. Verificar Usuario
$u = $db->query("SELECT * FROM usuarios WHERE id_usuario = $userId")->fetch(PDO::FETCH_ASSOC);
echo "Usuario ($userId): " . ($u ? "Encontrado ({$u['username']})" : "NO ENCONTRADO") . "<br>";

// 2. Insertar Cliente de Prueba
try {
    $code = 'TEST' . rand(1000, 9999);
    $stmt = $db->prepare("INSERT INTO clientes (nombre_completo, numero_documento, cobrador_id, id_agencia, estado) VALUES ('Cliente Test', :doc, :cob, 1, 'activo')");
    $stmt->execute(['doc' => $code, 'cob' => $userId]);
    $nuevoId = $db->lastInsertId();
    echo "Cliente insertado ID: $nuevoId (cobrador_id: $userId)<br>";
} catch (Exception $e) {
    echo "Error inserting: " . $e->getMessage() . "<br>";
}

// 3. Probar Query List
echo "<hr>Testing Query Logic:<br>";
$sql = "SELECT * FROM clientes c WHERE c.cobrador_id = :uid";
$stmt = $db->prepare($sql);
$stmt->execute(['uid' => $userId]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($res) . " clients for user $userId.<br>";
if (count($res) > 0) {
    echo "<pre>" . print_r($res[0], true) . "</pre>";
} else {
    // Ver si hay clientes con cobrador NULL
    $nulls = $db->query("SELECT count(*) FROM clientes WHERE cobrador_id IS NULL")->fetchColumn();
    echo "Clientes con cobrador NULL: $nulls<br>";

    $others = $db->query("SELECT count(*) FROM clientes WHERE cobrador_id != $userId")->fetchColumn();
    echo "Clientes con otro cobrador: $others<br>";
}

?>