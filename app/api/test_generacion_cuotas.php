<?php
/**
 * Script de Prueba - Verificar Generación de Cuotas
 * 
 * Este script simula el cambio de estado de un préstamo a "Pendiente de Operaciones"
 * y verifica que las cuotas se generen correctamente.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/PrestamoHelper.php';

echo "=== PRUEBA DE GENERACIÓN DE CUOTAS ===\n\n";

try {
    $db = getDB();

    // 1. Buscar un préstamo en estado "En Análisis" o "Verificación de Campo"
    $stmt = $db->query("SELECT * FROM prestamos 
                        WHERE estado IN ('En Análisis', 'Verificación de Campo', 'Solicitado') 
                        LIMIT 1");
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        echo "❌ No se encontró ningún préstamo en estado de análisis para probar.\n";
        echo "   Crea un préstamo de prueba primero.\n";
        exit;
    }

    echo "✓ Préstamo encontrado:\n";
    echo "  ID: {$prestamo['id']}\n";
    echo "  Monto: L {$prestamo['monto_capital']}\n";
    echo "  Modalidad: {$prestamo['modalidad']}\n";
    echo "  Plazo: {$prestamo['plazo_meses']} meses\n";
    echo "  Estado actual: {$prestamo['estado']}\n\n";

    // 2. Verificar cuotas existentes
    $stmtCuotas = $db->prepare("SELECT COUNT(*) as total FROM cuotas WHERE prestamo_id = ?");
    $stmtCuotas->execute([$prestamo['id']]);
    $cuotasAntes = $stmtCuotas->fetch(PDO::FETCH_ASSOC)['total'];

    echo "📊 Cuotas existentes antes: $cuotasAntes\n\n";

    // 3. Simular generación de cuotas
    echo "🔄 Simulando cambio a 'Pendiente de Operaciones'...\n";

    $db->beginTransaction();

    try {
        // Eliminar cuotas existentes
        $stmtDelete = $db->prepare("DELETE FROM cuotas WHERE prestamo_id = ?");
        $stmtDelete->execute([$prestamo['id']]);

        // Generar nuevas cuotas
        $montoCuota = floatval($prestamo['valor_cuota']);
        $periodoMeses = intval($prestamo['plazo_meses']);
        $fechaInicio = date('Y-m-d');
        $diaPago = intval(date('d'));
        $modalidad = strtolower($prestamo['modalidad']);

        PrestamoHelper::generateCuotasModalidad(
            $db,
            $prestamo['id'],
            $montoCuota,
            $periodoMeses,
            $fechaInicio,
            $diaPago,
            $modalidad
        );

        // Actualizar estado
        $stmtUpdate = $db->prepare("UPDATE prestamos SET estado = 'Pendiente de Operaciones', updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$prestamo['id']]);

        $db->commit();

        echo "✓ Transacción completada exitosamente\n\n";

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    // 4. Verificar cuotas generadas
    $stmtCuotasDespues = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
    $stmtCuotasDespues->execute([$prestamo['id']]);
    $cuotas = $stmtCuotasDespues->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 Cuotas generadas: " . count($cuotas) . "\n\n";

    if (count($cuotas) > 0) {
        echo "✅ ÉXITO - Cuotas generadas correctamente\n\n";

        echo "📅 Primeras 5 cuotas:\n";
        echo str_repeat("-", 60) . "\n";
        printf("%-5s %-15s %-15s\n", "#", "Fecha", "Monto");
        echo str_repeat("-", 60) . "\n";

        foreach (array_slice($cuotas, 0, 5) as $cuota) {
            printf(
                "%-5d %-15s L %12.2f\n",
                $cuota['numero_cuota'],
                $cuota['fecha_vencimiento'],
                $cuota['monto_cuota']
            );
        }

        if (count($cuotas) > 5) {
            echo "... y " . (count($cuotas) - 5) . " cuotas más\n";
        }

        echo str_repeat("-", 60) . "\n\n";

        // Verificar reglas de fechas
        echo "🔍 Verificando reglas de fechas:\n";

        if ($modalidad === 'diario') {
            $finDeSemana = 0;
            foreach ($cuotas as $cuota) {
                $fecha = new DateTime($cuota['fecha_vencimiento']);
                $diaSemana = $fecha->format('N'); // 1=Lunes, 7=Domingo
                if ($diaSemana == 6 || $diaSemana == 7) {
                    $finDeSemana++;
                }
            }

            if ($finDeSemana == 0) {
                echo "  ✓ Modalidad Diaria: Ninguna cuota cae en fin de semana\n";
            } else {
                echo "  ❌ Modalidad Diaria: $finDeSemana cuotas caen en fin de semana\n";
            }
        } else {
            echo "  ℹ️ Modalidad {$prestamo['modalidad']}: No aplica validación de fines de semana\n";
        }

    } else {
        echo "❌ ERROR - No se generaron cuotas\n";
    }

    echo "\n=== FIN DE LA PRUEBA ===\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>