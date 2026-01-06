<?php
/**
 * Test de redirección - para ver qué está pasando
 */

require_once __DIR__ . '/../app/config/config.php';
session_start();

echo "<h2>Test de Redirección</h2>";

echo "<h3>Variables de sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>URLs generadas:</h3>";
echo "<p>getBaseUrl(): <strong>" . getBaseUrl() . "</strong></p>";
echo "<p>base_url('public/admin/dashboard.php'): <strong>" . base_url('public/admin/dashboard.php') . "</strong></p>";

echo "<h3>Verificación de autenticación:</h3>";
if (isset($_SESSION['user_id']) && isset($_SESSION['user_token'])) {
    echo "<p style='color: green;'>✓ Sesión encontrada</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Token: " . substr($_SESSION['user_token'], 0, 20) . "...</p>";
    echo "<p>Rol: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "</p>";
    
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/core/Auth.php';
    
    $user = Auth::checkSession();
    if ($user) {
        echo "<p style='color: green;'>✓ Token válido</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Token inválido</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ No hay sesión activa</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Ir a Login</a></p>";

?>

