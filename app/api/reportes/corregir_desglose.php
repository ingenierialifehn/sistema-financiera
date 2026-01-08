<?php
/**
 * Script de Corrección de Desglose en Cuotas
 * Calcula y actualiza el desglose para cuotas que no lo tienen
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $db->beginTransaction();

    // Obtener todas las cuotas sin desglose
    $sqlCuotasSinDesglose = "SELECT id, monto_pagado, monto_cuota
                             FROM cuotas 
                             WHERE (capital_cuota IS NULL OR capital_cuota = 0)
                             AND monto_pagado > 0";

    $stmt = $db->query($sqlCuotasSinDesglose);
    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actualizadas = 0;

    foreach ($cuotas as $cuota) {
        $montoPagado = floatval($cuota['monto_pagado']);
        $montoCuota = floatval($cuota['monto_cuota']);

        // Usar el monto pagado si existe, si no usar monto_cuota
        $montoBase = $montoPagado > 0 ? $montoPagado : $montoCuota;

        if ($montoBase > 0) {
            // Calcular desglose
            // Capital = monto / 1.11
            $capital = $montoBase / 1.11;
            $interes = $capital * 0.04;
            $gastos = $capital * 0.04;
            $comision = $capital * 0.03;

            // Actualizar la cuota
            $sqlUpdate = "UPDATE cuotas 
                         SET capital_cuota = ?,
                             interes_cuota = ?,
                             gastos_cuota = ?,
                             comision_cuota = ?
                         WHERE id = ?";

            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->execute([
                $capital,
                $interes,
                $gastos,
                $comision,
                $cuota['id']
            ]);

            $actualizadas++;
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'cuotas_encontradas' => count($cuotas),
            'cuotas_actualizadas' => $actualizadas,
            'mensaje' => "Se actualizaron {$actualizadas} cuotas con el desglose calculado."
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>