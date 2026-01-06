<?php
require_once __DIR__ . '/../app/config/config.php';

echo "<h2>Test de Base URL</h2>";
echo "<p>BASE_URL definido: <strong>" . BASE_URL . "</strong></p>";
echo "<p>getBaseUrl(): <strong>" . getBaseUrl() . "</strong></p>";
echo "<p>base_url('public/admin/dashboard.php'): <strong>" . base_url('public/admin/dashboard.php') . "</strong></p>";

echo "<h3>Variables del servidor:</h3>";
echo "<pre>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "\n";
echo "</pre>";

?>

