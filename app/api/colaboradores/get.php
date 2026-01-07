<?php
/**
 * Obtener colaborador por ID
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

if (!isset($_GET['id'])) {
    Response::error('ID requerido', 400);
}

$id = intval($_GET['id']);
$db = getDB();

try {
    // Obtener datos del colaborador y usuario si existe
    $sql = "
        SELECT 
            c.*,
            u.id_usuario as usuario_id,
            u.username as user_username,
            u.id_rol as user_rol,
            u.estado as user_estado,
            u.saldo_caja_virtual,
            u.id_jefe_directo
        FROM colaboradores c
        LEFT JOIN usuarios u ON u.id_colaborador = c.id_colaborador
        WHERE c.id_colaborador = :id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        Response::error('Colaborador no encontrado', 404);
    }

    // Formatear respuesta
    $colaborador = [
        'id' => $data['id_colaborador'],
        'dni' => $data['dni'],
        'nombre_completo' => $data['nombre_completo'],
        'email' => $data['email'],
        'fecha_nacimiento' => $data['fecha_nacimiento'],
        'genero' => $data['genero'],
        'telefono' => $data['telefono'],
        'direccion_residencia' => $data['direccion_residencia'],
        'fecha_ingreso' => $data['fecha_ingreso'],
        'sueldo_base' => $data['sueldo_base'],
        'id_agencia' => $data['id_agencia'], // Ensure ID is sent
        'puesto_cargo' => $data['puesto_cargo'],
        'estado_laboral' => $data['estado_laboral'],
        'rtn_personal' => $data['rtn_personal'],
        'numero_seguro_social' => $data['numero_seguro_social'],
        'banco_receptor' => $data['banco_receptor'],
        'tipo_cuenta' => $data['tipo_cuenta'],
        'numero_cuenta_bancaria' => $data['numero_cuenta_bancaria'],
        'created_at' => $data['created_at'] ?? null,
        'usuario_vinculado' => null
    ];

    if ($data['usuario_id']) {
        $colaborador['usuario_vinculado'] = [
            'id' => $data['usuario_id'],
            'usuario' => $data['user_username'],
            'id_rol' => $data['user_rol'],
            'estado' => $data['user_estado'],
            'saldo_caja_virtual' => $data['saldo_caja_virtual'],
            'id_jefe_directo' => $data['id_jefe_directo']
        ];
    }

    Response::success($colaborador);

} catch (Exception $e) {
    error_log("Error al obtener colaborador: " . $e->getMessage());
    Response::serverError('Error al obtener datos');
}
