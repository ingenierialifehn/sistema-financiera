<?php
/**
 * API: Clientes en Mora
 * GET /api/reportes/clientes-mora.php
 * Retorna clientes con más de 30 días de mora
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

    // Consulta para obtener clientes con más de 30 días de mora
    $sql = "
        SELECT 
            pr.id_prestamo,
            cl.nombre_completo as nombre_cliente,
            cl.id_cliente,
            pr.monto_capital,
            pr.saldo_pendiente as monto_riesgo,
            -- Calcular días de mora
            DATEDIFF(CURDATE(), 
                (SELECT MIN(c.fecha_vencimiento) 
                 FROM cuotas c 
                 WHERE c.id_prestamo = pr.id_prestamo 
                 AND c.estado_pago = 'Pendiente' 
                 AND c.fecha_vencimiento < CURDATE())
            ) as dias_mora,
            -- Determinar categoría de riesgo
            CASE 
                WHEN DATEDIFF(CURDATE(), 
                    (SELECT MIN(c.fecha_vencimiento) 
                     FROM cuotas c 
                     WHERE c.id_prestamo = pr.id_prestamo 
                     AND c.estado_pago = 'Pendiente' 
                     AND c.fecha_vencimiento < CURDATE())
                ) BETWEEN 31 AND 60 THEN 'C'
                WHEN DATEDIFF(CURDATE(), 
                    (SELECT MIN(c.fecha_vencimiento) 
                     FROM cuotas c 
                     WHERE c.id_prestamo = pr.id_prestamo 
                     AND c.estado_pago = 'Pendiente' 
                     AND c.fecha_vencimiento < CURDATE())
                ) BETWEEN 61 AND 90 THEN 'D'
                WHEN DATEDIFF(CURDATE(), 
                    (SELECT MIN(c.fecha_vencimiento) 
                     FROM cuotas c 
                     WHERE c.id_prestamo = pr.id_prestamo 
                     AND c.estado_pago = 'Pendiente' 
                     AND c.fecha_vencimiento < CURDATE())
                ) > 90 THEN 'E'
                ELSE 'B'
            END as categoria
        FROM prestamos pr
        INNER JOIN clientes cl ON pr.id_cliente = cl.id_cliente
        WHERE pr.estado = 'Activo'
        AND cl.id_agencia = :id_agencia
        HAVING dias_mora > 30
        ORDER BY dias_mora DESC, monto_riesgo DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id_agencia' => $idAgencia]);

    $clientes = $stmt->fetchAll();

    // Formatear resultados
    $clientesFormateados = [];
    foreach ($clientes as $cliente) {
        $clientesFormateados[] = [
            'id_cliente' => $cliente['id_cliente'],
            'nombre_cliente' => $cliente['nombre_cliente'],
            'id_prestamo' => $cliente['id_prestamo'],
            'monto_capital' => round($cliente['monto_capital'], 2),
            'monto_riesgo' => round($cliente['monto_riesgo'], 2),
            'dias_mora' => $cliente['dias_mora'],
            'categoria' => $cliente['categoria']
        ];
    }

    Response::success($clientesFormateados);

} catch (Exception $e) {
    error_log("Error en clientes-mora.php: " . $e->getMessage());
    Response::error('Error al obtener clientes en mora: ' . $e->getMessage(), 500);
}
