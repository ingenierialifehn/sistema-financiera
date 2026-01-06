<?php
/**
 * Punto de entrada principal del sistema
 * Redirige según el tipo de usuario
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Auth.php';

// Iniciar sesión
session_start();

// Verificar si hay token de sesión
$token = $_GET['token'] ?? $_COOKIE['auth_token'] ?? null;
$user = null;

if ($token) {
    $user = Auth::verifyToken($token);
}

// Si no hay usuario autenticado, redirigir a login
if (!$user) {
    // Por defecto, redirigir a login
    header('Location: ' . base_url('public/login.php'));
    exit;
}

// Redirigir según rol
switch ($user['rol']) {
    case 'admin':
        header('Location: ' . base_url('public/admin/dashboard.php'));
        break;
    case 'cobrador':
        header('Location: ' . base_url('public/cobrador/home.php'));
        break;
    case 'cliente':
        header('Location: ' . base_url('public/cliente/index.php'));
        break;
    default:
        header('Location: ' . base_url('public/login.php'));
}
exit;

