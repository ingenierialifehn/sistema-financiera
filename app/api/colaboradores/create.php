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
    'sueldo_base' => ['type' => 'numeric', 'required' => true],
    'agencia' => ['required' => true],
    'puesto' => ['required' => true],
    'estado_laboral' => ['required' => true] // 'activo', 'inactivo', etc.
];

$validationColab = Validator::validate($data, $rulesColaborador);

if (!$validationColab['valid']) {
    Response::error('Datos de colaborador inválidos', 400, $validationColab['errors']);
}

// 2. Validar datos del Usuario (si aplica)
$crearUsuario = isset($data['crear_usuario']) && $data['crear_usuario'] === true;

if ($crearUsuario) {
    if (empty($data['usuario']) || empty($data['password']) || empty($data['id_rol'])) {
        Response::error('Faltan datos de usuario (usuario, password, rol)', 400);
    }
    // Validar longitud
    if (strlen($data['usuario']) < 3)
        Response::error('Usuario muy corto', 400);
    if (strlen($data['password']) < 6)
        Response::error('Contraseña muy corta', 400);
}

try {
    $db->beginTransaction();

    // Verificar duplicados (DNI o Email en colaboradores)
    $checkStmt = $db->prepare("SELECT id FROM colaboradores WHERE dni = :dni OR email = :email");
    $checkStmt->execute([
        'dni' => $data['dni'],
        'email' => $data['email']
    ]);
    if ($checkStmt->fetch()) {
        throw new Exception('El DNI o Email ya existe registrado en colaboradores', 400);
    }

    // Insertar Colaborador
    $stmtColab = $db->prepare("
        INSERT INTO colaboradores (dni, nombre_completo, email, sueldo_base, agencia, puesto, estado_laboral, created_at)
        VALUES (:dni, :nombre_completo, :email, :sueldo_base, :agencia, :puesto, :estado_laboral, NOW())
    ");

    $stmtColab->execute([
        'dni' => $data['dni'],
        'nombre_completo' => $data['nombre_completo'],
        'email' => $data['email'],
        'sueldo_base' => $data['sueldo_base'],
        'agencia' => $data['agencia'],
        'puesto' => $data['puesto'],
        'estado_laboral' => $data['estado_laboral']
    ]);

    $colaboradorId = $db->lastInsertId();

    // Insertar Usuario si aplica
    if ($crearUsuario) {
        // Verificar duplicidad de username
        $checkUser = $db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $checkUser->execute(['usuario' => $data['usuario']]);
        if ($checkUser->fetch()) {
            throw new Exception('El nombre de usuario ya está en uso', 400);
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmtUser = $db->prepare("
            INSERT INTO usuarios (usuario, password, nombre_completo, email, id_rol, id_colaborador, saldo_caja_virtual, estado, created_at, id_jefe_directo)
            VALUES (:usuario, :password, :nombre, :email, :id_rol, :id_colaborador, 0.00, 'activo', NOW(), :id_jefe)
        ");

        $idJefe = !empty($data['id_jefe_directo']) ? $data['id_jefe_directo'] : null;

        $stmtUser->execute([
            'usuario' => $data['usuario'],
            'password' => $passwordHash,
            'nombre' => $data['nombre_completo'],
            'email' => $data['email'],
            'id_rol' => $data['id_rol'],
            'id_colaborador' => $colaboradorId,
            'id_jefe' => $idJefe
        ]);
    }

    $db->commit();

    // Log
    Auth::logActivity($user['id'], 'create', 'colaborador', "Colaborador creado: {$data['nombre_completo']}", null, ['id' => $colaboradorId]);

    Response::success(['id' => $colaboradorId], 'Colaborador guardado exitosamente');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $code = $e->getCode() === 400 ? 400 : 500;
    error_log("Error crear colaborador: " . $e->getMessage());
    Response::error($e->getMessage(), $code);
}
