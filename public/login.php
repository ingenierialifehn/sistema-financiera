<?php
/**
 * Página de Login
 * Compatible con hosting compartido
 */

require_once __DIR__ . '/../app/config/config.php';

session_start();

// Si ya está autenticado, redirigir según rol
if (isset($_SESSION['user_id']) && isset($_SESSION['user_token'])) {
    // Verificar que el token sea válido
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/core/Auth.php';

    $user = Auth::checkSession();

    if ($user) {
        $rol = $user['rol'] ?? 'cliente';
        $redirectUrl = '';

        // Obtener rol y permisos para redirección inteligente
        $rolNombre = $user['rol_nombre'] ?? $user['rol'] ?? 'cliente';

        // Lógica de redirección basada en permisos o roles
        if (Auth::hasPermission('dashboard') || in_array($rolNombre, ['Administrador', 'admin', 'Supervisor', 'Gerente', 'Cajero', 'Asesor'])) {
            // Todos los roles administrativos van al dashboard admin
            $redirectUrl = '/public/admin/dashboard.php';
        } elseif ($rolNombre === 'cobrador' || $rolNombre === 'Cobrador') {
            $redirectUrl = '/public/cobrador/home.php';
        } else {
            // Cliente por defecto
            $redirectUrl = '/public/cliente/index.php';
        }

        if ($redirectUrl) {
            // Usar ruta relativa para compatibilidad con acceso móvil
            // Obtener el directorio base del proyecto
            $scriptPath = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/public/login.php', '', $scriptPath);
            $fullPath = $basePath . $redirectUrl;

            header('Location: ' . $fullPath);
            exit;
        }

        // Si ya está logueado, redirigir vía JavaScript (ver código al final de la página)
    } else {
        // Token inválido, limpiar sesión
        session_destroy();
    }
}

