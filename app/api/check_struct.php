<?php
require_once __DIR__ . '/../../app/config/database.php';
$db = getDB();
$cols = $db->query("DESCRIBE cuotas")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " - " . $c['Type'] . "\n";
}
?>