<?php
/**
 * Ejemplo de página protegida
 * Este archivo muestra cómo proteger una página web
 */

// Incluir el helper de autenticación
require_once __DIR__ . '/auth_check.php';

// Si quieres restringir por rol, descomenta una de estas líneas:
// requireAdmin(); // Solo admin
// requireCobradorOrAdmin(); // Solo cobrador o admin
// requireRole('cliente'); // Solo cliente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Protegida - Ejemplo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6">Página Protegida - Ejemplo</h1>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800">
                    <strong>✓ Autenticación exitosa</strong><br>
                    Esta página está protegida y solo se muestra si el usuario está autenticado.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h2 class="text-xl font-semibold mb-2">Información del Usuario</h2>
                    <ul class="space-y-2">
                        <li><strong>ID:</strong> <?php echo htmlspecialchars($user['id']); ?></li>
                        <li><strong>Usuario:</strong> <?php echo htmlspecialchars($user['usuario']); ?></li>
                        <li><strong>Nombre:</strong> <?php echo htmlspecialchars($user['nombre_completo']); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                        <li><strong>Rol:</strong> 
                            <span class="px-2 py-1 bg-blue-200 rounded text-sm">
                                <?php echo htmlspecialchars($user['rol']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h2 class="text-xl font-semibold mb-2">Acciones Disponibles</h2>
                    <div class="space-y-2">
                        <a href="/public/logout.php" class="block w-full bg-red-500 hover:bg-red-600 text-white text-center py-2 px-4 rounded transition">
                            Cerrar Sesión
                        </a>
                        <a href="/public/login.php" class="block w-full bg-gray-500 hover:bg-gray-600 text-white text-center py-2 px-4 rounded transition">
                            Ir a Login
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h2 class="text-xl font-semibold mb-2">Código de esta página:</h2>
                <pre class="bg-gray-800 text-green-400 p-4 rounded overflow-x-auto text-sm"><code><?php echo htmlspecialchars('<?php
// Incluir el helper de autenticación
require_once __DIR__ . \'/auth_check.php\';

// Opcional: Restringir por rol
// requireAdmin(); // Solo admin
// requireCobradorOrAdmin(); // Solo cobrador o admin
// requireRole(\'cliente\'); // Solo cliente
?>
&lt;!-- Tu HTML aquí --&gt;
&lt;?php echo $user[\'nombre_completo\']; ?&gt;'); ?></code></pre>
            </div>
            
            <div class="mt-6 text-center text-gray-600">
                <p>Elimina este archivo en producción. Es solo un ejemplo.</p>
            </div>
        </div>
    </div>
</body>
</html>

