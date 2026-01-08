<?php
/**
 * API: Listar pagos (desde cuotas)
 * GET /app/api/pagos/list.php?page=1&limit=20&prestamo_id=&cliente_id=&fecha=
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();

    $db = getDB();

    // Parámetros
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $prestamoId = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : null;
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
    $fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : null;
    $agenciaId = isset($_GET['agencia_id']) ? intval($_GET['agencia_id']) : null;

    // Construir query
    $where = ["cu.estado IN ('pagada', 'parcial')"];
    $params = [];

    // Si es cobrador, solo mostrar sus pagos
    if ($user['rol'] === 'cobrador') {
        $where[] = "cu.usuario_cobro_id = :cobrador_id";
        $params['cobrador_id'] = $user['id_usuario'];
    }

    // Si es cliente, solo mostrar sus pagos
    if ($user['rol'] === 'cliente') {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id_usuario']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $where[] = "c.id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            $where[] = "1 = 0";
        }
    }

    // Filtros
    if ($prestamoId) {
        $where[] = "p.id = :prestamo_id";
        $params['prestamo_id'] = $prestamoId;
    }

    if ($clienteId) {
        $where[] = "c.id = :cliente_id_filter";
        $params['cliente_id_filter'] = $clienteId;
    }

    if ($fecha) {
        $where[] = "DATE(cu.fecha_pago_real) = :fecha";
        $params['fecha'] = $fecha;
    }

    if ($agenciaId) {
        $where[] = "c.id_agencia = :agencia_id";
        $params['agencia_id'] = $agenciaId;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Contar total
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM cuotas cu
        INNER JOIN prestamos p ON cu.prestamo_id = p.id
        INNER JOIN clientes c ON p.id_cliente = c.id
        {$whereClause}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Obtener pagos (cuotas pagadas)
    $stmt = $db->prepare("
        SELECT 
            cu.id,
            cu.numero_cuota,
            cu.monto_cuota,
            cu.monto_pagado,
            cu.fecha_pago_real as fecha_pago,
            cu.estado,
            cu.capital_cuota,
            cu.interes_cuota,
            cu.gastos_cuota,
            cu.comision_cuota,
            c.id as cliente_id,
            c.nombre_completo as cliente_nombre,
            c.numero_documento as cliente_documento,
            p.id as prestamo_id,
            p.modalidad,
            u.nombre_completo as cobrador_nombre,
            a.nombre as agencia_nombre
        FROM cuotas cu
        INNER JOIN prestamos p ON cu.prestamo_id = p.id
        INNER JOIN clientes c ON p.id_cliente = c.id
        LEFT JOIN usuarios u ON cu.usuario_cobro_id = u.id_usuario
        LEFT JOIN agencias a ON c.id_agencia = a.id
        {$whereClause}
        ORDER BY cu.fecha_pago_real DESC, cu.id DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $pagos = $stmt->fetchAll();

    // Calcular totales
    $totalesStmt = $db->prepare("
        SELECT 
            SUM(cu.monto_pagado) as total_recaudado,
            SUM(cu.capital_cuota) as total_capital,
            SUM(cu.interes_cuota) as total_interes,
            SUM(cu.gastos_cuota) as total_gastos,
            SUM(cu.comision_cuota) as total_comision
        FROM cuotas cu
        INNER JOIN prestamos p ON cu.prestamo_id = p.id
        INNER JOIN clientes c ON p.id_cliente = c.id
        {$whereClause}
    ");
    $totalesStmt->execute($params);
    $totales = $totalesStmt->fetch();

    Response::success([
        'pagos' => $pagos,
        'totales' => [
            'total_recaudado' => floatval($totales['total_recaudado'] ?? 0),
            'total_capital' => floatval($totales['total_capital'] ?? 0),
            'total_interes' => floatval($totales['total_interes'] ?? 0),
            'total_gastos' => floatval($totales['total_gastos'] ?? 0),
            'total_comision' => floatval($totales['total_comision'] ?? 0)
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Pagos obtenidos exitosamente');

} catch (Exception $e) {
    error_log("Error en pagos/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener pagos: ' . $e->getMessage());
}
