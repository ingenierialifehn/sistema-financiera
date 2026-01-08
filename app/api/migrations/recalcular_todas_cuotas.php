<?php
/**
 * Script: Recalcular TODAS las cuotas (incluyendo pagadas)
 * Fecha: 2026-01-08
 * Descripción: Aplica el desglose detallado a TODAS las cuotas sin desglose
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Iniciando recálculo de TODAS las cuotas ===\n\n";

    // Proporciones del interés según regla 4-4-3 (total = 11)
    $propInteres = 4 / 11;
    $propGastos = 4 / 11;
    $propComision = 3 / 11;

    // Obtener todos los préstamos con cuotas sin desglose
    $stmt = $db->query("
        SELECT DISTINCT p.id, p.monto_capital, p.neto_entregar, p.total_a_pagar, p.estado
        FROM prestamos p
        INNER JOIN cuotas c ON p.id = c.prestamo_id
        WHERE (c.capital_cuota IS NULL OR c.capital_cuota = 0)
    ");

    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPrestamos = count($prestamos);

    echo "→ Encontrados $totalPrestamos préstamos con cuotas sin desglose\n\n";

    if ($totalPrestamos === 0) {
        echo "✓ No hay cuotas para recalcular.\n";
        exit(0);
    }

    $db->beginTransaction();

    $cuotasActualizadas = 0;

    foreach ($prestamos as $prestamo) {
        $prestamoId = $prestamo['id'];

        // Calcular ratio de interés del préstamo
        $totalPagar = floatval($prestamo['total_a_pagar']);
        $capitalOriginal = floatval($prestamo['neto_entregar'] ?: $prestamo['monto_capital']);
        $interesTotal = $totalPagar - $capitalOriginal;
        $ratioInteres = ($totalPagar > 0) ? ($interesTotal / $totalPagar) : 0;

        echo "→ Procesando préstamo ID: $prestamoId\n";
        echo "   Capital: L " . number_format($capitalOriginal, 2) . "\n";
        echo "   Total a Pagar: L " . number_format($totalPagar, 2) . "\n";
        echo "   Interés Total: L " . number_format($interesTotal, 2) . "\n";
        echo "   Ratio Interés: " . number_format($ratioInteres * 100, 2) . "%\n";

        // Obtener TODAS las cuotas de este préstamo sin desglose
        $stmtCuotas = $db->prepare("
            SELECT id, monto_cuota, numero_cuota, estado
            FROM cuotas
            WHERE prestamo_id = ?
            AND (capital_cuota IS NULL OR capital_cuota = 0)
            ORDER BY numero_cuota ASC
        ");
        $stmtCuotas->execute([$prestamoId]);
        $cuotas = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cuotas as $cuota) {
            $montoCuota = floatval($cuota['monto_cuota']);

            // Calcular desglose de la cuota
            $parteInteresMonto = $montoCuota * $ratioInteres;
            $parteCapitalMonto = $montoCuota - $parteInteresMonto;

            // Desglosar el interés según regla 4-4-3
            $interesCuota = $parteInteresMonto * $propInteres;
            $gastosCuota = $parteInteresMonto * $propGastos;
            $comisionCuota = $parteInteresMonto * $propComision;

            // Actualizar la cuota
            $stmtUpdate = $db->prepare("
                UPDATE cuotas
                SET capital_cuota = ?,
                    interes_cuota = ?,
                    gastos_cuota = ?,
                    comision_cuota = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $parteCapitalMonto,
                $interesCuota,
                $gastosCuota,
                $comisionCuota,
                $cuota['id']
            ]);

            $cuotasActualizadas++;

            echo "   ✓ Cuota #{$cuota['numero_cuota']} ({$cuota['estado']}): L " . number_format($montoCuota, 2) .
                " → Capital: L " . number_format($parteCapitalMonto, 2) .
                ", Interés: L " . number_format($interesCuota, 2) .
                ", Gastos: L " . number_format($gastosCuota, 2) .
                ", Comisión: L " . number_format($comisionCuota, 2) . "\n";
        }

        echo "\n";
    }

    $db->commit();

    echo "=== Recálculo completado exitosamente ===\n";
    echo "Total de préstamos procesados: $totalPrestamos\n";
    echo "Total de cuotas actualizadas: $cuotasActualizadas\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "✗ Error en el recálculo: " . $e->getMessage() . "\n";
    exit(1);
}
