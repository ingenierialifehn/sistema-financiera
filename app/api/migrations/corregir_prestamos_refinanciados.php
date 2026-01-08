<?php
/**
 * Script para corregir préstamos refinanciados que quedaron activos
 * Busca todos los préstamos que fueron refinanciados pero no se marcaron como cancelados
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    echo "=== CORRECCIÓN DE PRÉSTAMOS REFINANCIADOS ===\n\n";

    // Buscar todos los préstamos de tipo "Refinanciamiento"
    $stmt = $db->query("
        SELECT id, id_cliente, monto_capital, estado, fecha_solicitud
        FROM prestamos 
        WHERE tipo_prestamo = 'Refinanciamiento'
        ORDER BY id_cliente, id
    ");
    $refinanciamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Préstamos de refinanciamiento encontrados: " . count($refinanciamientos) . "\n\n";

    $corregidos = [];

    foreach ($refinanciamientos as $refi) {
        // Para cada refinanciamiento, buscar el préstamo original (anterior del mismo cliente)
        $stmt = $db->prepare("
            SELECT id, monto_capital, estado, tipo_prestamo
            FROM prestamos 
            WHERE id_cliente = ? 
            AND id < ?
            AND tipo_prestamo != 'Refinanciamiento'
            AND estado = 'Activo'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$refi['id_cliente'], $refi['id']]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($original) {
            echo "Préstamo original encontrado:\n";
            echo "- Préstamo original: #" . $original['id'] . " (L " . number_format($original['monto_capital'], 2) . ")\n";
            echo "- Estado actual: " . $original['estado'] . "\n";
            echo "- Refinanciamiento: #" . $refi['id'] . " (L " . number_format($refi['monto_capital'], 2) . ")\n";

            // Verificar cuotas
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas
                FROM cuotas 
                WHERE prestamo_id = ?
            ");
            $stmt->execute([$original['id']]);
            $cuotas = $stmt->fetch(PDO::FETCH_ASSOC);

            // Determinar nuevo estado
            if ($cuotas['total'] == $cuotas['pagadas']) {
                $nuevoEstado = 'Finalizado';
                $obs = 'Completamente pagado antes del refinanciamiento';
            } else {
                $nuevoEstado = 'Cancelado';
                $obs = "[Cancelado por refinanciamiento - Nuevo préstamo #" . $refi['id'] . "]";
            }

            // Actualizar
            $stmt = $db->prepare("
                UPDATE prestamos 
                SET estado = ?,
                    observaciones = CONCAT(IFNULL(observaciones, ''), ' ', ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nuevoEstado, $obs, $original['id']]);

            echo "✅ Actualizado a: $nuevoEstado\n";
            echo "   Cuotas: {$cuotas['pagadas']}/{$cuotas['total']} pagadas\n\n";

            $corregidos[] = [
                'prestamo_original_id' => $original['id'],
                'refinanciamiento_id' => $refi['id'],
                'estado_anterior' => $original['estado'],
                'estado_nuevo' => $nuevoEstado,
                'cuotas_pagadas' => $cuotas['pagadas'],
                'cuotas_total' => $cuotas['total']
            ];
        }
    }

    echo "\n=== RESUMEN ===\n";
    echo "Total de préstamos corregidos: " . count($corregidos) . "\n\n";

    echo json_encode([
        'success' => true,
        'total_corregidos' => count($corregidos),
        'detalles' => $corregidos
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>