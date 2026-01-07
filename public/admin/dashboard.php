<?php
/**
 * Dashboard de Administrador
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('dashboard');
require_once __DIR__ . '/includes/layout.php';

// Obtener nombre del usuario
$nombreUsuario = $_SESSION['nombre_completo'] ?? $user['nombre_completo'] ?? 'Usuario';
?>

<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="mb-6">
            <i class="fas fa-home text-indigo-600 text-6xl mb-4"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-2">
            ¡Bienvenido, <?php echo htmlspecialchars($nombreUsuario); ?>!
        </h1>
        <p class="text-gray-600 text-lg">
            Sistema de Gestión Financiera
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>