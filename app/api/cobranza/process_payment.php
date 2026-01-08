<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE)
    session_start();

try {
    $userId = $_SESSION['id_usuario'] ?? 1;
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['prestamo_id']) || !isset($data['monto'])) {
        throw new Exception("Datos incompletos");
    }

    $prestamoId = $data['prestamo_id'];
    $montoRecibido = floatval($data['monto']);
    $esCapital = $data['es_capital'] ?? false;
    $fecha = date('Y-m-d H:i:s');

    if ($montoRecibido <= 0) {
        throw new Exception("El monto debe ser mayor a 0.");
    }

    $db->beginTransaction();

    // 1. Obtener Datos Préstamo
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ? FOR UPDATE");
    $stmt->execute([$prestamoId]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo)
        throw new Exception("Préstamo no encontrado");

    $detallesPago = [];

    if ($esCapital) {
        // --- ABONO A CAPITAL DIRECTO ---
        $nuevoSaldo = floatval($prestamo['monto_capital']) - $montoRecibido;
        if ($nuevoSaldo < 0)
            $nuevoSaldo = 0;

        $upd = $db->prepare("UPDATE prestamos SET monto_capital = ? WHERE id = ?");
        $upd->execute([$nuevoSaldo, $prestamoId]);

        $msg = "Abono a Capital registrado. Nuevo saldo: L " . number_format($nuevoSaldo, 2);

    } else {
        // --- PAGO DE CUOTAS ---
        // Obtener cuotas pendientes
        $stmt = $db->prepare("
            SELECT id, monto_cuota, monto_pagado, numero_cuota, estado 
            FROM cuotas 
            WHERE prestamo_id = ? 
            AND estado IN ('pendiente', 'parcial')
            ORDER BY fecha_vencimiento ASC, numero_cuota ASC
        ");
        $stmt->execute([$prestamoId]);
        $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dineroRestante = $montoRecibido;

        foreach ($pendientes as $cuota) {
            if ($dineroRestante <= 0.01)
                break;

            $montoTotalCuota = floatval($cuota['monto_cuota']);
            $pagadoPreviamente = floatval($cuota['monto_pagado'] ?? 0);
            $saldoCuota = $montoTotalCuota - $pagadoPreviamente;

            if ($saldoCuota <= 0)
                continue;

            if ($dineroRestante >= $saldoCuota) {
                // Pago Total de la Cuota
                $montoAPagar = $saldoCuota;

                // Actualizar cuota como pagada
                $upd = $db->prepare("
                    UPDATE cuotas 
                    SET estado = 'pagada', 
                        fecha_pago_real = ?, 
                        monto_pagado = monto_cuota,
                        usuario_cobro_id = ?
                    WHERE id = ?
                ");
                $upd->execute([$fecha, $userId, $cuota['id']]);

                $detallesPago[] = "Cuota #{$cuota['numero_cuota']} pagada (L " . number_format($saldoCuota, 2) . ")";
            } else {
                // Pago Parcial
                $montoAPagar = $dineroRestante;
                $nuevoPagado = $pagadoPreviamente + $dineroRestante;

                // Actualizar cuota como parcial
                $upd = $db->prepare("
                    UPDATE cuotas 
                    SET estado = 'parcial', 
                        monto_pagado = ?,
                        fecha_pago_real = ?,
                        usuario_cobro_id = ?
                    WHERE id = ?
                ");
                $upd->execute([$nuevoPagado, $fecha, $userId, $cuota['id']]);

                $detallesPago[] = "Abono a Cuota #{$cuota['numero_cuota']} (L " . number_format($dineroRestante, 2) . ")";
            }

            $dineroRestante -= $montoAPagar;
        }

        if (empty($detallesPago)) {
            $msg = "No hay cuotas pendientes para este préstamo.";
        } else {
            $msg = "Pago aplicado: " . implode(', ', $detallesPago);
            if ($dineroRestante > 0.01) {
                $msg .= " (Sobrante: L " . number_format($dineroRestante, 2) . ")";
            }
        }
    }

    // Calcular total recaudado hoy desde las cuotas pagadas
    $hoySql = date('Y-m-d');
    $stmtTotal = $db->query("
        SELECT IFNULL(SUM(monto_pagado), 0) 
        FROM cuotas 
        WHERE DATE(fecha_pago_real) = '$hoySql'
        AND estado IN ('pagada', 'parcial')
    ");
    $nuevoTotal = $stmtTotal->fetchColumn();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'nuevo_total_hoy' => $nuevoTotal
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}