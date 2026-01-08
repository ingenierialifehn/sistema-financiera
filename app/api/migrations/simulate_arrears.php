<?php
/**
 * Script de simulación de atrasos (Mora)
 * Ajusta fechas de préstamos y cuotas para simular antigüedad
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';

try {
    $db = getDB();
    $db->beginTransaction();

    $simulaciones = [
        'CLIENTE PRUEBA A' => ['meses' => 1],
        'CLIENTE PRUEBA B' => ['meses' => 3],
        'CLIENTE PRUEBA C' => ['meses' => 6]
    ];

    $log = [];

    foreach ($simulaciones as $nombreCliente => $config) {
        $mesesAtras = $config['meses'];

        // 1. Buscar Cliente
        $stmt = $db->prepare("SELECT id, nombre_completo FROM clientes WHERE nombre_completo LIKE :nombre LIMIT 1");
        $stmt->execute(['nombre' => '%' . $nombreCliente . '%']);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $log[] = "Cliente no encontrado: $nombreCliente";
            continue;
        }

        // 2. Buscar último préstamo activo (o recién entregado)
        $stmt = $db->prepare("
            SELECT id, fecha_desembolso, fecha_solicitud 
            FROM prestamos 
            WHERE id_cliente = :id_cliente 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['id_cliente' => $cliente['id']]);
        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prestamo) {
            $log[] = "Préstamo no encontrado para: " . $cliente['nombre_completo'];
            continue;
        }

        // 3. Calcular desplazamiento
        // Si entregué hoy (o recientemente), quiero moverlo X meses atrás.
        // Interval string: "-X months"
        $interval = "-{$mesesAtras} months";

        // Actualizar Préstamo
        $sqlPrestamo = "
            UPDATE prestamos 
            SET fecha_desembolso = DATE_ADD(fecha_desembolso, INTERVAL :interval MONTH),
                fecha_aprobacion = DATE_ADD(fecha_aprobacion, INTERVAL :interval MONTH),
                fecha_solicitud = DATE_ADD(fecha_solicitud, INTERVAL :interval MONTH),
                created_at = DATE_ADD(created_at, INTERVAL :interval MONTH)
            WHERE id = :id_prestamo
        ";

        // En MySQL INTERVAL usa sintaxis directa, pero con parámetro bound es tricky.
        // Vamos a calcular las fechas en PHP mejor para seguridad y control.

        // Asumiendo que la fecha base es HOY o la fecha actual del registro
        // Pero el usuario dice "como si se entregó hace un mes".
        // Lo mejor es restar X meses a todas las fechas actuales del registro.

        $sqlUpdatePrestamo = "
            UPDATE prestamos 
            SET fecha_desembolso = DATE_SUB(fecha_desembolso, INTERVAL :meses MONTH),
                fecha_aprobacion = DATE_SUB(fecha_aprobacion, INTERVAL :meses MONTH),
                fecha_solicitud = DATE_SUB(fecha_solicitud, INTERVAL :meses MONTH),
                created_at = DATE_SUB(created_at, INTERVAL :meses MONTH)
            WHERE id = :id_prestamo
        ";

        $stmtUpdate = $db->prepare($sqlUpdatePrestamo);
        $stmtUpdate->execute([
            'meses' => $mesesAtras,
            'id_prestamo' => $prestamo['id']
        ]);

        // 4. Actualizar Cuotas
        // También movemos las fechas de vencimiento X meses atrás
        $sqlUpdateCuotas = "
            UPDATE cuotas 
            SET fecha_vencimiento = DATE_SUB(fecha_vencimiento, INTERVAL :meses MONTH)
            WHERE prestamo_id = :id_prestamo
        ";

        $stmtUpdateCuotas = $db->prepare($sqlUpdateCuotas);
        $stmtUpdateCuotas->execute([
            'meses' => $mesesAtras,
            'id_prestamo' => $prestamo['id']
        ]);

        $log[] = "Actualizado {$cliente['nombre_completo']}: Retrasado {$mesesAtras} meses (Préstamo ID: {$prestamo['id']})";
    }

    $db->commit();

    echo "Simulación completada con éxito:\n";
    echo implode("\n", $log);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
