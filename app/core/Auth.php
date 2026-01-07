<?php
/**
 * Clase Auth - Manejo de autenticación y autorización
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Response.php';

class Auth
{

    /**
     * Generar token de sesión
     */
    private static function generateToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Iniciar sesión (acepta usuario o email)
     */
    public static function login($usuarioOrEmail, $password)
    {
        $db = getDB();

        try {
            // Buscar por usuario o email
            // Nota: PDO requiere que los parámetros con el mismo nombre se pasen dos veces
            // o usar parámetros diferentes para cada uso
            $stmt = $db->prepare("
                SELECT u.id_usuario, u.username, u.password_hash, c.nombre_completo, c.email, 
                       r.nombre_rol as rol_nombre, r.id_rol, r.permisos,
                       u.estado, c.id_agencia, u.id_jefe_directo, u.token_autorizacion, c.id_colaborador 
                FROM usuarios u
                INNER JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                WHERE (u.username = :identificador OR c.email = :identificador2) 
                AND u.estado = 'Activo'
            ");
            $stmt->execute([
                'identificador' => $usuarioOrEmail,
                'identificador2' => $usuarioOrEmail
            ]);
            $user = $stmt->fetch();

            if (!$user) {
                // Registrar intento fallido en log (sin interrumpir si falla)
                try {
                    self::logActivity(null, 'login_failed', 'auth', 'Intento de login fallido: ' . $usuarioOrEmail);
                } catch (Exception $e) {
                    error_log("Error al registrar log: " . $e->getMessage());
                }
                return Response::error('Usuario o contraseña incorrectos', 401);
            }

            // Verificar contraseña
            if (!password_verify($password, $user['password_hash'])) {
                // Registrar intento fallido en log (sin interrumpir si falla)
                try {
                    self::logActivity($user['id_usuario'], 'login_failed', 'auth', 'Contraseña incorrecta');
                } catch (Exception $e) {
                    error_log("Error al registrar log: " . $e->getMessage());
                }
                return Response::error('Usuario o contraseña incorrectos', 401);
            }

            // Generar token de sesión
            $token = self::generateToken();
            $tokenExpiration = date('Y-m-d H:i:s', time() + TOKEN_EXPIRATION);

            // Actualizar token en base de datos
            // Actualizar token en base de datos
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET token_sesion = :token, 
                    token_expiracion = :expiration,
                    ultimo_acceso = NOW()
                WHERE id_usuario = :id
            ");
            $stmt->execute([
                'token' => $token,
                'expiration' => $tokenExpiration,
                'id' => $user['id_usuario']
            ]);

            // Registrar log exitoso (antes de iniciar sesión para evitar problemas con headers)
            // Registrar log exitoso (antes de iniciar sesión para evitar problemas con headers)
            try {
                self::logActivity($user['id_usuario'], 'login', 'auth', 'Inicio de sesión exitoso');
            } catch (Exception $logError) {
                // Si falla el log, no interrumpir el login
                error_log("Error al registrar log de login: " . $logError->getMessage());
            }

            // Iniciar sesión PHP (para uso web)
            // Solo si no se han enviado headers todavía
            if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
                session_start();

                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);

                // Guardar datos en sesión PHP
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_token'] = $token;
                // Variables solicitadas específicamente
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['id_rol'] = $user['id_rol']; // Ahora es el ID de la tabla roles
                $_SESSION['rol_nombre'] = $user['rol_nombre']; // Nombre del rol
                $_SESSION['permisos'] = json_decode($user['permisos'], true); // Permisos decodificados
                $_SESSION['id_agencia'] = $user['id_agencia'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['id_colaborador'] = $user['id_colaborador'];

                // Compatibilidad con código anterior que usa 'user_role'
                $_SESSION['user_role'] = $user['rol_nombre'];

                // Token de autorización para Supervisor/Gerente
                if (in_array($user['rol_nombre'], ['Supervisor', 'Gerente'])) {
                    if (!empty($user['token_autorizacion'])) {
                        $_SESSION['token_autorizacion'] = $user['token_autorizacion'];
                    }
                }
            }

            // Retornar datos del usuario sin password ni token de autorización sensible
            unset($user['password_hash']);
            // Nota: token_autorizacion se mantiene en $user array retornado por si el frontend lo necesita inmediatamente,
            // pero lo eliminamos si se considera sensible para exponer en la respuesta JSON.
            // Para seguridad, no lo enviamos al frontend a menos que sea necesario.
            unset($user['token_autorizacion']);

            // Decodificar permisos para la respuesta API
            $user['permisos'] = json_decode($user['permisos'], true);

            return Response::success([
                'user' => $user,
                'token' => $token,
                'expires_at' => $tokenExpiration
            ], 'Login exitoso');

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // En desarrollo, mostrar el error real
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                return Response::error('Error en login: ' . $e->getMessage(), 500);
            } else {
                return Response::serverError('Error al iniciar sesión');
            }
        }
    }

    /**
     * Verificar token de sesión
     */
    public static function verifyToken($token)
    {
        if (empty($token)) {
            return null;
        }

        $db = getDB();

        try {
            $stmt = $db->prepare("
                SELECT u.id_usuario, u.username, c.nombre_completo, c.email, 
                       r.nombre_rol as rol_nombre, r.id_rol, r.permisos,
                       u.estado, c.id_agencia, u.id_jefe_directo, u.token_autorizacion, c.id_colaborador 
                FROM usuarios u
                INNER JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.token_sesion = :token 
                AND u.token_expiracion > NOW() 
                AND u.estado = 'Activo'
            ");
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();

            if ($user) {
                // Actualizar último acceso
                $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id")
                    ->execute(['id' => $user['id_usuario']]);
            }

            return $user;

        } catch (Exception $e) {
            error_log("Error verificando token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario actual
     */
    public static function getCurrentUser()
    {
        $token = self::getTokenFromRequest();
        return self::verifyToken($token);
    }

    /**
     * Requerir autenticación
     */
    public static function requireAuth()
    {
        $user = self::getCurrentUser();

        if (!$user) {
            Response::unauthorized('Token de sesión inválido o expirado');
        }

        return $user;
    }

    /**
     * Requerir rol específico
     */
    public static function requireRole($roles)
    {
        $user = self::requireAuth();

        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Verificar usando el nombre del rol (desde la nueva tabla)
        // Nota: $user viene de verifyToken, ver columnas select
        $userRole = $user['rol_nombre'] ?? '';

        if (!in_array($userRole, $roles)) {
            Response::forbidden('No tiene permisos para esta acción');
        }

        return $user;
    }

    /**
     * Verificar si tiene permiso específico (Nueva función)
     */
    public static function hasPermission($permissionKey)
    {
        $user = self::getCurrentUser();
        if (!$user)
            return false;

        $permisos = json_decode($user['permisos'], true);

        // Special: 'readonly' must be explicitly set, never inherited by Admin or 'todos'
        if ($permissionKey === 'readonly') {
            return isset($permisos['readonly']) && $permisos['readonly'] === true;
        }

        // Si es Admin, tiene todo (o verificamos el flag 'todos')
        if ($user['rol_nombre'] === 'Administrador' || $user['rol_nombre'] === 'admin') {
            return true;
        }



        // Verificar flag 'todos'
        if (isset($permisos['todos']) && $permisos['todos'] === true) {
            return true;
        }

        // 1. Direct match or Matrix 'view' check
        if (isset($permisos[$permissionKey])) {
            $p = $permisos[$permissionKey];
            if ($p === true)
                return true; // Legacy boolean
            if (is_array($p) && isset($p['view']) && $p['view'] === true)
                return true; // Matrix view
        }

        // 2. Dot notation (module.action)
        if (strpos($permissionKey, '.') !== false) {
            list($module, $action) = explode('.', $permissionKey, 2);
            if (isset($permisos[$module])) {
                $p = $permisos[$module];
                if ($p === true)
                    return true; // Full access implies specific action
                if (is_array($p) && isset($p[$action]) && $p[$action] === true)
                    return true;
            }
        }

        return false;
    }

    /**
     * Requerir permiso específico
     */
    public static function requirePermission($permissionKey)
    {
        if (!self::hasPermission($permissionKey)) {
            Response::forbidden('No tiene permiso para realizar esta acción');
        }
    }

    /**
     * Obtener token del request (prioridad: header > session > cookie > GET/POST)
     */
    private static function getTokenFromRequest()
    {
        // 1. Buscar en header Authorization (para API)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }

        // 2. Buscar en header personalizado
        if (isset($headers['X-Auth-Token'])) {
            return $headers['X-Auth-Token'];
        }

        // 3. Buscar en sesión PHP (para uso web)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_token'])) {
            return $_SESSION['user_token'];
        }

        // 4. Buscar en cookie
        if (isset($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }

        // 5. Buscar en GET o POST
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }

        if (isset($_POST['token'])) {
            return $_POST['token'];
        }

        return null;
    }

    /**
     * Verificar autenticación desde sesión PHP (para uso web)
     */
    public static function checkSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_token'])) {
            return null;
        }

        // Verificar que el token de sesión sea válido
        $user = self::verifyToken($_SESSION['user_token']);

        if (!$user) {
            // Token inválido, limpiar sesión
            self::destroySession();
            return null;
        }

        return $user;
    }

    /**
     * Destruir sesión PHP
     */
    public static function destroySession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar variables de sesión
        $_SESSION = array();

        // Destruir cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destruir cookie de autenticación
        if (isset($_COOKIE['auth_token'])) {
            setcookie('auth_token', '', time() - 3600, '/');
        }

        // Destruir sesión
        session_destroy();
    }

    /**
     * Cerrar sesión
     */
    public static function logout($token = null, $sendResponse = true)
    {
        if ($token === null) {
            $token = self::getTokenFromRequest();
        }

        $userId = null;

        if ($token) {
            $db = getDB();
            try {
                // Obtener usuario antes de eliminar token para el log
                $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE token_sesion = :token");
                $stmt->execute(['token' => $token]);
                $user = $stmt->fetch();
                $userId = $user['id_usuario'] ?? null;

                // Invalidar token en base de datos
                $db->prepare("UPDATE usuarios SET token_sesion = NULL, token_expiracion = NULL WHERE token_sesion = :token")
                    ->execute(['token' => $token]);

            } catch (Exception $e) {
                error_log("Error en logout: " . $e->getMessage());
            }
        }

        // Destruir sesión PHP
        self::destroySession();

        // Registrar log
        if ($userId) {
            self::logActivity($userId, 'logout', 'auth', 'Cierre de sesión');
        }

        if ($sendResponse) {
            return Response::success(null, 'Sesión cerrada exitosamente');
        }

        return true;
    }

    /**
     * Registrar actividad en log
     */
    public static function logActivity($usuarioId, $accion, $modulo, $descripcion = '', $datosAnteriores = null, $datosNuevos = null)
    {
        $db = getDB();

        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Convertir a JSON si es necesario
            $datosAnterioresJson = null;
            $datosNuevosJson = null;

            if ($datosAnteriores !== null) {
                $datosAnterioresJson = is_string($datosAnteriores) ? $datosAnteriores : json_encode($datosAnteriores);
            }

            if ($datosNuevos !== null) {
                $datosNuevosJson = is_string($datosNuevos) ? $datosNuevos : json_encode($datosNuevos);
            }

            $stmt = $db->prepare("
                INSERT INTO logs_actividad 
                (usuario_id, accion, modulo, descripcion, ip_address, user_agent, datos_anteriores, datos_nuevos)
                VALUES (:usuario_id, :accion, :modulo, :descripcion, :ip_address, :user_agent, :datos_anteriores, :datos_nuevos)
            ");

            $stmt->execute([
                'usuario_id' => $usuarioId,
                'accion' => $accion,
                'modulo' => $modulo,
                'descripcion' => $descripcion,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'datos_anteriores' => $datosAnterioresJson,
                'datos_nuevos' => $datosNuevosJson
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar log: " . $e->getMessage());
            // No lanzar excepción para no interrumpir el flujo principal
        }
    }
}

