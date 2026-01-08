<?php
/**
 * Script: Investigar cuotas sin desglose
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Investigando cuotas sin desglose ===\n\n";

    $stmt = $db->query("
        SELECT 
            c.id,
            c.prestamo_id,
            c.numero_cuota,
            c.monto_cuota,
            c.estado,
            c.capital_cuota,
            p.estado as estado_prestamo,
            p.monto_capital,
            p.total_a_pagar
        FROM cuotas c
        INNER JOIN prestamos p ON c.prestamo_id = p.id
        WHERE c.capital_cuota = 0 OR c.capital_cuota IS NULL
        ORDER BY c.prestamo_id, c.numero_cuota
    ");

    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total de cuotas sin desglose: " . count($cuotas) . "\n\n";

    foreach ($cuotas as $cuota) {
        echo "Cuota ID: {$cuota['id']}\n";
        echo "  Préstamo ID: {$cuota['prestamo_id']}\n";
        echo "  Número: {$cuota['numero_cuota']}\n";
        echo "  Monto: L " . number_format($cuota['monto_cuota'], 2) . "\n";
        echo "  Estado Cuota: {$cuota['estado']}\n";
        echo "  Estado Préstamo: {$cuota['estado_prestamo']}\n";
        echo "  Capital Préstamo: L " . number_format($cuota['monto_capital'], 2) . "\n";
        echo "  Total a Pagar: L " . number_format($cuota['total_a_pagar'], 2) . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
