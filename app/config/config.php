<?php
/**
 * Configuración General del Sistema
 */

// Configuración de entorno
define('ENVIRONMENT', 'development'); // development | production

// Configuración de errores
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Configuración de rutas
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('API_PATH', APP_PATH . '/api');

// Configuración de URLs (ajustar según tu dominio)
define('BASE_URL', 'http://localhost/sistema-financiera');
define('API_URL', BASE_URL . '/app/api');

// Función helper para obtener la URL base automáticamente
if (!function_exists('getBaseUrl')) {
    function getBaseUrl()
    {
        // Si BASE_URL está definido, usarlo directamente
        if (defined('BASE_URL') && strpos(BASE_URL, 'http') === 0) {
            return rtrim(BASE_URL, '/');
        }

        // Si no, construir desde el script actual
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];

        // Extraer el path base del proyecto
        // Buscar 'sistema-financiera' en el path y tomar todo hasta ahí
        $path = dirname($script);

        // Encontrar la posición de 'sistema-financiera'
        $projectName = 'sistema-financiera';
        $pos = strpos($path, $projectName);

        if ($pos !== false) {
            // Extraer desde el inicio hasta el final del nombre del proyecto
            $path = substr($path, 0, $pos + strlen($projectName));
        } else {
            // Fallback: remover subdirectorios comunes
            $path = preg_replace('#/(public|app)(/.*)?$#', '', $path);
        }

        // Si el path termina en /, removerlo
        $path = rtrim($path, '/');

        return $protocol . '://' . $host . $path;
    }
}

// Función helper para generar URLs relativas al proyecto
if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        $base = getBaseUrl();
        $path = ltrim($path, '/');
        return $base . ($path ? '/' . $path : '');
    }
}

// Configuración de sesión
define('SESSION_LIFETIME', 3600 * 8); // 8 horas
define('TOKEN_EXPIRATION', 3600 * 24); // 24 horas

// Configuración de sesión para acceso móvil en red local
// Esto permite que las sesiones funcionen tanto con localhost como con IP de red local
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax'); // Permite cookies en red local
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    // No establecer dominio específico para permitir acceso por IP
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_path', '/');
}

// Configuración de Cloudinary
define('CLOUDINARY_CLOUD_NAME', '');
define('CLOUDINARY_API_KEY', '');
define('CLOUDINARY_API_SECRET', '');
define('CLOUDINARY_UPLOAD_PRESET', '');



// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 6);
define('TOKEN_SECRET', 'cambiar_esta_clave_secreta_en_produccion_123456789');

// Configuración de archivos
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Headers CORS solo para API (cuando el script está en /app/api/)
// Solo aplicar headers JSON si es una petición de API
$isApiRequest = (
    strpos($_SERVER['SCRIPT_NAME'] ?? '', '/app/api/') !== false ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/app/api/') !== false ||
    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($isApiRequest) {
    // Headers CORS (ajustar según necesidades)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    // Manejo de preflight OPTIONS
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

