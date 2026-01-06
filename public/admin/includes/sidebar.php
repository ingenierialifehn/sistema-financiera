<?php
/**
 * Sidebar de navegación - Admin
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 bg-gray-800 text-white transition-transform -translate-x-full lg:translate-x-0">
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
            <a href="<?php echo base_url('public/admin/dashboard.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'dashboard.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-chart-line w-5"></i>
                <span>Dashboard</span>
            </a>
            
            <!-- Clientes -->
            <a href="<?php echo base_url('public/admin/clientes.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'clientes.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-users w-5"></i>
                <span>Clientes</span>
            </a>
            
            <!-- Préstamos -->
            <a href="<?php echo base_url('public/admin/prestamos.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'prestamos.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-hand-holding-usd w-5"></i>
                <span>Préstamos</span>
            </a>
            
            <!-- Pagos -->
            <a href="<?php echo base_url('public/admin/pagos.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'pagos.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-money-bill-wave w-5"></i>
                <span>Pagos</span>
            </a>
            
            <!-- Cobradores -->
            <a href="<?php echo base_url('public/admin/cobradores.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'cobradores.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-user-tie w-5"></i>
                <span>Cobradores</span>
            </a>

            <!-- Colaboradores -->
            <a href="<?php echo base_url('public/admin/colaboradores.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'colaboradores.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-users-cog w-5"></i>
                <span>Colaboradores</span>
            </a>
            
            <!-- Reportes -->
            <a href="<?php echo base_url('public/admin/reportes.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'reportes.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-file-alt w-5"></i>
                <span>Reportes</span>
            </a>
            
            <!-- Configuración -->
            <a href="<?php echo base_url('public/admin/configuracion.php'); ?>" 
               class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'configuracion.php' ? 'bg-indigo-600' : ''; ?>">
                <i class="fas fa-cog w-5"></i>
                <span>Configuración</span>
            </a>
        </nav>
        
        <!-- Footer del sidebar -->
        <div class="border-t border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="truncate text-sm font-medium"><?php echo htmlspecialchars($user['nombre_completo'] ?? 'Admin'); ?></p>
                    <p class="truncate text-xs text-gray-400"><?php echo htmlspecialchars($user['rol'] ?? 'admin'); ?></p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay para móvil -->
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden"></div>

