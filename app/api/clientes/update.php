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

// Permitir PUT y POST (POST para archivos)
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación (solo admin puede actualizar)
    $user = AuthMiddleware::requireAdmin();

    // Obtener datos según el método
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
    } else {
        $input = getJsonInput();
    }

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
        'departamento' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Departamento inválido'
        ],
        'municipio' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Municipio inválido'
        ],
        'barrio' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Barrio inválido'
        ],
        'punto_referencia' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Punto de referencia inválido'
        ],
        'tipo_vivienda' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Tipo de vivienda inválido'
        ],
        'gps_coordenadas' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Coordenadas GPS inválidas'
        ],
        'fecha_nacimiento' => [
            'type' => 'date',
            'required' => false,
            'message' => 'Fecha de nacimiento inválida'
        ],
        'genero' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Género inválido'
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
        ],
        'id_agencia' => [
            'type' => 'integer',
            'required' => false,
            'message' => 'ID de agencia inválido'
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
        $stmt = $db->prepare("SELECT u.id_usuario FROM usuarios u WHERE u.id_usuario = :id AND u.estado = 'Activo'");
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
        'nombre_completo',
        'tipo_documento',
        'numero_documento',
        'email',
        'telefono',
        'direccion',
        'departamento',
        'municipio',
        'barrio',
        'punto_referencia',
        'tipo_vivienda',
        'gps_coordenadas',
        'fecha_nacimiento',
        'genero',
        'ocupacion',
        'estado',
        'cobrador_id',
        'id_agencia'
    ];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "{$field} = :{$field}";

            if ($field === 'numero_documento' && !empty($data['numero_documento'])) {
                $tipoDoc = $data['tipo_documento'] ?? $clienteExistente['tipo_documento'];
                $params[$field] = Validator::documento($data[$field], $tipoDoc);
            } elseif (in_array($field, ['nombre_completo', 'email', 'direccion', 'ocupacion', 'departamento', 'municipio', 'barrio', 'punto_referencia'])) {
                $params[$field] = !empty($data[$field]) ? Validator::sanitize($data[$field]) : null;
            } elseif ($field === 'telefono') {
                $params[$field] = Validator::phone($data[$field]);
            } elseif (in_array($field, ['cobrador_id', 'id_agencia'])) {
                $params[$field] = !empty($data[$field]) ? intval($data[$field]) : null;
            } else {
                $params[$field] = $data[$field];
            }
        }
    }

    // --- Procesamiento de Archivos (Expediente Digital) ---
    $fileFields = [
        'foto_dni_frontal',
        'foto_dni_posterior',
        'foto_perfil',
        'foto_fachada_casa',
        'foto_recibo_servicio',
        'foto_documento' // Legacy support
    ];

    $uploadDir = __DIR__ . '/../../../uploads/documentos/';

    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($fileFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];

            // Validar tipo (imágenes)
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file['type'], $allowedTypes)) {
                Response::error("El archivo {$field} debe ser una imagen JPG o PNG", 400);
            }

            // Validar tamaño (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                Response::error("El archivo {$field} no debe superar 5MB", 400);
            }

            // Generar nombre
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            // Usar nombre nuevo o existente para el archivo
            $nombreBase = $data['nombre_completo'] ?? $clienteExistente['nombre_completo'];
            $nombreCliente = preg_replace('/[^a-zA-Z0-9]/', '_', $nombreBase);
            $dni = $data['numero_documento'] ?? $clienteExistente['numero_documento'];
            $timestamp = time();

            $fileName = "{$dni}_{$nombreCliente}_{$field}_{$timestamp}.{$extension}";
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Eliminar archivo anterior si existe
                if (!empty($clienteExistente[$field])) {
                    $oldPath = $uploadDir . $clienteExistente[$field];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Agregar a campos a actualizar
                $updateFields[] = "{$field} = :{$field}";
                $params[$field] = $fileName;
            } else {
                Response::serverError("Error al subir el archivo {$field}");
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
        SELECT c.*, col.nombre_completo as cobrador_nombre 
        FROM clientes c
        LEFT JOIN usuarios u ON c.cobrador_id = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch();

    // Registrar log
    Auth::logActivity($user['id_usuario'], 'update', 'clientes', "Cliente actualizado: {$cliente['nombre_completo']}", $clienteExistente, $cliente);

    Response::success($cliente, 'Cliente actualizado exitosamente');

} catch (Exception $e) {
    error_log("Error en clientes/update.php: " . $e->getMessage());
    Response::serverError('Error al actualizar cliente');
}
