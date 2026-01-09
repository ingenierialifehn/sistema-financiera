<?php
/**
 * Layout base para páginas de admin
 * Incluye sidebar, header y estructura base
 */

require_once __DIR__ . '/../../auth_check.php';


// Obtener datos del usuario
$user = $GLOBALS['current_user'] ?? Auth::checkSession();

// Obtener URL base para JavaScript
$baseUrl = getBaseUrl();
if (empty($baseUrl)) {
    $baseUrl = BASE_URL;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Admin'; ?> - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- URL Helper para compatibilidad móvil - DEBE CARGARSE PRIMERO -->
    <script>
        // Inline URL helper para asegurar que esté disponible inmediatamente
        function getBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;
            let basePath = pathname.substring(0, pathname.indexOf('/public'));
            if (!basePath) {
                const projectIndex = pathname.indexOf('sistema-financiera');
                if (projectIndex !== -1) {
                    basePath = pathname.substring(0, projectIndex + 'sistema-financiera'.length);
                } else {
                    basePath = '';
                }
            }
            return protocol + '//' + host + basePath;
        }
        const BASE_URL = getBaseUrl();
        window.BASE_URL = BASE_URL;
        console.log('BASE_URL dinámico:', BASE_URL);
    </script>
    <style>
        /* Estilos adicionales para el sidebar */
        @media (max-width: 1023px) {
            #sidebar.translate-x-0 {
                transform: translateX(0);
            }

            #sidebar-overlay.hidden {
                display: none;
            }

            #sidebar-overlay:not(.hidden) {
                display: block;
            }
        }

        <?php if (Auth::hasPermission('readonly')): ?>
            /* Modo Solo Lectura */
            .btn-save,
            .btn-edit,
            .btn-delete,
            button[type="submit"]:not(.ignore-readonly),
            .action-edit,
            .action-delete {
                display: none !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }

        <?php endif; ?>
    </style>
</head>

<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Header -->
        <?php include __DIR__ . '/header.php'; ?>

        <!-- Page Content -->
        <main class="p-4 lg:p-6">