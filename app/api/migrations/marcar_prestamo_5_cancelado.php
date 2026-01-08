<?php
/**
 * Script para marcar préstamo #5 como Cancelado
 * Este préstamo fue refinanciado antes de implementar la lógica de cancelación automática
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // ID del préstamo a actualizar
    $prestamoId = 5;

    // Verificar que el préstamo existe
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        throw new Exception("Préstamo #$prestamoId no encontrado");
    }

    // Verificar el estado actual
    echo "Estado actual del préstamo #$prestamoId:\n";
    echo "- Cliente: " . $prestamo['id_cliente'] . "\n";
    echo "- Monto: L " . number_format($prestamo['monto_capital'], 2) . "\n";
    echo "- Estado actual: " . $prestamo['estado'] . "\n";
    echo "- Tipo: " . ($prestamo['tipo_prestamo'] ?? 'N/A') . "\n";
    echo "\n";

    // Verificar si hay un préstamo de refinanciamiento posterior
    $stmt = $db->prepare("
        SELECT id, monto_capital, estado, tipo_prestamo, fecha_solicitud
        FROM prestamos 
        WHERE id_cliente = ? 
        AND id > ?
        AND tipo_prestamo = 'Refinanciamiento'
        ORDER BY id ASC
        LIMIT 1
    ");
    $stmt->execute([$prestamo['id_cliente'], $prestamoId]);
    $refinanciamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($refinanciamiento) {
        echo "Préstamo de refinanciamiento encontrado:\n";
        echo "- ID: #" . $refinanciamiento['id'] . "\n";
        echo "- Monto: L " . number_format($refinanciamiento['monto_capital'], 2) . "\n";
        echo "- Estado: " . $refinanciamiento['estado'] . "\n";
        echo "- Fecha: " . $refinanciamiento['fecha_solicitud'] . "\n";
        echo "\n";
    }

    // Verificar cuotas del préstamo original
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_cuotas,
            SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as cuotas_pagadas,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as cuotas_pendientes
        FROM cuotas 
        WHERE prestamo_id = ?
    ");
    $stmt->execute([$prestamoId]);
    $cuotas = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Estado de cuotas:\n";
    echo "- Total: " . $cuotas['total_cuotas'] . "\n";
    echo "- Pagadas: " . $cuotas['cuotas_pagadas'] . "\n";
    echo "- Pendientes: " . $cuotas['cuotas_pendientes'] . "\n";
    echo "\n";

    // Determinar el nuevo estado según la lógica
    if ($cuotas['total_cuotas'] == $cuotas['cuotas_pagadas']) {
        $nuevoEstado = 'Finalizado';
        $observacion = 'Préstamo completamente pagado antes del refinanciamiento';
    } else {
        $nuevoEstado = 'Cancelado';
        $observacion = $refinanciamiento
            ? "[Cancelado por refinanciamiento - Nuevo préstamo #" . $refinanciamiento['id'] . "]"
            : "[Cancelado por refinanciamiento]";
    }

    echo "Acción a realizar:\n";
    echo "- Nuevo estado: $nuevoEstado\n";
    echo "- Observación: $observacion\n";
    echo "\n";

    // Actualizar el préstamo
    $stmt = $db->prepare("
        UPDATE prestamos 
        SET estado = ?,
            observaciones = CONCAT(IFNULL(observaciones, ''), ' ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$nuevoEstado, $observacion, $prestamoId]);

    echo "✅ Préstamo #$prestamoId actualizado exitosamente\n";
    echo "Estado cambiado de '{$prestamo['estado']}' a '$nuevoEstado'\n";

    // Verificar el cambio
    $stmt = $db->prepare("SELECT estado, observaciones FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'prestamo_id' => $prestamoId,
        'estado_anterior' => $prestamo['estado'],
        'estado_nuevo' => $updated['estado'],
        'observaciones' => $updated['observaciones'],
        'mensaje' => "Préstamo #$prestamoId actualizado a '$nuevoEstado'"
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>