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
            u.id as usuario_id,
            u.usuario as user_username,
            u.id_rol as user_rol,
            u.estado as user_estado,
            u.saldo_caja_virtual,
            u.id_jefe_directo
        FROM colaboradores c
        LEFT JOIN usuarios u ON u.id_colaborador = c.id
        WHERE c.id = :id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        Response::error('Colaborador no encontrado', 404);
    }

    // Formatear respuesta
    $colaborador = [
        'id' => $data['id'],
        'dni' => $data['dni'],
        'nombre_completo' => $data['nombre_completo'],
        'email' => $data['email'],
        'sueldo_base' => $data['sueldo_base'],
        'agencia' => $data['agencia'],
        'puesto' => $data['puesto'],
        'estado_laboral' => $data['estado_laboral'],
        'created_at' => $data['created_at'],
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
