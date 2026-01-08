<?php
/**
 * Script: Corregir fecha_pago_real en cuotas pagadas
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Corrigiendo fecha_pago_real en cuotas pagadas ===\n\n";

    // Buscar cuotas pagadas/parciales sin fecha_pago_real
    $stmt = $db->query("
        SELECT id, prestamo_id, numero_cuota, monto_pagado, estado, updated_at
        FROM cuotas
        WHERE estado IN ('pagada', 'parcial')
        AND fecha_pago_real IS NULL
    ");

    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($cuotas);

    echo "→ Encontradas $total cuotas sin fecha_pago_real\n\n";

    if ($total === 0) {
        echo "✓ No hay cuotas para corregir.\n";
        exit(0);
    }

    $db->beginTransaction();

    foreach ($cuotas as $cuota) {
        // Usar updated_at como fecha_pago_real
        $fechaPago = $cuota['updated_at'];

        $upd = $db->prepare("
            UPDATE cuotas 
            SET fecha_pago_real = ? 
            WHERE id = ?
        ");
        $upd->execute([$fechaPago, $cuota['id']]);

        echo "✓ Cuota #{$cuota['numero_cuota']} (Préstamo {$cuota['prestamo_id']}): fecha_pago_real = $fechaPago\n";
    }

    $db->commit();

    echo "\n=== Corrección completada ===\n";
    echo "Total de cuotas corregidas: $total\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
}
