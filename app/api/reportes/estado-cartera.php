<?php
/**
 * API: Estado de Cartera
 * GET /api/reportes/estado-cartera.php
 * Retorna resumen de cartera por categorías de riesgo (A-E)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

// Verificar autenticación
$user = Auth::requireAuth();

// Obtener parámetros
$idAgencia = $_GET['id_agencia'] ?? null;

// Si no es administrador, forzar su agencia
$rolNombre = $user['rol_nombre'] ?? '';
$esAdministrador = in_array($rolNombre, ['Administrador', 'Gerente']);

if (!$esAdministrador) {
    $idAgencia = $user['id_agencia'];
}

if (!$idAgencia) {
    Response::error('ID de agencia es requerido', 400);
}

try {
    $db = getDB();

    // Consulta para obtener estado de cartera con categorías de riesgo
    // Categorías basadas en días de mora:
    // A: 0-7 días (Al día)
    // B: 8-30 días (Mora temprana)
    // C: 31-60 días (Mora media)
    // D: 61-90 días (Mora alta)
    // E: >90 días (Mora crítica)

    $sql = "
        SELECT 
            pr.id_prestamo,
            pr.monto_capital,
            pr.saldo_pendiente,
            cl.nombre_completo,
            cl.id_agencia,
            -- Calcular días de mora basado en la cuota más antigua vencida
            COALESCE(
                DATEDIFF(CURDATE(), 
                    (SELECT MIN(c.fecha_vencimiento) 
                     FROM cuotas c 
                     WHERE c.id_prestamo = pr.id_prestamo 
                     AND c.estado_pago = 'Pendiente' 
                     AND c.fecha_vencimiento < CURDATE())
                ), 
                0
            ) as dias_mora,
            -- Determinar categoría de riesgo
            CASE 
                WHEN COALESCE(
                    DATEDIFF(CURDATE(), 
                        (SELECT MIN(c.fecha_vencimiento) 
                         FROM cuotas c 
                         WHERE c.id_prestamo = pr.id_prestamo 
                         AND c.estado_pago = 'Pendiente' 
                         AND c.fecha_vencimiento < CURDATE())
                    ), 
                    0
                ) <= 7 THEN 'A'
                WHEN COALESCE(
                    DATEDIFF(CURDATE(), 
                        (SELECT MIN(c.fecha_vencimiento) 
                         FROM cuotas c 
                         WHERE c.id_prestamo = pr.id_prestamo 
                         AND c.estado_pago = 'Pendiente' 
                         AND c.fecha_vencimiento < CURDATE())
                    ), 
                    0
                ) BETWEEN 8 AND 30 THEN 'B'
                WHEN COALESCE(
                    DATEDIFF(CURDATE(), 
                        (SELECT MIN(c.fecha_vencimiento) 
                         FROM cuotas c 
                         WHERE c.id_prestamo = pr.id_prestamo 
                         AND c.estado_pago = 'Pendiente' 
                         AND c.fecha_vencimiento < CURDATE())
                    ), 
                    0
                ) BETWEEN 31 AND 60 THEN 'C'
                WHEN COALESCE(
                    DATEDIFF(CURDATE(), 
                        (SELECT MIN(c.fecha_vencimiento) 
                         FROM cuotas c 
                         WHERE c.id_prestamo = pr.id_prestamo 
                         AND c.estado_pago = 'Pendiente' 
                         AND c.fecha_vencimiento < CURDATE())
                    ), 
                    0
                ) BETWEEN 61 AND 90 THEN 'D'
                ELSE 'E'
            END as categoria_riesgo
        FROM prestamos pr
        INNER JOIN clientes cl ON pr.id_cliente = cl.id_cliente
        WHERE pr.estado = 'Activo'
        AND cl.id_agencia = :id_agencia
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id_agencia' => $idAgencia]);

    $prestamos = $stmt->fetchAll();

    // Agrupar por categoría
    $categorias = [
        'A' => ['clientes' => 0, 'monto' => 0],
        'B' => ['clientes' => 0, 'monto' => 0],
        'C' => ['clientes' => 0, 'monto' => 0],
        'D' => ['clientes' => 0, 'monto' => 0],
        'E' => ['clientes' => 0, 'monto' => 0]
    ];

    foreach ($prestamos as $prestamo) {
        $categoria = $prestamo['categoria_riesgo'];
        $categorias[$categoria]['clientes']++;
        $categorias[$categoria]['monto'] += $prestamo['saldo_pendiente'];
    }

    // Redondear montos
    foreach ($categorias as $cat => $datos) {
        $categorias[$cat]['monto'] = round($datos['monto'], 2);
    }

    // Calcular totales
    $totalClientes = array_sum(array_column($categorias, 'clientes'));
    $totalCartera = array_sum(array_column($categorias, 'monto'));

    // Calcular monto en mora (categorías B-E)
    $montoEnMora = $categorias['B']['monto'] + $categorias['C']['monto'] +
        $categorias['D']['monto'] + $categorias['E']['monto'];

    Response::success([
        'categorias' => $categorias,
        'totales' => [
            'total_clientes' => $totalClientes,
            'total_cartera' => round($totalCartera, 2),
            'monto_en_mora' => round($montoEnMora, 2),
            'porcentaje_mora' => $totalCartera > 0 ? round(($montoEnMora / $totalCartera) * 100, 2) : 0
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en estado-cartera.php: " . $e->getMessage());
    Response::error('Error al obtener estado de cartera: ' . $e->getMessage(), 500);
}
