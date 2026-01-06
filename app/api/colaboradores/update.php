<?php
/**
 * Actualizar colaborador (Gestión de Personal)
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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (empty($data['id'])) {
    Response::error('ID inválido', 400);
}

$id = $data['id'];

// 1. Validar reglas de negocio antes de actualizar
// Obtener info actual
$stmtCurrent = $db->prepare("
    SELECT c.*, u.id as usuario_id, u.saldo_caja_virtual 
    FROM colaboradores c 
    LEFT JOIN usuarios u ON u.id_colaborador = c.id 
    WHERE c.id = :id
");
$stmtCurrent->execute(['id' => $id]);
$current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    Response::error('Colaborador no encontrado', 404);
}

// Validación de Cierre (Saldo Caja)
if (isset($data['estado_laboral'])) {
    $newStatus = strtolower($data['estado_laboral']);
    if (in_array($newStatus, ['despido', 'renuncia'])) {
        if ($current['usuario_id'] && $current['saldo_caja_virtual'] > 0) {
            Response::error("No se puede dar de baja (Despido/Renuncia). El usuario tiene un saldo pendiente de L " . number_format($current['saldo_caja_virtual'], 2), 400);
        }
    }
}

try {
    $db->beginTransaction();

    // Actualizar Colaborador
    $fields = [];
    $params = ['id' => $id];

    if (!empty($data['dni'])) {
        $fields[] = "dni = :dni";
        $params['dni'] = $data['dni'];
    }
    if (!empty($data['nombre_completo'])) {
        $fields[] = "nombre_completo = :nombre_completo";
        $params['nombre_completo'] = $data['nombre_completo'];
    }
    if (!empty($data['email'])) {
        $fields[] = "email = :email";
        $params['email'] = $data['email'];
    }
    if (isset($data['sueldo_base'])) {
        $fields[] = "sueldo_base = :sueldo_base";
        $params['sueldo_base'] = $data['sueldo_base'];
    }
    if (!empty($data['agencia'])) {
        $fields[] = "agencia = :agencia";
        $params['agencia'] = $data['agencia'];
    }
    if (!empty($data['puesto'])) {
        $fields[] = "puesto = :puesto";
        $params['puesto'] = $data['puesto'];
    }
    if (!empty($data['estado_laboral'])) {
        $fields[] = "estado_laboral = :estado_laboral";
        $params['estado_laboral'] = $data['estado_laboral'];
    }

    if (!empty($fields)) {
        $sql = "UPDATE colaboradores SET " . implode(', ', $fields) . " WHERE id = :id";
        $db->prepare($sql)->execute($params);
    }

    // Gestionar Usuario
    $crearUsuario = isset($data['crear_usuario']) && $data['crear_usuario'] === true; // Si checkbox marcado

    if ($crearUsuario) {
        // Si ya tenía usuario, actualizarlo
        if ($current['usuario_id']) {
            // Update exist
            $uFields = [];
            $uParams = ['uid' => $current['usuario_id']];

            if (!empty($data['id_rol'])) {
                $uFields[] = "id_rol = :id_rol";
                $uParams['id_rol'] = $data['id_rol'];
            }
            // Password solo si se envía
            if (!empty($data['password'])) {
                $uFields[] = "password = :password";
                $uParams['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (isset($data['id_jefe_directo'])) {
                $uFields[] = "id_jefe_directo = :id_jefe";
                $uParams['id_jefe'] = !empty($data['id_jefe_directo']) ? $data['id_jefe_directo'] : null;
            }

            if (!empty($uFields)) {
                $sqlU = "UPDATE usuarios SET " . implode(', ', $uFields) . " WHERE id = :uid";
                $db->prepare($sqlU)->execute($uParams);
            }
        } else {
            // Crear nuevo usuario para este colaborador
            // Validar requeridos
            if (empty($data['usuario']) || empty($data['password']) || empty($data['id_rol'])) {
                throw new Exception("Faltan datos para crear el usuario");
            }

            // Validar unique usuario
            $checkU = $db->prepare("SELECT id FROM usuarios WHERE usuario = :u");
            $checkU->execute(['u' => $data['usuario']]);
            if ($checkU->fetch())
                throw new Exception("El usuario ya existe");

            $stmtNewU = $db->prepare("
               INSERT INTO usuarios (usuario, password, nombre_completo, email, id_rol, id_colaborador, saldo_caja_virtual, estado, created_at, id_jefe_directo)
               VALUES (:usuario, :password, :nombre, :email, :rol, :colab, 0.00, 'activo', NOW(), :jefe)
           ");
            $stmtNewU->execute([
                'usuario' => $data['usuario'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'nombre' => $data['nombre_completo'] ?? $current['nombre_completo'],
                'email' => $data['email'] ?? $current['email'],
                'rol' => $data['id_rol'],
                'colab' => $id,
                'jefe' => !empty($data['id_jefe_directo']) ? $data['id_jefe_directo'] : null
            ]);
        }
    } else {
        // Si checkbox desmarcado
        // Opción: ¿Desactivar usuario? ¿Eliminar?
        // El prompt no especifica qué pasa si se desmarca "Tiene acceso".
        // Lo lógico es cambiar estado a inactivo si existía.
        if ($current['usuario_id']) {
            $db->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id = :uid")->execute(['uid' => $current['usuario_id']]);
        }
    }

    $db->commit();

    Auth::logActivity($user['id'], 'update', 'colaborador', "Colaborador actualizado ID: $id", $current, $data);
    Response::success(['id' => $id], 'Actualizado correctamente');

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    error_log("Error update colaborador: " . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
