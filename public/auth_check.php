<?php
/**
 * Helper para verificar autenticación en páginas web
 * 
 * Uso en cualquier página PHP:
 * require_once __DIR__ . '/auth_check.php';
 * 
 * O con rol específico:
 * require_once __DIR__ . '/auth_check.php';
 * requireRole('admin');
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Auth.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
$user = Auth::checkSession();

if (!$user) {
    // Limpiar cualquier dato residual
    session_destroy();
    
    // Redirigir a login
    header('Location: ' . base_url('public/login.php') . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Función helper para requerir rol específico
function requireRole($roles) {
    global $user;
    
    if (!$user) {
        header('Location: ' . base_url('public/login.php'));
        exit;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($user['rol'], $roles)) {
        // Usuario no tiene el rol requerido
        header('HTTP/1.1 403 Forbidden');
        die('Acceso denegado. No tiene permisos para acceder a esta página.');
    }
}

// Función helper para requerir admin
function requireAdmin() {
    requireRole('admin');
}

// Función helper para requerir cobrador o admin
function requireCobradorOrAdmin() {
    requireRole(['admin', 'cobrador']);
}

// Exponer datos del usuario globalmente
$GLOBALS['current_user'] = $user;

