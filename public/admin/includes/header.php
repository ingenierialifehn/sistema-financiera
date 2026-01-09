<?php
/**
 * Header/Navbar - Admin
 */
?>
<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b bg-white px-4 shadow-sm lg:px-6">
    <!-- Botón para abrir sidebar en móvil -->
    <button id="sidebar-toggle" class="lg:hidden text-gray-600 hover:text-gray-900">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <!-- Título de la página -->
    <div class="flex-1">
        <h1 class="text-xl font-semibold text-gray-800" id="page-title">
            <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
        </h1>
    </div>

    <!-- Acciones del header -->
    <div class="flex items-center space-x-4">
        <!-- Notificaciones (placeholder) -->
        <button class="relative text-gray-600 hover:text-gray-900">
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
        </button>

        <!-- Usuario -->
        <div class="flex items-center space-x-3">
            <div class="hidden md:block text-right">
                <p class="text-sm font-medium text-gray-800">
                    <?php echo htmlspecialchars($user['nombre_completo'] ?? 'Admin'); ?>
                </p>
                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['rol'] ?? 'admin'); ?></p>
            </div>
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white">
                <i class="fas fa-user text-sm"></i>
            </div>
        </div>

        <!-- Logout -->
        <a href="../logout.php"
            class="flex items-center space-x-2 rounded-lg bg-red-500 px-3 py-2 text-white transition hover:bg-red-600">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden md:inline">Salir</span>
        </a>
    </div>
</header>

<script>
    // Conectar botón hamburguesa con sidebar
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                if (typeof toggleSidebar === 'function') {
                    toggleSidebar();
                }
            });
        }
    });
</script>