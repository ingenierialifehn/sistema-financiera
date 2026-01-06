<?php
/**
 * Actualizar cobrador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (!isset($data['id']) || empty($data['id'])) {
    Response::error('ID de cobrador requerido', 400);
}

$id = intval($data['id']);

// Validar que el cobrador existe
$checkStmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id AND rol = 'cobrador'");
$checkStmt->execute(['id' => $id]);
$cobradorAnterior = $checkStmt->fetch();

if (!$cobradorAnterior) {
    Response::notFound('Cobrador no encontrado');
}

// Validar datos
$rules = [
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
    ],
    'password' => [
        'type' => 'string',
        'required' => false,
        'min' => 6,
        'max' => 255,
        'message' => 'La contraseña debe tener al menos 6 caracteres'
    ]
];

$validation = Validator::validate($data, $rules);

if (!$validation['valid']) {
    Response::error('Datos inválidos', 400, $validation['errors']);
}

$validatedData = $validation['data'];

try {
    // Verificar que el email no esté en uso por otro usuario
    $emailCheckStmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
    $emailCheckStmt->execute([
        'email' => $validatedData['email'],
        'id' => $id
    ]);
    
    if ($emailCheckStmt->fetch()) {
        Response::error('El email ya está en uso por otro usuario', 400);
    }
    
    // Preparar datos para actualizar
    $updateFields = [];
    $params = ['id' => $id];
    
    if (isset($validatedData['nombre_completo'])) {
        $updateFields[] = "nombre_completo = :nombre_completo";
        $params['nombre_completo'] = $validatedData['nombre_completo'];
    }
    
    if (isset($validatedData['email'])) {
        $updateFields[] = "email = :email";
        $params['email'] = $validatedData['email'];
    }
    
    if (isset($validatedData['password']) && !empty($validatedData['password'])) {
        $updateFields[] = "password = :password";
        $params['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);
    }
    
    if (isset($data['estado'])) {
        if (in_array($data['estado'], ['activo', 'inactivo'])) {
            $updateFields[] = "estado = :estado";
            $params['estado'] = $data['estado'];
        }
    }
    
    if (empty($updateFields)) {
        Response::error('No hay datos para actualizar', 400);
    }
    
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    
    $sql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Obtener datos actualizados
    $getStmt = $db->prepare("
        SELECT id, usuario, nombre_completo, email, rol, estado, created_at, updated_at
        FROM usuarios
        WHERE id = :id
    ");
    $getStmt->execute(['id' => $id]);
    $cobradorActualizado = $getStmt->fetch();
    
    // Registrar actividad
    Auth::logActivity(
        $user['id'], 
        'update', 
        'cobrador', 
        "Cobrador actualizado: {$validatedData['nombre_completo']}", 
        $cobradorAnterior, 
        $cobradorActualizado
    );
    
    Response::success($cobradorActualizado, 'Cobrador actualizado exitosamente');
    
} catch (Exception $e) {
    error_log("Error al actualizar cobrador: " . $e->getMessage());
    Response::serverError('Error al actualizar cobrador');
}

