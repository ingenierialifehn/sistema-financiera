<?php
/**
 * Crear nuevo colaborador (Gestión de Personal)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

// 1. Validar datos del Colaborador
$rulesColaborador = [
    'dni' => ['required' => true, 'min' => 5, 'max' => 20],
    'nombre_completo' => ['required' => true, 'min' => 3],
    'email' => ['type' => 'email', 'required' => true],
    'fecha_nacimiento' => ['required' => true],
    'sueldo_base' => ['type' => 'numeric', 'required' => true],
    'id_agencia' => ['required' => true],
    'puesto_cargo' => ['required' => true],
    'estado_laboral' => ['required' => true] // 'activo', 'inactivo', etc.
];

$validationColab = Validator::validate($data, $rulesColaborador);

if (!$validationColab['valid']) {
    Response::error('Datos de colaborador inválidos', 400, $validationColab['errors']);
}

// 2. Validar datos del Usuario (si aplica)
$crearUsuario = isset($data['crear_usuario']) && $data['crear_usuario'] === true;

if ($crearUsuario) {
    if (empty($data['username']) || empty($data['password']) || empty($data['id_rol'])) {
        Response::error('Faltan datos de usuario (username, password, id_rol)', 400);
    }
    // Validar longitud
    if (strlen($data['username']) < 3)
        Response::error('Usuario muy corto', 400);
    if (strlen($data['password']) < 6)
        Response::error('Contraseña muy corta', 400);
}

try {
    $db->beginTransaction();

    // Verificar duplicados (DNI o Email en colaboradores)
    $checkStmt = $db->prepare("SELECT id_colaborador FROM colaboradores WHERE dni = :dni OR email = :email");
    $checkStmt->execute([
        'dni' => $data['dni'],
        'email' => $data['email']
    ]);
    if ($checkStmt->fetch()) {
        throw new Exception('El DNI o Email ya existe registrado en colaboradores', 400);
    }

    // Insertar Colaborador
    $stmtColab = $db->prepare("
        INSERT INTO colaboradores (
            dni, nombre_completo, email, fecha_nacimiento, genero, telefono, 
            direccion_residencia, puesto_cargo, id_agencia, fecha_ingreso, 
            sueldo_base, numero_cuenta_bancaria, banco_receptor, tipo_cuenta,
            numero_seguro_social, rtn_personal, estado_laboral, creado_por
        )
        VALUES (
            :dni, :nombre_completo, :email, :fecha_nacimiento, :genero, :telefono,
            :direccion_residencia, :puesto_cargo, :id_agencia, :fecha_ingreso,
            :sueldo_base, :numero_cuenta_bancaria, :banco_receptor, :tipo_cuenta,
            :numero_seguro_social, :rtn_personal, :estado_laboral, :creado_por
        )
    ");

    $stmtColab->execute([
        'dni' => $data['dni'],
        'nombre_completo' => $data['nombre_completo'],
        'email' => $data['email'],
        'fecha_nacimiento' => !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
        'genero' => !empty($data['genero']) ? $data['genero'] : 'Masculino',
        'telefono' => !empty($data['telefono']) ? $data['telefono'] : null,
        'direccion_residencia' => !empty($data['direccion_residencia']) ? $data['direccion_residencia'] : null,
        'puesto_cargo' => $data['puesto_cargo'],
        'id_agencia' => $data['id_agencia'],
        'fecha_ingreso' => !empty($data['fecha_ingreso']) ? $data['fecha_ingreso'] : date('Y-m-d'),
        'sueldo_base' => $data['sueldo_base'],
        'numero_cuenta_bancaria' => !empty($data['numero_cuenta_bancaria']) ? $data['numero_cuenta_bancaria'] : null,
        'banco_receptor' => !empty($data['banco_receptor']) ? $data['banco_receptor'] : null,
        'tipo_cuenta' => !empty($data['tipo_cuenta']) ? $data['tipo_cuenta'] : 'Ahorro',
        'numero_seguro_social' => !empty($data['numero_seguro_social']) ? $data['numero_seguro_social'] : null,
        'rtn_personal' => !empty($data['rtn_personal']) ? $data['rtn_personal'] : null,
        'estado_laboral' => !empty($data['estado_laboral']) ? $data['estado_laboral'] : 'Activo',
        'creado_por' => $user['id_usuario']
    ]);

    $colaboradorId = $db->lastInsertId();

    // Insertar Usuario si aplica
    if ($crearUsuario) {
        // Verificar duplicidad de username
        $checkUser = $db->prepare("SELECT id_usuario FROM usuarios WHERE username = :username");
        $checkUser->execute(['username' => $data['username']]);
        if ($checkUser->fetch()) {
            throw new Exception('El nombre de usuario ya está en uso', 400);
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmtUser = $db->prepare("
            INSERT INTO usuarios (
                username, password_hash, id_rol, id_colaborador, 
                saldo_caja_virtual, estado, id_jefe_directo
            )
            VALUES (
                :username, :password_hash, :id_rol, :id_colaborador, 
                0.00, 'Activo', :id_jefe
            )
        ");

        $idJefe = !empty($data['id_jefe_directo']) ? $data['id_jefe_directo'] : null;

        $stmtUser->execute([
            'username' => $data['username'],
            'password_hash' => $passwordHash,
            'id_rol' => $data['id_rol'],
            'id_colaborador' => $colaboradorId,
            'id_jefe' => $idJefe
        ]);
    }

    $db->commit();

    // Log
    Auth::logActivity($user['id_usuario'], 'create', 'colaborador', "Colaborador creado: {$data['nombre_completo']}", null, ['id_colaborador' => $colaboradorId]);

    // =========================================================
    // AUTO-DESACTIVACIÓN DEL ADMIN TEMPORAL (RESET INICIAL)
    // =========================================================
    // Si se acaba de crear un usuario nuevo (ID > 1), y existe el usuario 'admin' (ID 1), lo desactivamos.
    if ($crearUsuario) {
        $db->exec("UPDATE usuarios SET estado = 'Inactivo' WHERE username = 'admin' AND id_usuario = 1");
    }

    Response::success(['id' => $colaboradorId], 'Colaborador guardado exitosamente');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $code = $e->getCode() === 400 ? 400 : 500;
    error_log("Error crear colaborador: " . $e->getMessage());
    Response::error($e->getMessage(), $code);
}
