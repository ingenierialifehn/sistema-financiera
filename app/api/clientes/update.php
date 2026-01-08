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
    // Requerir autenticación
    $user = AuthMiddleware::requireAuth();

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

    // Validación simple manual - solo campos requeridos
    if (empty($input['nombre_completo']) || strlen(trim($input['nombre_completo'])) < 3) {
        Response::error('El nombre completo es requerido (mínimo 3 caracteres)', 400);
    }

    if (empty($input['numero_documento'])) {
        Response::error('El número de documento es requerido', 400);
    }

    // Usar los datos directamente del input
    $data = $input;

    // Verificar documento único si se actualiza (solo verificar duplicados, no validar formato)
    if (!empty($data['numero_documento']) && $data['numero_documento'] !== $clienteExistente['numero_documento']) {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :documento AND id != :id");
        $stmt->execute(['documento' => $data['numero_documento'], 'id' => $id]);
        if ($stmt->fetch()) {
            Response::error('Ya existe otro cliente con este número de documento', 409);
        }
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

            // Convertir a tipo apropiado sin validar
            if (in_array($field, ['cobrador_id', 'id_agencia'])) {
                $params[$field] = !empty($data[$field]) ? intval($data[$field]) : null;
            } elseif ($field === 'fecha_nacimiento') {
                // Fechas vacías deben ser NULL, no string vacío
                $params[$field] = (!empty($data[$field]) && $data[$field] !== '') ? $data[$field] : null;
            } else {
                // Aceptar el valor tal cual, incluso si está vacío
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
