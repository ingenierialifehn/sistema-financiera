<?php
/**
 * API: Actualizar préstamo
 * PUT /app/api/prestamos/update.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

try {
    // Solo admin puede actualizar
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    
    if (!isset($input['id']) || empty($input['id'])) {
        Response::error('ID de préstamo es requerido', 400);
    }
    
    $id = intval($input['id']);
    $db = getDB();
    
    // Verificar que existe
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $prestamoExistente = $stmt->fetch();
    
    if (!$prestamoExistente) {
        Response::notFound('Préstamo no encontrado');
    }
    
    // Validar que no se actualice si tiene pagos
    if ($prestamoExistente['estado'] === 'activo') {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM pagos WHERE prestamo_id = :id");
        $stmt->execute(['id' => $id]);
        $tienePagos = $stmt->fetch()['total'] > 0;
        
        if ($tienePagos) {
            Response::error('No se puede actualizar un préstamo que ya tiene pagos registrados', 409);
        }
    }
    
    // Validar campos actualizables
    $validation = Validator::validate($input, [
        'observaciones' => [
            'type' => 'string',
            'required' => false,
            'max' => 500,
            'message' => 'Observaciones inválidas'
        ],
        'estado' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Estado inválido'
        ]
    ]);
    
    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }
    
    $data = $validation['data'];
    
    // Construir UPDATE
    $updateFields = [];
    $params = ['id' => $id];
    
    if (isset($data['observaciones'])) {
        $updateFields[] = "observaciones = :observaciones";
        $params['observaciones'] = !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : null;
    }
    
    if (isset($data['estado'])) {
        if (!in_array($data['estado'], ['pendiente', 'activo', 'completado', 'cancelado', 'en_mora'])) {
            Response::error('Estado inválido', 400);
        }
        $updateFields[] = "estado = :estado";
        $params['estado'] = $data['estado'];
    }
    
    if (empty($updateFields)) {
        Response::error('No hay campos para actualizar', 400);
    }
    
    $updateFields[] = "updated_at = NOW()";
    
    $sql = "UPDATE prestamos SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Obtener actualizado
    $stmt = $db->prepare("
        SELECT p.*, c.nombre_completo as cliente_nombre, c.codigo_cliente
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $prestamo = $stmt->fetch();
    
    Auth::logActivity($user['id'], 'update', 'prestamos', "Préstamo actualizado: {$prestamo['numero_prestamo']}", $prestamoExistente, $prestamo);
    
    Response::success($prestamo, 'Préstamo actualizado exitosamente');
    
} catch (Exception $e) {
    error_log("Error en prestamos/update.php: " . $e->getMessage());
    Response::serverError('Error al actualizar préstamo');
}

