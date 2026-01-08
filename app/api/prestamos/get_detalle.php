<?php
/**
 * API: Obtener detalle completo de un préstamo
 * GET /app/api/prestamos/get_detalle.php?id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        Response::error('ID de préstamo es requerido', 400);
    }

    $prestamoId = intval($_GET['id']);
    $db = getDB();

    // Obtener información completa del préstamo
    $sqlPrestamo = "
        SELECT 
            p.*,
            c.nombre_completo as cliente_nombre,
            c.numero_documento as cliente_documento,
            c.telefono as cliente_telefono,
            c.direccion as cliente_direccion,
            
            -- Calcular balance pendiente
            (p.total_a_pagar - IFNULL((SELECT SUM(monto_pagado) FROM cuotas WHERE prestamo_id = p.id), 0)) as balance_pendiente,
            
            -- Calcular capital restante
            (p.monto_capital - IFNULL((SELECT SUM(monto_pagado * (capital_cuota / monto_cuota)) 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pagada', 'parcial')
                AND monto_cuota > 0), 0)) as capital_restante
            
        FROM prestamos p
        INNER JOIN clientes c ON p.id_cliente = c.id
        WHERE p.id = :prestamo_id
    ";

    $stmt = $db->prepare($sqlPrestamo);
    $stmt->execute(['prestamo_id' => $prestamoId]);
    $prestamo = $stmt->fetch();

    if (!$prestamo) {
        Response::notFound('Préstamo no encontrado');
    }

    // Obtener nombres de usuarios involucrados
    if ($prestamo['asesor_creditos_id']) {
        $stmt = $db->prepare("
            SELECT col.nombre_completo 
            FROM usuarios u 
            LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
            WHERE u.id_usuario = ?
        ");
        $stmt->execute([$prestamo['asesor_creditos_id']]);
        $prestamo['asesor_nombre'] = $stmt->fetchColumn() ?: 'N/A';
    } else {
        $prestamo['asesor_nombre'] = 'N/A';
    }

    if ($prestamo['oficial_desembolsos_id']) {
        $stmt = $db->prepare("
            SELECT col.nombre_completo 
            FROM usuarios u 
            LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
            WHERE u.id_usuario = ?
        ");
        $stmt->execute([$prestamo['oficial_desembolsos_id']]);
        $prestamo['oficial_desembolsos_nombre'] = $stmt->fetchColumn() ?: 'N/A';
    } else {
        $prestamo['oficial_desembolsos_nombre'] = 'N/A';
    }

    // Obtener cuotas con desglose
    $sqlCuotas = "
        SELECT 
            id,
            numero_cuota,
            monto_cuota,
            monto_pagado,
            fecha_vencimiento,
            fecha_pago_real,
            estado,
            dias_mora,
            monto_mora,
            capital_cuota,
            interes_cuota,
            gastos_cuota,
            comision_cuota
        FROM cuotas
        WHERE prestamo_id = :prestamo_id
        ORDER BY numero_cuota ASC
    ";

    $stmt = $db->prepare($sqlCuotas);
    $stmt->execute(['prestamo_id' => $prestamoId]);
    $cuotas = $stmt->fetchAll();

    // Obtener comentarios
    $sqlComentarios = "
        SELECT pc.*
        FROM prestamos_comentarios pc
        WHERE pc.prestamo_id = :prestamo_id
        ORDER BY pc.created_at DESC
    ";

    $stmt = $db->prepare($sqlComentarios);
    $stmt->execute(['prestamo_id' => $prestamoId]);
    $comentarios = $stmt->fetchAll();

    // Obtener nombres de usuarios para cada comentario
    foreach ($comentarios as &$comentario) {
        if ($comentario['usuario_id']) {
            $stmt = $db->prepare("
                SELECT col.nombre_completo 
                FROM usuarios u 
                LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                WHERE u.id_usuario = ?
            ");
            $stmt->execute([$comentario['usuario_id']]);
            $comentario['usuario_nombre'] = $stmt->fetchColumn() ?: 'Usuario';
        } else {
            $comentario['usuario_nombre'] = 'Sistema';
        }
    }

    // Formatear datos
    $prestamo['monto_capital'] = floatval($prestamo['monto_capital']);
    $prestamo['neto_entregar'] = floatval($prestamo['neto_entregar']);
    $prestamo['total_a_pagar'] = floatval($prestamo['total_a_pagar']);
    $prestamo['balance_pendiente'] = floatval($prestamo['balance_pendiente'] ?? 0);
    $prestamo['capital_restante'] = floatval($prestamo['capital_restante'] ?? 0);

    // Calcular totales pagados desglosados
    $capital_pagado = 0;
    $interes_pagado = 0;
    $gastos_pagados = 0;
    $comision_pagada = 0;
    $total_pagado = 0;

    foreach ($cuotas as $cuota) {
        $monto_pagado = floatval($cuota['monto_pagado'] ?? 0);
        $monto_cuota = floatval($cuota['monto_cuota']);

        if ($monto_pagado > 0 && $monto_cuota > 0) {
            // Calcular proporción pagada de esta cuota
            $proporcion = $monto_pagado / $monto_cuota;

            // Aplicar proporción a cada componente
            $capital_pagado += floatval($cuota['capital_cuota'] ?? 0) * $proporcion;
            $interes_pagado += floatval($cuota['interes_cuota'] ?? 0) * $proporcion;
            $gastos_pagados += floatval($cuota['gastos_cuota'] ?? 0) * $proporcion;
            $comision_pagada += floatval($cuota['comision_cuota'] ?? 0) * $proporcion;
            $total_pagado += $monto_pagado;
        }
    }

    $prestamo['capital_pagado'] = $capital_pagado;
    $prestamo['interes_pagado'] = $interes_pagado;
    $prestamo['gastos_pagados'] = $gastos_pagados;
    $prestamo['comision_pagada'] = $comision_pagada;
    $prestamo['total_pagado'] = $total_pagado;

    foreach ($cuotas as &$cuota) {
        $cuota['monto_cuota'] = floatval($cuota['monto_cuota']);
        $cuota['monto_pagado'] = floatval($cuota['monto_pagado'] ?? 0);
        $cuota['capital_cuota'] = floatval($cuota['capital_cuota'] ?? 0);
        $cuota['interes_cuota'] = floatval($cuota['interes_cuota'] ?? 0);
        $cuota['gastos_cuota'] = floatval($cuota['gastos_cuota'] ?? 0);
        $cuota['comision_cuota'] = floatval($cuota['comision_cuota'] ?? 0);
    }

    Response::success([
        'prestamo' => $prestamo,
        'cuotas' => $cuotas,
        'comentarios' => $comentarios
    ], 'Detalle del préstamo obtenido exitosamente');

} catch (Exception $e) {
    error_log("Error en prestamos/get_detalle.php: " . $e->getMessage());
    Response::serverError('Error al obtener detalle del préstamo: ' . $e->getMessage());
}
