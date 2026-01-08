<?php
/**
 * Script: Buscar pagos en todas las tablas
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Buscando pagos en la base de datos ===\n\n";

    $hoy = date('Y-m-d');
    echo "Fecha de hoy: $hoy\n\n";

    // 1. Verificar cuotas con fecha_pago_real de hoy
    echo "1. Cuotas con fecha_pago_real de hoy:\n";
    $stmt = $db->query("
        SELECT COUNT(*) as total, SUM(monto_pagado) as monto_total
        FROM cuotas 
        WHERE DATE(fecha_pago_real) = '$hoy'
    ");
    $result = $stmt->fetch();
    echo "   Total: {$result['total']} cuotas\n";
    echo "   Monto: L " . number_format($result['monto_total'] ?? 0, 2) . "\n\n";

    if ($result['total'] > 0) {
        echo "   Detalles:\n";
        $stmt = $db->query("
            SELECT id, prestamo_id, numero_cuota, monto_pagado, fecha_pago_real, estado
            FROM cuotas 
            WHERE DATE(fecha_pago_real) = '$hoy'
            ORDER BY fecha_pago_real DESC
        ");
        while ($row = $stmt->fetch()) {
            echo "   - Cuota #{$row['numero_cuota']} (Préstamo {$row['prestamo_id']}): L " .
                number_format($row['monto_pagado'], 2) . " - {$row['fecha_pago_real']} - {$row['estado']}\n";
        }
        echo "\n";
    }

    // 2. Verificar cuotas con updated_at de hoy
    echo "2. Cuotas actualizadas hoy:\n";
    $stmt = $db->query("
        SELECT COUNT(*) as total, SUM(monto_pagado) as monto_total
        FROM cuotas 
        WHERE DATE(updated_at) = '$hoy'
        AND estado IN ('pagada', 'parcial')
    ");
    $result = $stmt->fetch();
    echo "   Total: {$result['total']} cuotas\n";
    echo "   Monto: L " . number_format($result['monto_total'] ?? 0, 2) . "\n\n";

    // 3. Verificar todas las cuotas pagadas (sin filtro de fecha)
    echo "3. Todas las cuotas pagadas/parciales:\n";
    $stmt = $db->query("
        SELECT COUNT(*) as total, SUM(monto_pagado) as monto_total
        FROM cuotas 
        WHERE estado IN ('pagada', 'parcial')
    ");
    $result = $stmt->fetch();
    echo "   Total: {$result['total']} cuotas\n";
    echo "   Monto: L " . number_format($result['monto_total'] ?? 0, 2) . "\n\n";

    if ($result['total'] > 0) {
        echo "   Últimas 5 cuotas pagadas:\n";
        $stmt = $db->query("
            SELECT id, prestamo_id, numero_cuota, monto_pagado, fecha_pago_real, updated_at, estado
            FROM cuotas 
            WHERE estado IN ('pagada', 'parcial')
            ORDER BY updated_at DESC
            LIMIT 5
        ");
        while ($row = $stmt->fetch()) {
            echo "   - Cuota #{$row['numero_cuota']} (Préstamo {$row['prestamo_id']}): L " .
                number_format($row['monto_pagado'], 2) .
                " - Pago real: " . ($row['fecha_pago_real'] ?? 'NULL') .
                " - Actualizado: {$row['updated_at']} - {$row['estado']}\n";
        }
        echo "\n";
    }

    // 4. Verificar si existe tabla pagos
    echo "4. Verificando tabla 'pagos':\n";
    $stmt = $db->query("SHOW TABLES LIKE 'pagos'");
    if ($stmt->fetch()) {
        echo "   ✓ La tabla 'pagos' existe\n";

        $stmt = $db->query("SELECT COUNT(*) as total FROM pagos");
        $result = $stmt->fetch();
        echo "   Total de registros: {$result['total']}\n";

        if ($result['total'] > 0) {
            echo "   Últimos 5 pagos:\n";
            $stmt = $db->query("SELECT * FROM pagos ORDER BY created_at DESC LIMIT 5");
            while ($row = $stmt->fetch()) {
                echo "   - ID: {$row['id']} - Monto: L " . number_format($row['monto_total'] ?? 0, 2) .
                    " - Fecha: " . ($row['fecha_pago'] ?? $row['created_at']) . "\n";
            }
        }
    } else {
        echo "   ✗ La tabla 'pagos' NO existe\n";
    }
    echo "\n";

    // 5. Buscar otras tablas que puedan tener pagos
    echo "5. Buscando otras tablas relacionadas:\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $paymentTables = array_filter($tables, function ($table) {
        return stripos($table, 'pago') !== false ||
            stripos($table, 'cobro') !== false ||
            stripos($table, 'recaudo') !== false;
    });

    if (!empty($paymentTables)) {
        echo "   Tablas encontradas:\n";
        foreach ($paymentTables as $table) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM `$table`");
            $result = $stmt->fetch();
            echo "   - $table: {$result['total']} registros\n";
        }
    } else {
        echo "   No se encontraron otras tablas relacionadas\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
