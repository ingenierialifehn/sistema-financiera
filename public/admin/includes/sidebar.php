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
            <?php if (Auth::hasPermission('dashboard.view') || Auth::hasPermission('dashboard')): ?>
                <a href="dashboard.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'dashboard.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>

            <!-- Dashboard Gerencial -->
            <?php if (Auth::hasPermission('dashboard_gerencial.view') || Auth::hasPermission('dashboard_gerencial')): ?>
                <a href="dashboard_gerencial.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'dashboard_gerencial.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Dashboard Gerencial</span>
                </a>
            <?php endif; ?>

            <!-- Colaboradores -->
            <!-- Gestión de Personal (Colaboradores y Usuarios) -->
            <?php if (Auth::hasPermission('colaboradores.view') || Auth::hasPermission('agencias.view') || Auth::hasPermission('clientes.view')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Gestión de Personal
                </div>

                <!-- Colaboradores -->
                <?php if (Auth::hasPermission('colaboradores.view')): ?>
                    <a href="colaboradores.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'colaboradores.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-users-cog w-5"></i>
                        <span>Colaboradores</span>
                    </a>
                <?php endif; ?>

                <!-- Agencias -->
                <?php if (Auth::hasPermission('agencias.view')): ?>
                    <a href="agencias.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'agencias.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-building w-5"></i>
                        <span>Agencias</span>
                    </a>
                <?php endif; ?>

                <!-- Clientes -->
                <?php if (Auth::hasPermission('clientes.view')): ?>
                    <a href="clientes.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'clientes.php' || $currentPage === 'ficha_cliente.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-users w-5"></i>
                        <span>Clientes</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Gestión de Créditos -->
            <?php if (Auth::hasPermission('prestamos_analisis.view') || Auth::hasPermission('verificacion_campo.view') || Auth::hasPermission('clientes.view')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Gestión de Créditos
                </div>

                <?php if (Auth::hasPermission('prestamos_analisis.view')): ?>
                    <a href="analisis_prestamos.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'analisis_prestamos.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-search-dollar w-5"></i>
                        <span>Análisis de Préstamos</span>
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('verificacion_campo.view')): ?>
                    <a href="verificacion_campo.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'verificacion_campo.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-clipboard-check w-5"></i>
                        <span>Verificación de Campo</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Finanzas -->
            <?php if (Auth::hasPermission('tesoreria.view')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Finanzas
                </div>
                <a href="tesoreria.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'tesoreria.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-university w-5"></i>
                    <span>Tesorería y Bancos</span>
                </a>
            <?php endif; ?>

            <!-- Planillas -->
            <?php if (Auth::hasPermission('planillas.view')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Planillas
                </div>
                <a href="planilla_gestion.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'planilla_gestion.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span>Generar Planilla</span>
                </a>
                <a href="planilla_config.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'planilla_config.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-sliders-h w-5"></i>
                    <span>Configuración Planilla</span>
                </a>
            <?php endif; ?>

            <!-- Gastos Operativos -->
            <?php if (Auth::hasPermission('gastos_operativos.view')): ?>
                <a href="gastos_operativos.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'gastos_operativos.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span>Gastos Operativos</span>
                </a>
            <?php endif; ?>

            <!-- Operaciones -->
            <?php if (Auth::hasPermission('operaciones.view')): ?>
                <a href="operaciones.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'operaciones.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-tasks w-5"></i>
                    <span>Operaciones</span>
                </a>
            <?php endif; ?>

            <!-- Desembolsos -->
            <?php if (Auth::hasPermission('desembolsos.view')): ?>
                <a href="desembolsos.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'desembolsos.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-hand-holding-usd w-5"></i>
                    <span>Desembolsos</span>
                </a>
            <?php endif; ?>

            <!-- Cobranza -->
            <?php if (Auth::hasPermission('cobranza.view_routes') || Auth::hasPermission('cobranza')): ?>
                <a href="cobranza.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'cobranza.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-money-bill-alt w-5"></i>
                    <span>Gestión de Cobranza</span>
                </a>
            <?php endif; ?>

            <!-- Control de Caja -->
            <?php if (Auth::hasPermission('caja.view')): ?>
                <a href="control_caja.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'control_caja.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-cash-register w-5"></i>
                    <span>Control de Caja</span>
                </a>
            <?php endif; ?>

            <!-- Documentación -->
            <?php if (Auth::hasPermission('documentacion.view')): ?>
                <a href="documentacion.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'documentacion.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-file-contract w-5"></i>
                    <span>Documentación</span>
                </a>
            <?php endif; ?>

            <!-- Seguridad y Roles -->
            <?php if (Auth::hasPermission('seguridad.view')): ?>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Seguridad
                </div>
                <a href="roles/index.php"
                    class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo ($currentPage === 'index.php' && $parentDir === 'roles') || $currentPage === 'roles.php' ? 'bg-indigo-600' : ''; ?>">
                    <i class="fas fa-shield-alt w-5"></i>
                    <span>Roles y Permisos</span>
                </a>
            <?php endif; ?>

            <!-- Reportes -->
            <?php if (Auth::hasPermission('reportes.view_consolidado') || Auth::hasPermission('reportes.view_agencia') || Auth::hasPermission('reportes.view_financieros')): ?>
                <div class="border-t border-gray-700 my-2"></div>

                <?php if (Auth::hasPermission('reportes.view_consolidado')): ?>
                    <a href="reportes_consolidado.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'reportes_consolidado.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Reportes Consolidados</span>
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('reportes.view_agencia')): ?>
                    <a href="reportes_agencia.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'reportes_agencia.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Reportes de Agencia</span>
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasPermission('reportes.view_financieros')): ?>
                    <a href="reportes_financieros.php"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2 transition hover:bg-gray-700 <?php echo $currentPage === 'reportes_financieros.php' ? 'bg-indigo-600' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar w-5"></i>
                        <span>Reportes Financieros</span>
                    </a>
                <?php endif; ?>

            <?php endif; ?>

            <?php if (Auth::hasPermission('configuracion.view')): ?>
                <a href="configuracion.php"
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
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden" onclick="toggleSidebar()">
</div>

<script>
    // Función para toggle del sidebar en móvil
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Auto-cerrar sidebar en móvil al hacer clic en un enlace
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const sidebarLinks = sidebar.querySelectorAll('a');

        // Detectar si es móvil
        function isMobile() {
            return window.innerWidth < 1024; // lg breakpoint
        }

        // Cerrar sidebar al hacer clic en un enlace (solo en móvil)
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                if (isMobile()) {
                    // Pequeño delay para que se vea la selección
                    setTimeout(() => {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    }, 150);
                }
            });
        });
    });
</script>