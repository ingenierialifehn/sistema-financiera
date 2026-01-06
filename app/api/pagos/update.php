<?php
/**
 * API: Actualizar pago
 * PUT /app/api/pagos/update.php
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
        Response::error('ID de pago es requerido', 400);
    }
    
    $id = intval($input['id']);
    $db = getDB();
    
    // Verificar que existe
    $stmt = $db->prepare("SELECT * FROM pagos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $pagoExistente = $stmt->fetch();
    
    if (!$pagoExistente) {
        Response::notFound('Pago no encontrado');
    }
    
    // Validar campos
    $validation = Validator::validate($input, [
        'estado' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Estado inválido'
        ],
        'observaciones' => [
            'type' => 'string',
            'required' => false,
            'max' => 500,
            'message' => 'Observaciones inválidas'
        ],
        'comprobante_url' => [
            'type' => 'string',
            'required' => false,
            'max' => 255,
            'message' => 'URL de comprobante inválida'
        ]
    ]);
    
    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }
    
    $data = $validation['data'];
    
    // Construir UPDATE
    $updateFields = [];
    $params = ['id' => $id];
    
    if (isset($data['estado'])) {
        if (!in_array($data['estado'], ['pendiente', 'confirmado', 'rechazado'])) {
            Response::error('Estado inválido', 400);
        }
        $updateFields[] = "estado = :estado";
        $params['estado'] = $data['estado'];
    }
    
    if (isset($data['observaciones'])) {
        $updateFields[] = "observaciones = :observaciones";
        $params['observaciones'] = !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : null;
    }
    
    if (isset($data['comprobante_url'])) {
        $updateFields[] = "comprobante_url = :comprobante_url";
        $params['comprobante_url'] = !empty($data['comprobante_url']) ? Validator::sanitize($data['comprobante_url']) : null;
    }
    
    if (empty($updateFields)) {
        Response::error('No hay campos para actualizar', 400);
    }
    
    $updateFields[] = "updated_at = NOW()";
    
    $sql = "UPDATE pagos SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Obtener actualizado
    $stmt = $db->prepare("
        SELECT 
            p.*,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            pr.numero_prestamo,
            cu.numero_cuota,
            u.nombre_completo as cobrador_nombre
        FROM pagos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        INNER JOIN prestamos pr ON p.prestamo_id = pr.id
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        LEFT JOIN usuarios u ON p.cobrado_por = u.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $pago = $stmt->fetch();
    
    Auth::logActivity($user['id'], 'update', 'pagos', "Pago actualizado: ID {$id}", $pagoExistente, $pago);
    
    Response::success($pago, 'Pago actualizado exitosamente');
    
} catch (Exception $e) {
    error_log("Error en pagos/update.php: " . $e->getMessage());
    Response::serverError('Error al actualizar pago');
}