// Obtener URL base para JavaScript
$baseUrl = getBaseUrl();
// Asegurar que la variable esté disponible en JavaScript
if (empty($baseUrl)) {
    $baseUrl = BASE_URL;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .input-focus:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .bg-login {
            background-image: url('assets/img/login_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>

<body class="bg-login">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-black bg-opacity-30">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo/Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-gradient rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-university text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900">Sistema Financiero</h2>
                <p class="mt-2 text-sm text-gray-600">Ingrese sus credenciales para continuar</p>
            </div>

            <!-- Formulario de Login -->
            <form id="loginForm" class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow-lg">
                <!-- Mensaje de error/éxito -->
                <div id="alertMessage" class="hidden rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i id="alertIcon" class="fas text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p id="alertText" class="text-sm font-medium"></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" onclick="closeAlert()"
                                class="inline-flex text-gray-400 hover:text-gray-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Campos del formulario -->
                <div class="space-y-4">
                    <div>
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1">
                            Usuario o Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input id="usuario" name="usuario" type="text" autocomplete="username" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 input-focus focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Ingrese su usuario o email">
                        </div>
                        <p class="mt-1 text-xs text-gray-500" id="usuarioError"></p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                required
                                class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md placeholder-gray-400 input-focus focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Ingrese su contraseña">
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i id="passwordIcon" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" id="passwordError"></p>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Recordar sesión
                        </label>
                    </div>
                </div>

                <!-- Botón de envío -->
                <div>
                    <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-indigo-500 group-hover:text-indigo-400"></i>
                        </span>
                        <span id="submitText">Iniciar Sesión</span>
                        <span id="submitSpinner" class="hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <p class="mt-6 text-center text-sm text-gray-600">
                © <?php echo date('Y'); ?> Sistema Financiero. Todos los derechos reservados.
            </p>
        </div>
    </div>

    <script>
        // Manejar envío del formulario
        $('#loginForm').on('submit', function (e) {
            e.preventDefault();

            // Limpiar errores anteriores
            clearErrors();
            closeAlert();

            // Obtener datos del formulario
            const usuario = $('#usuario').val().trim();
            const password = $('#password').val();
            const remember = $('#remember').is(':checked');

            // Validación básica
            let isValid = true;

            if (!usuario || usuario.length < 3) {
                showFieldError('usuario', 'Usuario o email es requerido (mínimo 3 caracteres)');
                isValid = false;
            }

            if (!password || password.length < 6) {
                showFieldError('password', 'Contraseña es requerida (mínimo 6 caracteres)');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            // Deshabilitar botón y mostrar spinner
            const $submitBtn = $('#submitBtn');
            const $submitText = $('#submitText');
            const $submitSpinner = $('#submitSpinner');

            $submitBtn.prop('disabled', true);
            $submitText.text('Iniciando sesión...');
            $submitSpinner.removeClass('hidden');

            // Construir URL base dinámicamente desde la ubicación actual del navegador
            // Esto permite que funcione tanto con localhost como con IP de red local
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;

            // Extraer el path base del proyecto (hasta 'sistema-financiera')
            let basePath = pathname.substring(0, pathname.indexOf('/public'));
            if (!basePath) {
                // Fallback: buscar 'sistema-financiera' en el path
                const projectIndex = pathname.indexOf('sistema-financiera');
                if (projectIndex !== -1) {
                    basePath = pathname.substring(0, projectIndex + 'sistema-financiera'.length);
                } else {
                    basePath = '';
                }
            }

            const baseUrl = protocol + '//' + host + basePath;

            // Debug: verificar baseUrl
            console.log('Base URL construida:', baseUrl);
            console.log('Protocol:', protocol);
            console.log('Host:', host);
            console.log('Base Path:', basePath);

            $.ajax({
                url: baseUrl + '/app/api/auth/login.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    usuario: usuario,
                    password: password
                }),
                success: function (response) {
                    if (response.success) {
                        // Guardar token en localStorage
                        localStorage.setItem('auth_token', response.data.token);
                        localStorage.setItem('user_data', JSON.stringify(response.data.user));

                        // Si se marcó "Recordar", guardar en cookie
                        if (remember) {
                            const expires = new Date();
                            expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000)); // 24 horas
                            document.cookie = `auth_token=${response.data.token}; expires=${expires.toUTCString()}; path=/`;
                        }

                        // Mostrar mensaje de éxito
                        showAlert('success', 'Inicio de sesión exitoso. Redirigiendo...');

                        // Redirigir según rol
                        // Guardar baseUrl en una variable local para asegurar que esté disponible
                        const currentBaseUrl = baseUrl;
                        const userRole = response.data.user.rol_nombre || response.data.user.rol;

                        setTimeout(function () {
                            let redirectUrl = '';

                            // Lógica mejorada de redirección en JS
                            if (['admin', 'Administrador', 'Supervisor', 'Gerente', 'Cajero', 'Asesor'].includes(userRole)) {
                                redirectUrl = currentBaseUrl + '/public/admin/dashboard.php';
                            } else if (['cobrador', 'Cobrador'].includes(userRole)) {
                                redirectUrl = currentBaseUrl + '/public/cobrador/home.php';
                            } else if (['cliente', 'Cliente'].includes(userRole)) {
                                redirectUrl = currentBaseUrl + '/public/cliente/index.php';
                            } else {
                                // Fallback inteligente o error
                                console.warn('Rol no reconocido para redirección automática:', userRole);
                                // Intentar admin por defecto si tiene permisos de dashboard (no podemos verificar permisos aquí fácilmente, asumimos login)
                                redirectUrl = currentBaseUrl + '/public/admin/dashboard.php';
                            }

                            console.log('Base URL usada:', currentBaseUrl);
                            console.log('Rol del usuario:', userRole);
                            console.log('Redirigiendo a:', redirectUrl);

                            // Verificar que la URL sea válida antes de redirigir
                            if (redirectUrl && redirectUrl.startsWith('http')) {
                                window.location.href = redirectUrl;
                            } else {
                                console.error('URL de redirección inválida:', redirectUrl);
                                alert('Error: URL de redirección inválida');
                            }
                        }, 1000);

                    } else {
                        showAlert('error', response.message || 'Error al iniciar sesión');
                        resetSubmitButton();
                    }
                },
                error: function (xhr) {
                    let message = 'Error al conectar con el servidor';

                    if (xhr.responseJSON) {
                        message = xhr.responseJSON.message || message;

                        // Mostrar errores de validación
                        if (xhr.responseJSON.errors) {
                            Object.keys(xhr.responseJSON.errors).forEach(function (field) {
                                showFieldError(field, xhr.responseJSON.errors[field]);
                            });
                        }
                    }

                    showAlert('error', message);
                    resetSubmitButton();
                }
            });
        });

        // Funciones auxiliares
        function showAlert(type, message) {
            const $alert = $('#alertMessage');
            const $icon = $('#alertIcon');
            const $text = $('#alertText');

            $alert.removeClass('hidden');

            if (type === 'success') {
                $alert.removeClass('bg-red-50').addClass('bg-green-50');
                $icon.removeClass('fa-exclamation-circle text-red-400').addClass('fa-check-circle text-green-400');
                $text.removeClass('text-red-800').addClass('text-green-800');
            } else {
                $alert.removeClass('bg-green-50').addClass('bg-red-50');
                $icon.removeClass('fa-check-circle text-green-400').addClass('fa-exclamation-circle text-red-400');
                $text.removeClass('text-green-800').addClass('text-red-800');
            }

            $text.text(message);
        }

        function closeAlert() {
            $('#alertMessage').addClass('hidden');
        }

        function showFieldError(field, message) {
            $(`#${field}Error`).text(message).addClass('text-red-600');
            $(`#${field}`).addClass('border-red-500');
        }

        function clearErrors() {
            $('.text-red-600').text('').removeClass('text-red-600');
            $('.border-red-500').removeClass('border-red-500');
        }

        function resetSubmitButton() {
            $('#submitBtn').prop('disabled', false);
            $('#submitText').text('Iniciar Sesión');
            $('#submitSpinner').addClass('hidden');
        }

        function togglePassword() {
            const $password = $('#password');
            const $icon = $('#passwordIcon');

            if ($password.attr('type') === 'password') {
                $password.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $password.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }

        // Permitir envío con Enter
        $('#usuario, #password').on('keypress', function (e) {
            if (e.which === 13) {
                $('#loginForm').submit();
            }
        });
    </script>
</body>

</html>