<?php
/**
 * API: Actualizar cliente
 * PUT /app/api/clientes/update.php?id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación (solo admin puede actualizar)
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    
    // Validar ID
    if (!isset($input['id']) || empty($input['id'])) {
        Response::error('ID de cliente es requerido', 400);
    }
    
    $id = intval($input['id']);
    
    $db = getDB();
    
    // Verificar que el cliente existe
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $clienteExistente = $stmt->fetch();
    
    if (!$clienteExistente) {
        Response::notFound('Cliente no encontrado');
    }
    
    // Validar datos (campos opcionales para actualización)
    $validation = Validator::validate($input, [
        'nombre_completo' => [
            'type' => 'string',
            'required' => false,
            'min' => 3,
            'max' => 100,
            'message' => 'Nombre completo inválido (3-100 caracteres)'
        ],
        'tipo_documento' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Tipo de documento inválido'
        ],
        'numero_documento' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Número de documento inválido'
        ],
        'telefono' => [
            'type' => 'phone',
            'required' => false,
            'message' => 'Teléfono inválido'
        ],
        'email' => [
            'type' => 'email',
            'required' => false,
            'message' => 'Email inválido'
        ],
        'direccion' => [
            'type' => 'string',
            'required' => false,
            'max' => 255,
            'message' => 'Dirección inválida'
        ],
        'fecha_nacimiento' => [
            'type' => 'date',
            'required' => false,
            'message' => 'Fecha de nacimiento inválida'
        ],
        'ocupacion' => [
            'type' => 'string',
            'required' => false,
            'max' => 100,
            'message' => 'Ocupación inválida'
        ],
        'estado' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Estado inválido'
        ],
        'cobrador_id' => [
            'type' => 'integer',
            'required' => false,
            'message' => 'ID de cobrador inválido'
        ]
    ]);
    
    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }
    
    $data = $validation['data'];
    
    // Verificar documento único si se actualiza
    if (!empty($data['numero_documento']) && $data['numero_documento'] !== $clienteExistente['numero_documento']) {
        $tipoDoc = $data['tipo_documento'] ?? $clienteExistente['tipo_documento'];
        $documentoValidado = Validator::documento($data['numero_documento'], $tipoDoc);
        
        if ($documentoValidado === false) {
            Response::error('Número de documento inválido', 400);
        }
        
        $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :documento AND id != :id");
        $stmt->execute(['documento' => $documentoValidado, 'id' => $id]);
        if ($stmt->fetch()) {
            Response::error('Ya existe otro cliente con este número de documento', 409);
        }
    }
    
    // Verificar cobrador si se especificó
    if (!empty($data['cobrador_id'])) {
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = :id AND rol = 'cobrador' AND estado = 'activo'");
        $stmt->execute(['id' => $data['cobrador_id']]);
        if (!$stmt->fetch()) {
            Response::error('Cobrador no válido', 400);
        }
    }
    
    // Validar estado si se actualiza
    if (!empty($data['estado']) && !in_array($data['estado'], ['activo', 'inactivo', 'en_mora', 'bloqueado'])) {
        Response::error('Estado inválido', 400);
    }
    
    // Construir UPDATE dinámico
    $updateFields = [];
    $params = ['id' => $id];
    
    $allowedFields = [
        'nombre_completo', 'tipo_documento', 'numero_documento', 'email', 
        'telefono', 'direccion', 'fecha_nacimiento', 'ocupacion', 'estado', 'cobrador_id'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "{$field} = :{$field}";
            
            if ($field === 'numero_documento' && !empty($data['numero_documento'])) {
                $tipoDoc = $data['tipo_documento'] ?? $clienteExistente['tipo_documento'];
                $params[$field] = Validator::documento($data[$field], $tipoDoc);
            } elseif ($field === 'nombre_completo' || $field === 'email' || $field === 'direccion' || $field === 'ocupacion') {
                $params[$field] = !empty($data[$field]) ? Validator::sanitize($data[$field]) : null;
            } elseif ($field === 'telefono') {
                $params[$field] = Validator::phone($data[$field]);
            } elseif ($field === 'cobrador_id') {
                $params[$field] = !empty($data[$field]) ? intval($data[$field]) : null;
            } else {
                $params[$field] = $data[$field];
            }
        }
    }
    
    if (empty($updateFields)) {
        Response::error('No hay campos para actualizar', 400);
    }
    
    $updateFields[] = "updated_at = NOW()";
    
    $sql = "UPDATE clientes SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Obtener cliente actualizado
    $stmt = $db->prepare("
        SELECT c.*, u.nombre_completo as cobrador_nombre 
        FROM clientes c
        LEFT JOIN usuarios u ON c.cobrador_id = u.id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch();
    
    // Registrar log
    Auth::logActivity($user['id'], 'update', 'clientes', "Cliente actualizado: {$cliente['nombre_completo']}", $clienteExistente, $cliente);
    
    Response::success($cliente, 'Cliente actualizado exitosamente');
    
} catch (Exception $e) {
    error_log("Error en clientes/update.php: " . $e->getMessage());
    Response::serverError('Error al actualizar cliente');
}

