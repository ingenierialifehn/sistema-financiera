<?php
/**
 * Página de Logout
 * Cierra la sesión y redirige al login
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Obtener token de sesión o cookie
$token = null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_token'])) {
    $token = $_SESSION['user_token'];
} elseif (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
}

// Cerrar sesión
if ($token) {
    Auth::logout($token, false);
}

// Limpiar localStorage si es una petición AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada exitosamente',
        'clear_localStorage' => true
    ]);
    exit;
}

// Redirigir a login usando la IP/Host actual para mantener la sesión correcta en móviles
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
// Detectar si estamos en una subcarpeta
$scriptName = $_SERVER['SCRIPT_NAME'];
$publicIndex = strpos($scriptName, '/public');
$basePath = ($publicIndex !== false) ? substr($scriptName, 0, $publicIndex) : '';

$redirectUrl = $protocol . $host . $basePath . '/public/login.php?logged_out=1';

header('Location: ' . $redirectUrl);
exit;

