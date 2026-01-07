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
    SELECT c.*, u.id_usuario as usuario_id, u.saldo_caja_virtual 
    FROM colaboradores c 
    LEFT JOIN usuarios u ON u.id_colaborador = c.id_colaborador 
    WHERE c.id_colaborador = :id
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

    // Validar campos obligatorios que no deben quedar vacíos si se envían
    if (isset($data['fecha_nacimiento']) && empty($data['fecha_nacimiento'])) {
        throw new Exception("La fecha de nacimiento es obligatoria");
    }

    // Mapeo campos
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
    if (!empty($data['fecha_nacimiento'])) {
        $fields[] = "fecha_nacimiento = :fecha_nacimiento";
        $params['fecha_nacimiento'] = $data['fecha_nacimiento'];
    }
    if (!empty($data['genero'])) {
        $fields[] = "genero = :genero";
        $params['genero'] = $data['genero'];
    }
    if (isset($data['telefono'])) { // Permite borrar si envía ""? No, usually phones are kept. If explicitly null needed, change logic.
        $fields[] = "telefono = :telefono";
        $params['telefono'] = !empty($data['telefono']) ? $data['telefono'] : null;
    }
    if (isset($data['direccion_residencia'])) {
        $fields[] = "direccion_residencia = :direccion_residencia";
        $params['direccion_residencia'] = !empty($data['direccion_residencia']) ? $data['direccion_residencia'] : null;
    }
    if (!empty($data['fecha_ingreso'])) {
        $fields[] = "fecha_ingreso = :fecha_ingreso";
        $params['fecha_ingreso'] = $data['fecha_ingreso'];
    }
    if (isset($data['sueldo_base'])) {
        $fields[] = "sueldo_base = :sueldo_base";
        $params['sueldo_base'] = $data['sueldo_base'];
    }
    if (!empty($data['id_agencia'])) {
        $fields[] = "id_agencia = :id_agencia";
        $params['id_agencia'] = $data['id_agencia'];
    }
    if (!empty($data['puesto_cargo'])) {
        $fields[] = "puesto_cargo = :puesto_cargo";
        $params['puesto_cargo'] = $data['puesto_cargo'];
    }
    if (!empty($data['estado_laboral'])) {
        $fields[] = "estado_laboral = :estado_laboral";
        $params['estado_laboral'] = $data['estado_laboral'];
    }

    // Nuevos campos (Bank/Legal)
    if (isset($data['rtn_personal'])) {
        $fields[] = "rtn_personal = :rtn_personal";
        $params['rtn_personal'] = !empty($data['rtn_personal']) ? $data['rtn_personal'] : null;
    }
    if (isset($data['numero_seguro_social'])) {
        $fields[] = "numero_seguro_social = :numero_seguro_social";
        $params['numero_seguro_social'] = !empty($data['numero_seguro_social']) ? $data['numero_seguro_social'] : null;
    }
    if (isset($data['banco_receptor'])) {
        $fields[] = "banco_receptor = :banco_receptor";
        $params['banco_receptor'] = !empty($data['banco_receptor']) ? $data['banco_receptor'] : null;
    }
    if (isset($data['tipo_cuenta'])) {
        $fields[] = "tipo_cuenta = :tipo_cuenta";
        $params['tipo_cuenta'] = !empty($data['tipo_cuenta']) ? $data['tipo_cuenta'] : 'Ahorro';
    }
    if (isset($data['numero_cuenta_bancaria'])) {
        $fields[] = "numero_cuenta_bancaria = :numero_cuenta_bancaria";
        $params['numero_cuenta_bancaria'] = !empty($data['numero_cuenta_bancaria']) ? $data['numero_cuenta_bancaria'] : null;
    }

    if (!empty($fields)) {
        $sql = "UPDATE colaboradores SET " . implode(', ', $fields) . " WHERE id_colaborador = :id";
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
                $uFields[] = "password_hash = :password";
                $uParams['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (isset($data['id_jefe_directo'])) {
                $uFields[] = "id_jefe_directo = :id_jefe";
                $uParams['id_jefe'] = !empty($data['id_jefe_directo']) ? $data['id_jefe_directo'] : null;
            }

            if (!empty($uFields)) {
                $sqlU = "UPDATE usuarios SET " . implode(', ', $uFields) . " WHERE id_usuario = :uid";
                $db->prepare($sqlU)->execute($uParams);
            }
        } else {
            // Crear nuevo usuario para este colaborador
            // Validar requeridos
            if (empty($data['username']) || empty($data['password']) || empty($data['id_rol'])) {
                throw new Exception("Faltan datos para crear el usuario");
            }

            // Validar unique usuario
            $checkU = $db->prepare("SELECT id_usuario FROM usuarios WHERE username = :u");
            $checkU->execute(['u' => $data['username']]);
            if ($checkU->fetch())
                throw new Exception("El usuario ya existe");

            $stmtNewU = $db->prepare("
               INSERT INTO usuarios (username, password_hash, id_rol, id_colaborador, saldo_caja_virtual, estado, id_jefe_directo)
               VALUES (:usuario, :password, :rol, :colab, 0.00, 'Activo', :jefe)
           ");
            $stmtNewU->execute([
                'usuario' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
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
            $db->prepare("UPDATE usuarios SET estado = 'Inactivo' WHERE id_usuario = :uid")->execute(['uid' => $current['usuario_id']]);
        }
    }

    $db->commit();

    Auth::logActivity($user['id_usuario'] ?? 0, 'update', 'colaborador', "Colaborador actualizado ID: $id", $current, $data);
    Response::success(['id' => $id], 'Actualizado correctamente');

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    error_log("Error update colaborador: " . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
