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

    // Support JSON or POST
    $input = $_POST;
    if (empty($input)) {
        $json = file_get_contents('php://input');
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }

    // Map Aliases
    $negocioId = $input['negocio_id'] ?? $input['id'] ?? null;
    $nombre = $input['nombre_negocio'] ?? null;
    $rubro = $input['rubro'] ?? $input['tipo_negocio'] ?? null; // Handle JS alias
    $direccion = $input['direccion_negocio'] ?? '';
    // Ensure numeric
    $ingresos = !empty($input['ingresos_promedio']) ? floatval($input['ingresos_promedio']) : 0;

    if (empty($negocioId) || empty($nombre) || empty($rubro)) {
        Response::error('Faltan campos obligatorios: ID, Nombre, o Rubro', 400);
    }

    $uploadDir = __DIR__ . '/../../../../uploads/negocios/';

    // Get current files to preserve if not updated
    $stmtCurrent = $db->prepare("SELECT * FROM clientes_negocios WHERE id = ?");
    $stmtCurrent->execute([$negocioId]);
    $currentData = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

    if (!$currentData) {
        Response::error('Negocio no encontrado', 404);
    }

    $db->beginTransaction();

    try {
        // 1. Update Negocio Data
        $updatedFiles = [];
        $fileFields = ['doc_permiso_operaciones', 'foto_negocio_1', 'foto_negocio_2', 'foto_negocio_3', 'foto_negocio_4', 'foto_negocio_5'];

        foreach ($fileFields as $field) {
            $updatedFiles[$field] = $currentData[$field]; // Default to old file
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                // Upload new file
                $file = $_FILES[$field];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', $nombre);
                $timestamp = time();
                $fileName = "{$currentData['cliente_id']}_{$cleanName}_{$field}_{$timestamp}.{$extension}";
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    $updatedFiles[$field] = $fileName;
                    // Optional: Delete old file here
                }
            }
        }

        $sql = "UPDATE clientes_negocios SET 
                    nombre_negocio = :nombre, 
                    rubro = :rubro,
                    direccion_negocio = :direccion,
                    ingresos_promedio = :ingresos,
                    foto_negocio_1 = :f1, foto_negocio_2 = :f2, foto_negocio_3 = :f3, foto_negocio_4 = :f4, foto_negocio_5 = :f5,
                    doc_permiso_operaciones = :doc
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':rubro' => $rubro,
            ':direccion' => $direccion,
            ':ingresos' => $ingresos,
            ':f1' => $updatedFiles['foto_negocio_1'],
            ':f2' => $updatedFiles['foto_negocio_2'],
            ':f3' => $updatedFiles['foto_negocio_3'],
            ':f4' => $updatedFiles['foto_negocio_4'],
            ':f5' => $updatedFiles['foto_negocio_5'],
            ':doc' => $updatedFiles['doc_permiso_operaciones'],
            ':id' => $negocioId
        ]);

        // 2. Update Garantias
        // Strategy: Delete all existing guarantees for this business and re-insert the ones sent.
        // This handles additions, removals, and updates (as re-creations).
        // Since we don't strictly need to track guarantee history/IDs, this is cleanest for the dynamic list.

        // However, we need to preserve PHOTOS if they haven't changed.
        // The submitted form will have garantias_descripcion[] etc.
        // For photos, if a file is uploaded, great. If not, we might be losing the old photo if we just delete rows.
        // SOLUTION: The form needs to tell us "keep existing photo X".
        // But simplifying: Only manual re-upload supported or comprehensive logic?
        // Let's try to be smart. We can't easily map the new array index to the old database row without IDs.
        // Better: We will delete all old rows. But if the user didn't re-upload a photo, they lost it? 
        // We should demand re-upload OR better, handle this later.
        // Given constraints and user request "modificar", losing photos on edit is bad.
        // Let's just DELETE ALL and assume user re-enters or we keep it simple for now and I'll add "KEEP PHOTO" logic if needed.
        // Actually, let's look at the JS. If I populate the form, I can't populate file inputs.
        // So editing guarantees with photos is hard without keeping track of IDs.
        // FOR MVP: Changing basic business info is easy. Changing guarantees... let's just DELETE existing guarantees and add the new ones provided. 
        // WARNING: This deletes old guarantee photos from DB records (files remain).
        // If the user opens Edit, sees empty guarantee list (because populating file inputs is impossible), and saves, they lose guarantees.
        // We must populate the text inputs in JS. For photos, we can show a "Current Photo" text/preview.
        // If a new photo is empty, we check if there's a hidden field "existing_photo_X".

        // Let's implement robust sync:
        // We delete all guarantees first? No, we need to know previous photos.
        // Let's fetch old guarantees inside the loop or map them?
        // Actually, easiest reasonable path:
        // Client sends `garantias_id[]` for existing ones. If empty/0, it's new.
        // If `garantias_id` present, we update text. If file uploaded, update file.
        // If `garantias_id` NOT in the new list, we delete the missing ones.

        // But the current `create.php` logic was just arrays.
        // Let's stick to: Delete all and re-insert. User has to re-add guarantees? No that's annoying.
        // OK, I will try to implement "Append Only" or simple update.
        // Let's just create the file with basic update logic first.

        // REVISED PLAN FOR GUARANTEES:
        // We will wipe existing guarantees and re-insert. 
        // BUT, to preserve photos, the frontend must send the old filename in a hidden input `garantias_existing_foto[]`.

        $db->exec("DELETE FROM negocios_garantias WHERE negocio_id = $negocioId");

        if (isset($_POST['garantias_descripcion']) && is_array($_POST['garantias_descripcion'])) {
            $descripciones = $_POST['garantias_descripcion'];
            $valores = $_POST['garantias_valor'] ?? [];
            $existingFotos = $_POST['garantias_existing_foto'] ?? []; // Array of filenames to keep
            $files = $_FILES['garantias_fotos'] ?? null;

            foreach ($descripciones as $index => $descripcion) {
                if (empty($descripcion))
                    continue;

                $valor = $valores[$index] ?? 0;
                $fotoName = $existingFotos[$index] ?? null; // Keep old if present

                // If new file uploaded, overwrite
                if ($files && isset($files['name'][$index]) && $files['error'][$index] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$index];
                    $name = $files['name'][$index];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $cleanName = "Garantia_{$index}";
                    $timestamp = time();
                    $fileName = "{$currentData['cliente_id']}_NEG{$negocioId}_{$cleanName}_{$timestamp}.{$extension}";

                    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                        $fotoName = $fileName;
                    }
                }

                $stmtG = $db->prepare("INSERT INTO negocios_garantias (negocio_id, descripcion, valor, foto) VALUES (?, ?, ?, ?)");
                $stmtG->execute([$negocioId, $descripcion, $valor, $fotoName]);
            }
        }

        $db->commit();
        Response::success([], 'Negocio actualizado correctamente');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
