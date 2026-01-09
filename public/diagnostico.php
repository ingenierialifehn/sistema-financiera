<?php
/**
 * Diagnóstico de Conexión - Sistema Financiero
 * Este archivo ayuda a diagnosticar problemas de acceso móvil
 */

header('Content-Type: application/json');

// Información del servidor
$diagnostico = [
    'timestamp' => date('Y-m-d H:i:s'),
    'servidor' => [
        'ip_servidor' => $_SERVER['SERVER_ADDR'] ?? 'No disponible',
        'puerto' => $_SERVER['SERVER_PORT'] ?? 'No disponible',
        'nombre_servidor' => $_SERVER['SERVER_NAME'] ?? 'No disponible',
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
    ],
    'cliente' => [
        'ip_cliente' => $_SERVER['REMOTE_ADDR'] ?? 'No disponible',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible',
        'host' => $_SERVER['HTTP_HOST'] ?? 'No disponible',
    ],
    'request' => [
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'No disponible',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'No disponible',
        'protocolo' => $_SERVER['SERVER_PROTOCOL'] ?? 'No disponible',
        'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Sí' : 'No',
    ],
    'php' => [
        'version' => PHP_VERSION,
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'Activa' : 'Inactiva',
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
    ],
    'paths' => [
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'No disponible',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'No disponible',
    ],
    'urls_construidas' => [
        'base_url_calculada' => construirBaseUrl(),
        'api_login_url' => construirBaseUrl() . '/app/api/auth/login.php',
    ]
];

// Verificar conexión a base de datos
try {
    require_once __DIR__ . '/../app/config/database.php';
    $db = getDB();
    $diagnostico['base_datos'] = [
        'estado' => 'Conectada',
        'mensaje' => 'Conexión exitosa a la base de datos'
    ];
} catch (Exception $e) {
    $diagnostico['base_datos'] = [
        'estado' => 'Error',
        'mensaje' => $e->getMessage()
    ];
}

// Verificar archivos críticos
$archivos_criticos = [
    'config.php' => __DIR__ . '/../app/config/config.php',
    'database.php' => __DIR__ . '/../app/config/database.php',
    'Auth.php' => __DIR__ . '/../app/core/Auth.php',
    'login_api' => __DIR__ . '/../app/api/auth/login.php',
];

$diagnostico['archivos'] = [];
foreach ($archivos_criticos as $nombre => $ruta) {
    $diagnostico['archivos'][$nombre] = [
        'existe' => file_exists($ruta) ? 'Sí' : 'No',
        'legible' => is_readable($ruta) ? 'Sí' : 'No',
        'ruta' => $ruta
    ];
}

// Verificar permisos de sesión
$diagnostico['sesion'] = [
    'cookie_params' => session_get_cookie_params(),
    'session_name' => session_name(),
    'session_save_path' => session_save_path(),
];

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Función auxiliar para construir la URL base
 */
function construirBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];

    // Extraer el path base del proyecto
    $path = dirname($script);

    // Encontrar la posición de 'sistema-financiera'
    $projectName = 'sistema-financiera';
    $pos = strpos($path, $projectName);

    if ($pos !== false) {
        $path = substr($path, 0, $pos + strlen($projectName));
    } else {
        $path = preg_replace('#/(public|app)(/.*)?$#', '', $path);
    }

    $path = rtrim($path, '/');

    return $protocol . '://' . $host . $path;
}
