<?php
/**
 * Debug del login - para ver qué está pasando
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG LOGIN ===<br><br>";

echo "1. Verificando require de config.php...<br>";
try {
    require_once __DIR__ . '/../app/config/config.php';
    echo "✓ config.php cargado correctamente<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<br>2. Verificando funciones...<br>";
if (function_exists('getBaseUrl')) {
    echo "✓ getBaseUrl() existe<br>";
    echo "   Valor: " . getBaseUrl() . "<br>";
} else {
    echo "✗ getBaseUrl() NO existe<br>";
}

if (function_exists('base_url')) {
    echo "✓ base_url() existe<br>";
    echo "   base_url('public/login.php'): " . base_url('public/login.php') . "<br>";
} else {
    echo "✗ base_url() NO existe<br>";
}

echo "<br>3. Verificando sesión...<br>";
session_start();
echo "✓ Sesión iniciada<br>";
echo "   Session ID: " . session_id() . "<br>";

echo "<br>4. Verificando variables...<br>";
echo "   BASE_URL definido: " . (defined('BASE_URL') ? BASE_URL : 'NO DEFINIDO') . "<br>";
echo "   API_URL definido: " . (defined('API_URL') ? API_URL : 'NO DEFINIDO') . "<br>";

echo "<br>5. Headers enviados:<br>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

echo "<br>6. Intentando cargar login.php completo...<br>";
echo "<hr>";
echo "<h3>Si ves esto, el problema está en el HTML/JavaScript del login.php</h3>";
echo "<p>Accede ahora a: <a href='login.php'>login.php</a></p>";

?>

