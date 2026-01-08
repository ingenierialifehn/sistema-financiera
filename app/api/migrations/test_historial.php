<?php
/**
 * Script: Probar API de historial de pagos
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Probando consulta de historial de pagos ===\n\n";

    $fechaFiltro = date('Y-m-d');
    echo "Fecha: $fechaFiltro\n\n";

    // Consulta exacta del archivo historial_pagos.php
    $sql = "SELECT 
                c.id as cuota_id, 
                c.numero_cuota, 
                c.monto_pagado, 
                c.fecha_pago_real,
                c.estado,
                cl.nombre_completo, 
                cl.numero_documento,
                p.id as prestamo_id,
                p.modalidad
            FROM cuotas c
            JOIN prestamos p ON c.prestamo_id = p.id
            JOIN clientes cl ON p.id_cliente = cl.id
            WHERE (c.monto_pagado > 0)
              AND DATE(c.fecha_pago_real) = ?
            ORDER BY c.fecha_pago_real DESC, c.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$fechaFiltro]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total de registros: " . count($data) . "\n\n";

    if (count($data) > 0) {
        $totalMonto = 0;
        echo "Detalles:\n";
        foreach ($data as $row) {
            echo "- {$row['nombre_completo']} - Cuota #{$row['numero_cuota']} - L " .
                number_format($row['monto_pagado'], 2) . " - {$row['fecha_pago_real']}\n";
            $totalMonto += $row['monto_pagado'];
        }
        echo "\nTotal recaudado: L " . number_format($totalMonto, 2) . "\n";
    } else {
        echo "No se encontraron pagos para la fecha $fechaFiltro\n\n";

        // Verificar si hay cuotas con monto_pagado > 0
        echo "Verificando cuotas con monto_pagado > 0:\n";
        $stmt = $db->query("
            SELECT COUNT(*) as total, 
                   SUM(monto_pagado) as monto,
                   MIN(fecha_pago_real) as primera_fecha,
                   MAX(fecha_pago_real) as ultima_fecha
            FROM cuotas 
            WHERE monto_pagado > 0
        ");
        $result = $stmt->fetch();
        echo "Total: {$result['total']} cuotas\n";
        echo "Monto total: L " . number_format($result['monto'] ?? 0, 2) . "\n";
        echo "Primera fecha: {$result['primera_fecha']}\n";
        echo "Última fecha: {$result['ultima_fecha']}\n\n";

        // Ver las últimas 5 cuotas con monto_pagado
        echo "Últimas 5 cuotas con monto_pagado > 0:\n";
        $stmt = $db->query("
            SELECT id, numero_cuota, monto_pagado, fecha_pago_real, estado,
                   DATE(fecha_pago_real) as fecha_solo
            FROM cuotas 
            WHERE monto_pagado > 0
            ORDER BY fecha_pago_real DESC
            LIMIT 5
        ");
        while ($row = $stmt->fetch()) {
            echo "- Cuota #{$row['numero_cuota']}: L " . number_format($row['monto_pagado'], 2) .
                " - {$row['fecha_pago_real']} - DATE: {$row['fecha_solo']} - {$row['estado']}\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
