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

// Redirigir a login
header('Location: ' . base_url('public/login.php') . '?logged_out=1');
exit;

