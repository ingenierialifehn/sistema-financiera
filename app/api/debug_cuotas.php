<?php
/**
 * Script de Depuración - Verificar por qué no se generan cuotas
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/PrestamoHelper.php';

echo "=== DEBUG: GENERACIÓN DE CUOTAS ===\n\n";

try {
    $db = getDB();

    // Obtener el ID del préstamo desde parámetro o buscar uno
    $prestamoId = isset($argv[1]) ? intval($argv[1]) : null;

    if (!$prestamoId) {
        // Buscar el último préstamo
        $stmt = $db->query("SELECT id FROM prestamos ORDER BY id DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $prestamoId = $result ? $result['id'] : null;
    }

    if (!$prestamoId) {
        echo "❌ No se encontró ningún préstamo en la base de datos.\n";
        exit;
    }

    echo "📋 Préstamo ID: $prestamoId\n\n";

    // Obtener datos del préstamo
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        echo "❌ Préstamo no encontrado.\n";
        exit;
    }

    echo "Datos del Préstamo:\n";
    echo "  Estado: {$loan['estado']}\n";
    echo "  Monto Capital: L {$loan['monto_capital']}\n";
    echo "  Modalidad: {$loan['modalidad']}\n";
    echo "  Plazo: {$loan['plazo_meses']} meses\n";
    echo "  Valor Cuota: L {$loan['valor_cuota']}\n";
    echo "  Total a Pagar: L {$loan['total_a_pagar']}\n\n";

    // Verificar cuotas existentes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM cuotas WHERE prestamo_id = ?");
    $stmt->execute([$prestamoId]);
    $cuotasExistentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "📊 Cuotas existentes: $cuotasExistentes\n\n";

    if ($cuotasExistentes > 0) {
        echo "⚠️ Ya existen cuotas. ¿Desea regenerarlas? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 's') {
            echo "Operación cancelada.\n";
            exit;
        }
        fclose($handle);
    }

    // Intentar generar cuotas
    echo "🔄 Generando cuotas...\n\n";

    $db->beginTransaction();

    try {
        // Eliminar cuotas existentes
        $stmt = $db->prepare("DELETE FROM cuotas WHERE prestamo_id = ?");
        $stmt->execute([$prestamoId]);
        echo "  ✓ Cuotas anteriores eliminadas\n";

        // Preparar parámetros
        $montoCuota = floatval($loan['valor_cuota']);
        $periodoMeses = intval($loan['plazo_meses']);
        $fechaInicio = date('Y-m-d');
        $diaPago = intval(date('d'));
        $modalidad = strtolower($loan['modalidad']);

        echo "  Parámetros:\n";
        echo "    - Monto Cuota: L $montoCuota\n";
        echo "    - Periodo: $periodoMeses meses\n";
        echo "    - Fecha Inicio: $fechaInicio\n";
        echo "    - Día Pago: $diaPago\n";
        echo "    - Modalidad: $modalidad\n\n";

        // Calcular número de cuotas esperadas
        $numeroCuotas = PrestamoHelper::calculateNumeroCuotas($periodoMeses, $modalidad);
        echo "  Número de cuotas esperadas: $numeroCuotas\n\n";

        // Generar cuotas
        PrestamoHelper::generateCuotasModalidad(
            $db,
            $prestamoId,
            $montoCuota,
            $periodoMeses,
            $fechaInicio,
            $diaPago,
            $modalidad
        );

        echo "  ✓ Función de generación ejecutada\n\n";

        // Verificar cuotas generadas
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM cuotas WHERE prestamo_id = ?");
        $stmt->execute([$prestamoId]);
        $cuotasGeneradas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo "📊 Cuotas generadas: $cuotasGeneradas\n\n";

        if ($cuotasGeneradas > 0) {
            // Mostrar primeras 5 cuotas
            $stmt = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC LIMIT 5");
            $stmt->execute([$prestamoId]);
            $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Primeras 5 cuotas:\n";
            echo str_repeat("-", 60) . "\n";
            printf("%-5s %-15s %-15s\n", "#", "Fecha", "Monto");
            echo str_repeat("-", 60) . "\n";

            foreach ($cuotas as $cuota) {
                printf(
                    "%-5d %-15s L %12.2f\n",
                    $cuota['numero_cuota'],
                    $cuota['fecha_vencimiento'],
                    $cuota['monto_cuota']
                );
            }

            echo str_repeat("-", 60) . "\n\n";

            $db->commit();
            echo "✅ ÉXITO - Cuotas generadas y guardadas correctamente\n";

        } else {
            $db->rollBack();
            echo "❌ ERROR - No se generaron cuotas\n";
            echo "   Verifica que la función generateCuotasModalidad esté funcionando correctamente.\n";
        }

    } catch (Exception $e) {
        $db->rollBack();
        echo "❌ ERROR en la transacción: " . $e->getMessage() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>