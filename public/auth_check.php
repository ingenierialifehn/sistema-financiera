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
function requireRole($roles)
{
    global $user;

    if (!$user) {
        header('Location: ' . base_url('public/login.php'));
        exit;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    // Obtener rol actual (compatible con nueva estructura)
    $currentRole = $user['rol_nombre'] ?? $user['rol'] ?? '';

    // Mapeo de roles legacy a nuevos si es necesario
    if ($currentRole === 'Administrador' && in_array('admin', $roles)) {
        return; // Permitir acceso
    }

    if (!in_array($currentRole, $roles)) {
        // Usuario no tiene el rol requerido
        header('HTTP/1.1 403 Forbidden');

        $baseUrl = base_url();
        $logoutUrl = base_url('public/logout.php');
        $homeUrl = base_url('public/admin/dashboard.php'); // Default fallback

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-xl overflow-hidden text-center p-8">
        <div class="mb-6">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-4">
                <i class="fas fa-lock text-3xl text-red-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Acceso Denegado</h1>
            <p class="text-gray-600">
                Lo sentimos, no tiene los permisos necesarios para acceder a esta sección.
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Rol actual: <span class="font-mono bg-gray-100 px-2 py-1 rounded">{$currentRole}</span>
            </p>
        </div>
        
        <div class="flex flex-col space-y-3">
            <a href="{$logoutUrl}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
            
            <a href="javascript:history.back()" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors mt-2">
                <i class="fas fa-arrow-left mr-1"></i> Volver atrás
            </a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }
}

// Función helper para requerir admin
function requireAdmin()
{
    // Permitir acceso a cualquiera de estos roles "administrativos"
    requireRole(['admin', 'Administrador', 'Supervisor', 'Gerente']);
}

// Función helper para requerir cobrador o admin
function requireCobradorOrAdmin()
{
    requireRole(['admin', 'cobrador']);
}

// Exponer datos del usuario globalmente
$GLOBALS['current_user'] = $user;

