<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php'; // Assuming these exist based on the other file
require_once __DIR__ . '/../../../core/Validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();
    $db = getDB();

    // Validar campos obligatorios
    if (empty($_POST['cliente_id']) || empty($_POST['nombre_negocio']) || empty($_POST['rubro'])) {
        Response::error('Faltan campos obligatorios (Cliente, Nombre Negocio, Rubro)', 400);
    }

    $clienteId = $_POST['cliente_id'];

    // Verificar cliente
    $stmt = $db->prepare("SELECT id FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    if (!$stmt->fetch()) {
        Response::error('Cliente no encontrado', 404);
    }

    // Configurar directorio de subida
    // app/api/clientes/negocios -> uploads/negocios (4 levels up from script? No, Script is in app/api/clientes/negocios)
    // app/api/clientes/negocios -> app/api/clientes -> app/api -> app -> root -> uploads
    // So ../../../../uploads
    $uploadDir = __DIR__ . '/../../../../uploads/negocios/';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedData = [];

    // Campos de archivo esperados
    // doc_permiso_operaciones
    // foto_negocio_1 ... foto_negocio_5
    $fileFields = ['doc_permiso_operaciones'];
    for ($i = 1; $i <= 5; $i++) {
        $fileFields[] = "foto_negocio_$i";
    }

    foreach ($fileFields as $field) {
        $uploadedData[$field] = null;

        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];

            // Validaciones básicas (puedes expandir esto)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                // Si es foto de negocio, estrictamente imagen
                if (strpos($field, 'foto_negocio') !== false && $file['type'] === 'application/pdf') {
                    Response::error("El campo $field debe ser una imagen", 400);
                }
                // Si es permiso, puede ser pdf o imagen
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['nombre_negocio']);
            $timestamp = time();
            $fileName = "{$clienteId}_{$cleanName}_{$field}_{$timestamp}.{$extension}";
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $uploadedData[$field] = $fileName;
            } else {
                Response::error("Error al subir archivo $field", 500);
            }
        }
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO clientes_negocios (
                cliente_id, nombre_negocio, rubro, 
                foto_negocio_1, foto_negocio_2, foto_negocio_3, foto_negocio_4, foto_negocio_5,
                doc_permiso_operaciones, garantia_descripcion, garantia_valor, created_at
            ) VALUES (
                :cliente_id, :nombre_negocio, :rubro,
                :foto_negocio_1, :foto_negocio_2, :foto_negocio_3, :foto_negocio_4, :foto_negocio_5,
                :doc_permiso_operaciones, :garantia_descripcion, :garantia_valor, NOW()
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':cliente_id' => $clienteId,
        ':nombre_negocio' => $_POST['nombre_negocio'],
        ':rubro' => $_POST['rubro'],
        ':foto_negocio_1' => $uploadedData['foto_negocio_1'],
        ':foto_negocio_2' => $uploadedData['foto_negocio_2'],
        ':foto_negocio_3' => $uploadedData['foto_negocio_3'],
        ':foto_negocio_4' => $uploadedData['foto_negocio_4'],
        ':foto_negocio_5' => $uploadedData['foto_negocio_5'],
        ':doc_permiso_operaciones' => $uploadedData['doc_permiso_operaciones'],
        ':garantia_descripcion' => $_POST['garantia_descripcion'] ?? null,
        ':garantia_valor' => !empty($_POST['garantia_valor']) ? $_POST['garantia_valor'] : null
    ]);

    Response::success(['id' => $db->lastInsertId()], 'Negocio registrado exitosamente');

} catch (Exception $e) {
    error_log("Error en create_negocio: " . $e->getMessage());
    Response::serverError('Error interno del servidor: ' . $e->getMessage());
}
