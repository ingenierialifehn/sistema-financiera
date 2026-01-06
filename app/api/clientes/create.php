<?php
/**
 * API: Crear cliente
 * POST /app/api/clientes/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación (solo admin puede crear clientes)
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    
    // Validar datos
    $validation = Validator::validate($input, [
        'nombre_completo' => [
            'type' => 'string',
            'required' => true,
            'min' => 3,
            'max' => 100,
            'message' => 'Nombre completo es requerido (3-100 caracteres)'
        ],
        'tipo_documento' => [
            'type' => 'string',
            'required' => true,
            'message' => 'Tipo de documento es requerido'
        ],
        'numero_documento' => [
            'type' => 'documento',
            'required' => true,
            'message' => 'Número de documento es requerido'
        ],
        'telefono' => [
            'type' => 'phone',
            'required' => true,
            'message' => 'Teléfono es requerido'
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
    
    // Validar tipo de documento
    if (!in_array($data['tipo_documento'], ['DNI', 'RUC', 'CE'])) {
        Response::error('Tipo de documento inválido', 400);
    }
    
    // Validar número de documento según tipo
    $documentoValidado = Validator::documento($data['numero_documento'], $data['tipo_documento']);
    if ($documentoValidado === false) {
        Response::error('Número de documento inválido para el tipo seleccionado', 400);
    }
    
    $db = getDB();
    
    // Verificar que el documento no exista
    $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :documento");
    $stmt->execute(['documento' => $documentoValidado]);
    if ($stmt->fetch()) {
        Response::error('Ya existe un cliente con este número de documento', 409);
    }
    
    // Verificar cobrador si se especificó
    if (!empty($data['cobrador_id'])) {
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = :id AND rol = 'cobrador' AND estado = 'activo'");
        $stmt->execute(['id' => $data['cobrador_id']]);
        if (!$stmt->fetch()) {
            Response::error('Cobrador no válido', 400);
        }
    }
    
    // Generar código de cliente único
    $codigoCliente = generateClienteCode();
    
    // Verificar que el código sea único
    $stmt = $db->prepare("SELECT id FROM clientes WHERE codigo_cliente = :codigo");
    $stmt->execute(['codigo' => $codigoCliente]);
    $intentos = 0;
    while ($stmt->fetch() && $intentos < 10) {
        $codigoCliente = generateClienteCode();
        $stmt->execute(['codigo' => $codigoCliente]);
        $intentos++;
    }
    
    // Insertar cliente
    $stmt = $db->prepare("
        INSERT INTO clientes (
            codigo_cliente, nombre_completo, tipo_documento, numero_documento,
            email, telefono, direccion, fecha_nacimiento, ocupacion,
            cobrador_id, estado
        ) VALUES (
            :codigo_cliente, :nombre_completo, :tipo_documento, :numero_documento,
            :email, :telefono, :direccion, :fecha_nacimiento, :ocupacion,
            :cobrador_id, 'activo'
        )
    ");
    
    $stmt->execute([
        'codigo_cliente' => $codigoCliente,
        'nombre_completo' => Validator::sanitize($data['nombre_completo']),
        'tipo_documento' => $data['tipo_documento'],
        'numero_documento' => $documentoValidado,
        'email' => !empty($data['email']) ? Validator::sanitize($data['email']) : null,
        'telefono' => Validator::phone($data['telefono']),
        'direccion' => !empty($data['direccion']) ? Validator::sanitize($data['direccion']) : null,
        'fecha_nacimiento' => !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
        'ocupacion' => !empty($data['ocupacion']) ? Validator::sanitize($data['ocupacion']) : null,
        'cobrador_id' => !empty($data['cobrador_id']) ? intval($data['cobrador_id']) : null
    ]);
    
    $clienteId = $db->lastInsertId();
    
    // Obtener cliente creado
    $stmt = $db->prepare("
        SELECT c.*, u.nombre_completo as cobrador_nombre 
        FROM clientes c
        LEFT JOIN usuarios u ON c.cobrador_id = u.id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $clienteId]);
    $cliente = $stmt->fetch();
    
    // Registrar log
    Auth::logActivity($user['id'], 'create', 'clientes', "Cliente creado: {$data['nombre_completo']}", null, $cliente);
    
    Response::success($cliente, 'Cliente creado exitosamente', 201);
    
} catch (Exception $e) {
    error_log("Error en clientes/create.php: " . $e->getMessage());
    Response::serverError('Error al crear cliente');
}

