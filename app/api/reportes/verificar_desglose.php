<?php
/**
 * Script de Verificación de Desglose en Cuotas
 * Verifica si todas las cuotas tienen el desglose calculado
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Verificar cuántas cuotas NO tienen desglose
    $sqlSinDesglose = "SELECT COUNT(*) as total 
                       FROM cuotas 
                       WHERE (capital_cuota IS NULL OR capital_cuota = 0)
                       AND estado = 'pagada'";

    $stmt = $db->query($sqlSinDesglose);
    $sinDesglose = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar cuántas cuotas SÍ tienen desglose
    $sqlConDesglose = "SELECT COUNT(*) as total 
                       FROM cuotas 
                       WHERE capital_cuota > 0
                       AND estado = 'pagada'";

    $stmt = $db->query($sqlConDesglose);
    $conDesglose = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener ejemplos de cuotas sin desglose
    $sqlEjemplos = "SELECT cu.id, cu.numero_cuota, cu.monto_pagado, cu.fecha_pago,
                    c.nombre_completo, p.id as prestamo_id
                    FROM cuotas cu
                    JOIN prestamos p ON cu.prestamo_id = p.id
                    JOIN clientes c ON p.id_cliente = c.id
                    WHERE (cu.capital_cuota IS NULL OR cu.capital_cuota = 0)
                    AND cu.estado = 'pagada'
                    LIMIT 10";

    $stmt = $db->query($sqlEjemplos);
    $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'cuotas_sin_desglose' => $sinDesglose['total'],
            'cuotas_con_desglose' => $conDesglose['total'],
            'total_cuotas_pagadas' => $sinDesglose['total'] + $conDesglose['total'],
            'porcentaje_sin_desglose' => round(($sinDesglose['total'] / ($sinDesglose['total'] + $conDesglose['total'])) * 100, 2),
            'ejemplos_sin_desglose' => $ejemplos,
            'mensaje' => $sinDesglose['total'] > 0
                ? "Hay {$sinDesglose['total']} cuotas pagadas sin desglose. El sistema calculará el desglose automáticamente."
                : "Todas las cuotas tienen desglose calculado."
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>