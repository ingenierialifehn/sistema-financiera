        </main>
    </div>
    
    <script>
        // Configurar base URL para JavaScript
        const BASE_URL = '<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>';
        
        // Toggle sidebar en móvil
        $(document).ready(function() {
            $('#sidebar-toggle').on('click', function() {
                $('#sidebar').toggleClass('-translate-x-0');
                $('#sidebar-overlay').toggleClass('hidden');
            });
            
            $('#sidebar-overlay').on('click', function() {
                $('#sidebar').addClass('-translate-x-full');
                $('#sidebar-overlay').addClass('hidden');
            });
            
            // Cerrar sidebar al hacer clic en un enlace (móvil)
            $('#sidebar a').on('click', function() {
                if (window.innerWidth < 1024) {
                    $('#sidebar').addClass('-translate-x-full');
                    $('#sidebar-overlay').addClass('hidden');
                }
            });
        });
    </script>
</body>
</html>

