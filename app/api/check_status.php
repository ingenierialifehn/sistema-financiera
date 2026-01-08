<?php
require_once __DIR__ . '/../config/database.php';
session_start();
echo '<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .btn{padding:5px 10px;text-decoration:none;background:#2563eb;color:white;border-radius:4px;}</style>';

echo "<h1>🔍 Auditoría de Estados de Préstamos</h1>";

$db = getDB();
$sql = "SELECT p.id, c.nombre_completo, p.monto_capital, p.estado, p.asesor_creditos_id 
        FROM prestamos p 
        JOIN clientes c ON p.id_cliente = c.id";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>Cliente</th><th>Monto</th><th>Estado Actual</th><th>Asesor ID</th><th>Acción</th></tr>";

foreach ($rows as $r) {
    echo "<tr>";
    echo "<td>#{$r['id']}</td>";
    echo "<td>{$r['nombre_completo']}</td>";
    echo "<td>L " . number_format($r['monto_capital'], 2) . "</td>";

    $color = ($r['estado'] == 'Activo') ? 'green' : 'orange';
    echo "<td style='color:$color;font-weight:bold;'>{$r['estado']}</td>";
    echo "<td>{$r['asesor_creditos_id']}</td>";

    echo "<td>";
    if ($r['estado'] == 'Listo para Entrega') {
        echo "<form method='POST' action='force_active.php'>
                <input type='hidden' name='id' value='{$r['id']}'>
                <button type='submit' class='btn'>Forzar a ACTIVO</button>
              </form>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
?>