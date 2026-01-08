<?php
/**
 * Script de verificación: Comprobar desglose de cuotas
 * Fecha: 2026-01-08
 * Descripción: Verifica que las cuotas tengan el desglose correcto
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Verificación de Desglose de Cuotas ===\n\n";

    // Verificar estructura de tabla
    echo "1. Verificando estructura de la tabla cuotas...\n";
    $stmt = $db->query("SHOW COLUMNS FROM cuotas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $requiredFields = ['capital_cuota', 'interes_cuota', 'gastos_cuota', 'comision_cuota'];
    $foundFields = [];

    foreach ($columns as $column) {
        if (in_array($column['Field'], $requiredFields)) {
            $foundFields[] = $column['Field'];
            echo "   ✓ Campo '{$column['Field']}' existe (Tipo: {$column['Type']})\n";
        }
    }

    $missingFields = array_diff($requiredFields, $foundFields);
    if (!empty($missingFields)) {
        echo "   ✗ Campos faltantes: " . implode(', ', $missingFields) . "\n";
        exit(1);
    }

    echo "\n2. Verificando cuotas con desglose...\n";

    // Obtener estadísticas
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_cuotas,
            SUM(CASE WHEN capital_cuota > 0 THEN 1 ELSE 0 END) as con_desglose,
            SUM(CASE WHEN capital_cuota = 0 OR capital_cuota IS NULL THEN 1 ELSE 0 END) as sin_desglose
        FROM cuotas
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "   Total de cuotas: {$stats['total_cuotas']}\n";
    echo "   Con desglose: {$stats['con_desglose']}\n";
    echo "   Sin desglose: {$stats['sin_desglose']}\n";

    if ($stats['sin_desglose'] > 0) {
        echo "\n   ⚠ Hay {$stats['sin_desglose']} cuotas sin desglose.\n";
        echo "   Ejecuta: php app/api/migrations/recalcular_cuotas_existentes.php\n";
    }

    echo "\n3. Ejemplo de cuotas con desglose:\n";

    // Mostrar ejemplos
    $stmt = $db->query("
        SELECT 
            c.id,
            c.prestamo_id,
            c.numero_cuota,
            c.monto_cuota,
            c.capital_cuota,
            c.interes_cuota,
            c.gastos_cuota,
            c.comision_cuota,
            p.monto_capital,
            p.total_a_pagar
        FROM cuotas c
        INNER JOIN prestamos p ON c.prestamo_id = p.id
        WHERE c.capital_cuota > 0
        ORDER BY c.prestamo_id, c.numero_cuota
        LIMIT 5
    ");

    $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ejemplos)) {
        echo "   ⚠ No se encontraron cuotas con desglose.\n";
    } else {
        foreach ($ejemplos as $cuota) {
            echo "\n   Préstamo ID: {$cuota['prestamo_id']} - Cuota #{$cuota['numero_cuota']}\n";
            echo "   ├─ Monto Total: L " . number_format($cuota['monto_cuota'], 2) . "\n";
            echo "   ├─ Capital: L " . number_format($cuota['capital_cuota'], 2) . "\n";
            echo "   ├─ Interés: L " . number_format($cuota['interes_cuota'], 2) . "\n";
            echo "   ├─ Gastos: L " . number_format($cuota['gastos_cuota'], 2) . "\n";
            echo "   └─ Comisión: L " . number_format($cuota['comision_cuota'], 2) . "\n";

            // Verificar que la suma sea correcta
            $suma = $cuota['capital_cuota'] + $cuota['interes_cuota'] + $cuota['gastos_cuota'] + $cuota['comision_cuota'];
            $diferencia = abs($suma - $cuota['monto_cuota']);

            if ($diferencia > 0.01) {
                echo "   ⚠ ADVERTENCIA: La suma no coincide (diferencia: L " . number_format($diferencia, 2) . ")\n";
            } else {
                echo "   ✓ Suma verificada correctamente\n";
            }
        }
    }

    echo "\n4. Verificando proporciones 4-4-3...\n";

    // Verificar que las proporciones sean correctas
    $stmt = $db->query("
        SELECT 
            c.id,
            c.numero_cuota,
            c.interes_cuota,
            c.gastos_cuota,
            c.comision_cuota,
            (c.interes_cuota + c.gastos_cuota + c.comision_cuota) as total_interes
        FROM cuotas c
        WHERE c.capital_cuota > 0
        LIMIT 3
    ");

    $proporciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($proporciones as $cuota) {
        $totalInteres = $cuota['total_interes'];
        if ($totalInteres > 0) {
            $propInteres = ($cuota['interes_cuota'] / $totalInteres) * 11;
            $propGastos = ($cuota['gastos_cuota'] / $totalInteres) * 11;
            $propComision = ($cuota['comision_cuota'] / $totalInteres) * 11;

            echo "   Cuota #{$cuota['numero_cuota']}:\n";
            echo "   ├─ Proporción Interés: " . number_format($propInteres, 2) . " (esperado: 4.00)\n";
            echo "   ├─ Proporción Gastos: " . number_format($propGastos, 2) . " (esperado: 4.00)\n";
            echo "   └─ Proporción Comisión: " . number_format($propComision, 2) . " (esperado: 3.00)\n";

            if (abs($propInteres - 4) < 0.01 && abs($propGastos - 4) < 0.01 && abs($propComision - 3) < 0.01) {
                echo "   ✓ Proporciones correctas (4-4-3)\n\n";
            } else {
                echo "   ⚠ Las proporciones no coinciden con 4-4-3\n\n";
            }
        }
    }

    echo "=== Verificación completada ===\n";

} catch (Exception $e) {
    echo "✗ Error en la verificación: " . $e->getMessage() . "\n";
    exit(1);
}
