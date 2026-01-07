<?php
/**
 * API: Crear cliente (versión simplificada para estructura actual)
 * POST /app/api/clientes/create_with_files.php
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
    // Requerir autenticación
    $user = AuthMiddleware::requireAuth();

    // Obtener id_agencia del usuario logueado si no se envió
    if (empty($_POST['id_agencia'])) {
        $_POST['id_agencia'] = $user['id_agencia'] ?? null;
    }

    // Validar datos básicos
    $requiredFields = ['nombre_completo', 'tipo_documento', 'numero_documento', 'telefono'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            Response::error("El campo {$field} es requerido", 400);
        }
    }

    // Validar tipo de documento
    if (!in_array($_POST['tipo_documento'], ['DNI', 'RUC', 'CE'])) {
        Response::error('Tipo de documento inválido', 400);
    }

    $db = getDB();

    // Verificar que el documento no exista
    $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :documento");
    $stmt->execute(['documento' => $_POST['numero_documento']]);
    if ($stmt->fetch()) {
        Response::error('Ya existe un cliente con este número de documento', 409);
    }

    // Generar código de cliente único
    $codigoCliente = generateClienteCode();
    $stmt = $db->prepare("SELECT id FROM clientes WHERE codigo_cliente = :codigo");
    $stmt->execute(['codigo' => $codigoCliente]);
    $intentos = 0;
    while ($stmt->fetch() && $intentos < 10) {
        $codigoCliente = generateClienteCode();
        $stmt->execute(['codigo' => $codigoCliente]);
        $intentos++;
    }

    // Procesar archivo de documento (solo uno por ahora)
    $fotoDocumento = null;
    $uploadDir = __DIR__ . '/../../../uploads/documentos/';

    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Buscar cualquier archivo de imagen subido
    $imageFields = ['foto_dni_frontal', 'foto_dni_reverso', 'foto_perfil', 'foto_casa', 'foto_recibo', 'foto_documento'];
    foreach ($imageFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];

            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file['type'], $allowedTypes)) {
                continue; // Saltar si no es imagen válida
            }

            // Validar tamaño (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                continue; // Saltar si es muy grande
            }

            // Generar nombre de archivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombreCliente = preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['nombre_completo']);
            $dni = $_POST['numero_documento'];
            $fileName = "{$dni}_{$nombreCliente}_{$field}.{$extension}";
            $filePath = $uploadDir . $fileName;

            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $fotoDocumento = $fileName;
                break; // Solo guardar el primer archivo válido
            }
        }
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Insertar cliente con las columnas que existen
        $stmt = $db->prepare("
            INSERT INTO clientes (
                usuario_id, codigo_cliente, nombre_completo, tipo_documento, numero_documento,
                email, telefono, direccion, fecha_nacimiento, ocupacion,
                referencia_personal, telefono_referencia, foto_documento,
                id_agencia, estado, created_at
            ) VALUES (
                :usuario_id, :codigo_cliente, :nombre_completo, :tipo_documento, :numero_documento,
                :email, :telefono, :direccion, :fecha_nacimiento, :ocupacion,
                :referencia_personal, :telefono_referencia, :foto_documento,
                :id_agencia, 'activo', NOW()
            )
        ");

        $stmt->execute([
            'usuario_id' => $user['id'],
            'codigo_cliente' => $codigoCliente,
            'nombre_completo' => Validator::sanitize($_POST['nombre_completo']),
            'tipo_documento' => $_POST['tipo_documento'],
            'numero_documento' => $_POST['numero_documento'],
            'email' => !empty($_POST['email']) ? Validator::sanitize($_POST['email']) : null,
            'telefono' => $_POST['telefono'],
            'direccion' => !empty($_POST['direccion']) ? Validator::sanitize($_POST['direccion']) : null,
            'fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            'ocupacion' => !empty($_POST['ocupacion']) ? Validator::sanitize($_POST['ocupacion']) : null,
            'referencia_personal' => !empty($_POST['referencia_personal']) ? Validator::sanitize($_POST['referencia_personal']) : null,
            'telefono_referencia' => !empty($_POST['telefono_referencia']) ? $_POST['telefono_referencia'] : null,
            'foto_documento' => $fotoDocumento,
            'id_agencia' => !empty($_POST['id_agencia']) ? intval($_POST['id_agencia']) : null
        ]);

        $clienteId = $db->lastInsertId();

        // Confirmar transacción
        $db->commit();

        // Obtener cliente creado
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Registrar log
        Auth::logActivity($user['id'], 'create', 'clientes', "Cliente creado: {$_POST['nombre_completo']}", null, $cliente);

        Response::success($cliente, 'Cliente creado exitosamente', 201);

    } catch (Exception $e) {
        $db->rollBack();

        // Eliminar archivo subido si hay error
        if ($fotoDocumento) {
            $filePath = $uploadDir . $fotoDocumento;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en clientes/create_with_files.php: " . $e->getMessage());
    Response::serverError('Error al crear cliente: ' . $e->getMessage());
}
