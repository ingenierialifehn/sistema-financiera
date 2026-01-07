<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();
    $db = getDB();

    if (empty($_POST['cliente_id']) || empty($_POST['nombre_negocio']) || empty($_POST['rubro'])) {
        Response::error('Faltan campos obligatorios', 400);
    }

    $uploadDir = __DIR__ . '/../../../../uploads/negocios/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $db->beginTransaction();

    try {
        // 1. Insertar Negocio
        $uploadedData = [];
        $fileFields = ['doc_permiso_operaciones', 'foto_negocio_1', 'foto_negocio_2', 'foto_negocio_3', 'foto_negocio_4', 'foto_negocio_5'];

        foreach ($fileFields as $field) {
            $uploadedData[$field] = null;
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['nombre_negocio']);
                $timestamp = time();
                $fileName = "{$_POST['cliente_id']}_{$cleanName}_{$field}_{$timestamp}.{$extension}";
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    $uploadedData[$field] = $fileName;
                }
            }
        }

        $sql = "INSERT INTO clientes_negocios (
                    cliente_id, nombre_negocio, rubro, 
                    foto_negocio_1, foto_negocio_2, foto_negocio_3, foto_negocio_4, foto_negocio_5,
                    doc_permiso_operaciones, created_at
                ) VALUES (
                    :cliente_id, :nombre_negocio, :rubro,
                    :foto_negocio_1, :foto_negocio_2, :foto_negocio_3, :foto_negocio_4, :foto_negocio_5,
                    :doc_permiso_operaciones, NOW()
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':cliente_id' => $_POST['cliente_id'],
            ':nombre_negocio' => $_POST['nombre_negocio'],
            ':rubro' => $_POST['rubro'],
            ':foto_negocio_1' => $uploadedData['foto_negocio_1'],
            ':foto_negocio_2' => $uploadedData['foto_negocio_2'],
            ':foto_negocio_3' => $uploadedData['foto_negocio_3'],
            ':foto_negocio_4' => $uploadedData['foto_negocio_4'],
            ':foto_negocio_5' => $uploadedData['foto_negocio_5'],
            ':doc_permiso_operaciones' => $uploadedData['doc_permiso_operaciones']
        ]);

        $negocioId = $db->lastInsertId();

        // 2. Insertar Garantías - Procesamiento de Arrays
        if (isset($_POST['garantias_descripcion']) && is_array($_POST['garantias_descripcion'])) {
            $descripciones = $_POST['garantias_descripcion'];
            $valores = $_POST['garantias_valor'] ?? [];
            $files = $_FILES['garantias_fotos'] ?? null;

            foreach ($descripciones as $index => $descripcion) {
                if (empty($descripcion))
                    continue;

                $valor = $valores[$index] ?? 0;
                $fotoName = null;

                // Handle file upload for this index
                // Note: $_FILES['garantias_fotos']['name'][$index]
                if ($files && isset($files['name'][$index]) && $files['error'][$index] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$index];
                    $name = $files['name'][$index];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $cleanName = "Garantia_{$index}";
                    $timestamp = time();
                    $fileName = "{$_POST['cliente_id']}_NEG{$negocioId}_{$cleanName}_{$timestamp}.{$extension}";

                    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                        $fotoName = $fileName;
                    }
                }

                $stmtG = $db->prepare("INSERT INTO negocios_garantias (negocio_id, descripcion, valor, foto) VALUES (?, ?, ?, ?)");
                $stmtG->execute([$negocioId, $descripcion, $valor, $fotoName]);
            }
        }

        $db->commit();
        Response::success(['id' => $negocioId], 'Negocio y garantías registrados');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
