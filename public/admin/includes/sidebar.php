<?php
/**
 * Sidebar de navegación - Admin
 */
require_once __DIR__ . '/../../../app/core/Auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$parentDir = basename(dirname($_SERVER['PHP_SELF'])); // Para saber si estamos en subcarpeta roles
?>
<aside id="sidebar"
    class="fixed left-0 top-0 z-40 h-screen w-64 bg-gray-800 text-white transition-transform -translate-x-full lg:translate-x-0">
    <div class="flex h-full flex-col">
        <!-- Logo/Brand -->
        <div class="flex h-16 items-center justify-center border-b border-gray-700 bg-gray-900">
            <div class="flex items-center space-x-2">
                <i class="fas fa-university text-2xl text-indigo-400"></i>
                <span class="text-lg font-bold">Sistema Financiero</span>
            </div>
        </div>

        <!-- Menú de navegación -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <!-- Dashboard -->
            <!-- Dashboard -->
            <?php if (Auth::hasPermission('dashboard')): ?>
                <a href="<?php echo base_url('public/admin/dashboard.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'dashboard.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>







            <!-- Cobradores -->
            <a href="<?php echo base_url('public/admin/cobradores.php'); ?>"
                class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'cobradores.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-user-tie w-5"></i>
                <span>Cobradores</span>
            </a>

            <!-- Colaboradores -->
            <!-- Gestión de Personal (Colaboradores y Usuarios) -->
            <?php if (Auth::hasPermission('usuarios')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Gestión de Personal
                </div>

                <!-- Colaboradores -->
                <a href="<?php echo base_url('public/admin/colaboradores.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'colaboradores.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-users-cog w-5"></i>
                    <span>Colaboradores</span>
                </a>

                <!-- Agencias -->
                <a href="<?php echo base_url('public/admin/agencias.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'agencias.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-building w-5"></i>
                    <span>Agencias</span>
                </a>
            <?php endif; ?>



            <!-- Seguridad y Roles (Nuevo) -->
            <?php if (Auth::hasPermission('seguridad')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Seguridad
                </div>
                <a href="<?php echo base_url('public/admin/roles/index.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo ($currentPage === 'index.php' && $parentDir === 'roles') || $currentPage === 'roles.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-shield-alt w-5"></i>
                    <span>Roles y Permisos</span>
                </a>
            <?php endif; ?>

            <!-- Reportes -->
            <!-- Reportes -->
            <?php if (Auth::hasPermission('reportes')): ?>
                <div class="border-t border-gray-700 my-2"></div>
                <a href="<?php echo base_url('public/admin/reportes.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'reportes.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Reportes</span>
                </a>
            <?php endif; ?>

            <!-- Configuración -->
            <!-- Configuración -->
            <?php if (Auth::hasPermission('configuracion')): ?>
                <a href="<?php echo base_url('public/admin/configuracion.php'); ?>"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'configuracion.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span>Configuración</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Footer del sidebar -->
        <div class="border-t border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="truncate text-sm font-medium">
                        <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $user['nombre_completo'] ?? 'Usuario'); ?>
                    </p>
                    <p class="truncate text-xs text-gray-400">
                        <?php echo htmlspecialchars($_SESSION['rol_nombre'] ?? $user['rol_nombre'] ?? 'Rol'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay para móvil -->
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden"></div>