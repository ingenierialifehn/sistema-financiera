<?php
/**
 * Crear nuevo colaborador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

// Validar datos
$rules = [
    'usuario' => [
        'type' => 'string',
        'required' => true,
        'min' => 3,
        'max' => 50,
        'message' => 'El usuario debe tener entre 3 y 50 caracteres'
    ],
    'password' => [
        'type' => 'string',
        'required' => true,
        'min' => 6,
        'max' => 255,
        'message' => 'La contraseña debe tener al menos 6 caracteres'
    ],
    'nombre_completo' => [
        'type' => 'string',
        'required' => true,
        'min' => 3,
        'max' => 255,
        'message' => 'El nombre completo es requerido'
    ],
    'email' => [
        'type' => 'email',
        'required' => true,
        'message' => 'Email inválido'
    ]
];

$validation = Validator::validate($data, $rules);

if (!$validation['valid']) {
    Response::error('Datos inválidos', 400, $validation['errors']);
}

$validatedData = $validation['data'];

try {
    // Verificar que el usuario no exista
    $checkStmt = $db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario OR email = :email");
    $checkStmt->execute([
        'usuario' => $validatedData['usuario'],
        'email' => $validatedData['email']
    ]);
    
    if ($checkStmt->fetch()) {
        Response::error('El usuario o email ya existe', 400);
    }
    
    // Hash de contraseña
    $passwordHash = password_hash($validatedData['password'], PASSWORD_DEFAULT);
    
    // Crear colaborador
    // NOTA: Se asume que el ENUM 'rol' ya incluye 'colaborador' como se hizo en pasos previos.
    $stmt = $db->prepare("
        INSERT INTO usuarios (usuario, password, nombre_completo, email, rol, estado)
        VALUES (:usuario, :password, :nombre_completo, :email, 'colaborador', :estado)
    ");
    
    $estado = $data['estado'] ?? 'activo';
    if (!in_array($estado, ['activo', 'inactivo'])) {
        $estado = 'activo';
    }
    
    $stmt->execute([
        'usuario' => $validatedData['usuario'],
        'password' => $passwordHash,
        'nombre_completo' => $validatedData['nombre_completo'],
        'email' => $validatedData['email'],
        'estado' => $estado
    ]);
    
    $colaboradorId = $db->lastInsertId();
    
    // Registrar actividad
    Auth::logActivity($user['id'], 'create', 'colaborador', "Colaborador creado: {$validatedData['nombre_completo']}", null, $validatedData);
    
    // Obtener colaborador creado
    $getStmt = $db->prepare("
        SELECT id, usuario, nombre_completo, email, rol, estado, created_at
        FROM usuarios
        WHERE id = :id
    ");
    $getStmt->execute(['id' => $colaboradorId]);
    $colaborador = $getStmt->fetch();
    
    Response::success($colaborador, 'Colaborador creado exitosamente');
    
} catch (Exception $e) {
    error_log("Error al crear colaborador: " . $e->getMessage());
    Response::serverError('Error al crear colaborador');
}
