<?php
/**
 * Configuración del Sistema
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/core/Helpers.php';

AuthMiddleware::requireAdmin();

$pageTitle = 'Configuración';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Configuración del Sistema</h2>
            <p class="text-gray-600">Gestione los parámetros generales de la aplicación</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <!-- Mantenimiento de Préstamos -->
    <a href="<?php echo BASE_URL; ?>/public/admin/configuracion_prestamos.php" class="block">
        <div
            class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6 border-l-4 border-indigo-600 h-full flex flex-col">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                    <i class="fas fa-hand-holding-usd text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Préstamos</h3>
            </div>
            <p class="text-gray-600 text-sm flex-grow">
                Configure tasas de interés, moras, días de gracia y condiciones de refinanciamiento.
            </p>
            <div class="mt-4 text-indigo-600 font-semibold text-sm">
                Gestionar <i class="fas fa-arrow-right ml-1"></i>
            </div>
        </div>
    </a>

    <!-- Sistema (General) -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-gray-500 h-full flex flex-col opacity-75">
        <div class="flex items-center mb-4">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600 mr-4">
                <i class="fas fa-cogs text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">General</h3>
        </div>
        <p class="text-gray-600 text-sm flex-grow">
            Información de la empresa, moneda y parámetros básicos.
        </p>
        <div class="mt-4 text-gray-500 font-semibold text-sm italic">
            Próximamente
        </div>
    </div>

    <!-- Usuarios y Roles -->
    <a href="<?php echo BASE_URL; ?>/public/admin/roles/index.php" class="block">
        <div
            class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6 border-l-4 border-green-600 h-full flex flex-col">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-user-shield text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Seguridad</h3>
            </div>
            <p class="text-gray-600 text-sm flex-grow">
                Gestión de usuarios, roles y permisos de acceso.
            </p>
            <div class="mt-4 text-green-600 font-semibold text-sm">
                Gestionar <i class="fas fa-arrow-right ml-1"></i>
            </div>
        </div>
    </a>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>