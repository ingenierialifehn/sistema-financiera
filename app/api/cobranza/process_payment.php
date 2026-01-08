<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';

header('Content-Type: application/json');
session_start();

try {
    $user = $_SESSION['id_usuario'] ?? 1;
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    $prestamoId = $data['prestamo_id'];
    $montoRecibido = floatval($data['monto']);
    $esCapital = $data['es_capital'] ?? false;
    $fecha = date('Y-m-d H:i:s');

    if ($montoRecibido <= 0) {
        throw new Exception("El monto debe ser mayor a 0.");
    }

    $db->beginTransaction();

    if ($esCapital) {
        // --- ABONO A CAPITAL ---
        $stmt = $db->prepare("SELECT monto_capital FROM prestamos WHERE id = ? FOR UPDATE");
        $stmt->execute([$prestamoId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        $nuevoSaldo = floatval($p['monto_capital']) - $montoRecibido;
        if ($nuevoSaldo < 0)
            $nuevoSaldo = 0;

        $upd = $db->prepare("UPDATE prestamos SET monto_capital = ? WHERE id = ?");
        $upd->execute([$nuevoSaldo, $prestamoId]);

        $msg = "Abono a Capital registrado. Nuevo saldo capital: L " . number_format($nuevoSaldo, 2);

    } else {
        // --- PAGO DE CUOTAS (CON PARCIALES) ---
        // Buscar cuotas NO pagadas totalmente (pendiente o parcial)
        $stmt = $db->prepare("SELECT id, monto_cuota, monto_pagado, numero_cuota FROM cuotas WHERE prestamo_id = ? AND estado != 'pagada' ORDER BY fecha_vencimiento ASC, numero_cuota ASC");
        $stmt->execute([$prestamoId]);
        $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dineroRestante = $montoRecibido;
        $detallesPago = [];

        foreach ($pendientes as $cuota) {
            if ($dineroRestante <= 0.01)
                break; // Margen error float

            $montoTotalCuota = floatval($cuota['monto_cuota']);
            $pagadoPreviamente = floatval($cuota['monto_pagado'] ?? 0);
            $saldoCuota = $montoTotalCuota - $pagadoPreviamente;

            if ($saldoCuota <= 0)
                continue; // Ya pagada (safety check)

            if ($dineroRestante >= $saldoCuota) {
                // Pagar TOTALMENTE esta cuota
                $upd = $db->prepare("UPDATE cuotas SET estado = 'pagada', fecha_pago_real = ?, monto_pagado = monto_cuota WHERE id = ?");
                $upd->execute([$fecha, $cuota['id']]);

                $dineroRestante -= $saldoCuota;
                $detallesPago[] = "Cuota #{$cuota['numero_cuota']} (L " . number_format($saldoCuota, 2) . ")";
                $pagosIds[] = $cuota['id']; // Capturar ID para ticket
            } else {
                // Pagar PARCIALMENTE
                $nuevoPagado = $pagadoPreviamente + $dineroRestante;
                $upd = $db->prepare("UPDATE cuotas SET estado = 'parcial', monto_pagado = ? WHERE id = ?");
                $upd->execute([$nuevoPagado, $cuota['id']]);

                $detallesPago[] = "Abono Cuota #{$cuota['numero_cuota']} (L " . number_format($dineroRestante, 2) . ")";
                // Capturar ID para ticket
                $pagosIds[] = $cuota['id'];
                $dineroRestante = 0;
            }
        }

        if (empty($detallesPago)) {
            // Si no se pago nada (ej. no habia deuda), se podria registrar como saldo a favor o error.
            // Pero asumiremos que siempre hay algo pendiente si llegó aquí.
            if ($dineroRestante > 0) {
                // Sobrante real SIN deuda pendiente?
                $msg = "No hay más cuotas pendientes. Sobrante: L " . number_format($dineroRestante, 2);
            } else {
                $msg = "Pago procesado.";
            }
        } else {
            $msg = "Pago aplicado: " . implode(', ', $detallesPago) . ".";
        }
    }

    // --- CALCULAR NUEVO TOTAL RECAUDADO HOY ---
    $hoySql = date('Y-m-d');
    $stmtTotal = $db->query("SELECT IFNULL(SUM(monto_pagado),0) FROM cuotas WHERE DATE(fecha_pago_real) = '$hoySql'");
    $nuevoTotal = $stmtTotal->fetchColumn();

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => isset($pagosIds) ? "Pago aplicado: " . implode(', ', $detallesPago) : $msg,
        'nuevo_total_hoy' => $nuevoTotal,
        'pagos_ids' => $pagosIds ?? []
    ]);

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>