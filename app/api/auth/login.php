<?php
/**
 * API: Login
 * POST /api/auth/login.php
 */

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla, solo en logs
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../core/Helpers.php';
    require_once __DIR__ . '/../../core/Auth.php';
    require_once __DIR__ . '/../../core/Validator.php';
    require_once __DIR__ . '/../../core/Response.php';
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar archivos: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $input = getJsonInput();
    
    // Si no hay JSON, intentar obtener de POST
    if (empty($input)) {
        $input = $_POST;
    }
    
    // Validar datos (acepta usuario o email)
    $validation = Validator::validate($input, [
        'usuario' => [
            'type' => 'string',
            'required' => true,
            'min' => 3,
            'max' => 100,
            'message' => 'Usuario o email es requerido'
        ],
        'password' => [
            'type' => 'string',
            'required' => true,
            'min' => 6,
            'message' => 'Contraseña es requerida (mínimo 6 caracteres)'
        ]
    ]);
    
    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }
    
    // Sanitizar entrada
    $usuarioOrEmail = Validator::sanitize($validation['data']['usuario']);
    $password = $validation['data']['password'];
    
    // Realizar login
    Auth::login($usuarioOrEmail, $password);
    
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // En desarrollo, mostrar el error real
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        Response::error('Error: ' . $e->getMessage(), 500);
    } else {
        Response::serverError('Error al procesar la solicitud');
    }
}

