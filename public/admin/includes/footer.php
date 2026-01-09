</main>
</div>

<script>
    // Configurar base URL para JavaScript
    // Configurar base URL para JavaScript (Safely)
    if (typeof BASE_URL === 'undefined') {
        const BASE_URL = '<?php echo htmlspecialchars($baseUrl ?? "", ENT_QUOTES, 'UTF-8'); ?>';
    }

    // Toggle sidebar en móvil
    $(document).ready(function () {
        $('#sidebar-toggle').on('click', function () {
            $('#sidebar').toggleClass('-translate-x-0');
            $('#sidebar-overlay').toggleClass('hidden');
        });

        $('#sidebar-overlay').on('click', function () {
            $('#sidebar').addClass('-translate-x-full');
            $('#sidebar-overlay').addClass('hidden');
        });

        // Cerrar sidebar al hacer clic en un enlace (móvil)
        $('#sidebar a').on('click', function () {
            if (window.innerWidth < 1024) {
                $('#sidebar').addClass('-translate-x-full');
                $('#sidebar-overlay').addClass('hidden');
            }
        });

        <?php
        // Check global read only flag from session
        $isGlobalReadOnly = false;
        if (isset($_SESSION['permisos']['solo_lectura_global']) && $_SESSION['permisos']['solo_lectura_global'] === true) {
            $isGlobalReadOnly = true;
        } elseif (Auth::hasPermission('readonly')) { // Also check specific permission key if set
            $isGlobalReadOnly = true;
        }

        if ($isGlobalReadOnly):
            ?>
            // Global Read Only Mode
            $('input, select, textarea').prop('disabled', true);
            // Re-disable if dynamic content is loaded (using mutation observer or ajax events if needed, but simple interval for now)
            setInterval(function () {
                $('input:not([disabled]), select:not([disabled]), textarea:not([disabled])').prop('disabled', true);
                // Also hide action buttons that might have been added dynamically
                $('.btn-save, .btn-new, .btn-delete, .btn-edit').hide();
            }, 1000);
        <?php endif; ?>
    });
</script>
</body>

</html>