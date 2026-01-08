<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

// Check cuotas structure and sample data
echo "<h1>Cuotas Sample (Paid)</h1>";
$stmt = $db->query("SELECT * FROM cuotas WHERE estado='pagada' LIMIT 5");
$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cuotas)) {
    echo "No paid cuotas found.<br>";
} else {
    echo "<table border='1'><tr>";
    foreach (array_keys($cuotas[0]) as $key) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    foreach ($cuotas as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Check Prestamos columns
echo "<h1>Prestamos Sample</h1>";
$stmt = $db->query("SELECT * FROM prestamos LIMIT 1");
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($prestamos)) {
    echo "<pre>";
    print_r(array_keys($prestamos[0]));
    echo "</pre>";
}
?>