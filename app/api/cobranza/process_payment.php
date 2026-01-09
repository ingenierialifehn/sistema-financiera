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

    $paidIds = []; // Initialize ID capture
    $db->beginTransaction();

    // 1. Obtener Datos Préstamo
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ? FOR UPDATE");
    $stmt->execute([$prestamoId]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo)
        throw new Exception("Préstamo no encontrado");

    $detallesPago = [];

    if ($data['es_cancelacion'] ?? false) {
        // --- CANCELACIÓN TOTAL ---
        // 1. Recalcular la meta de cuotas a cobrar intereses (Vencidas vs 1 Mes Mínimo)
        $modNorm = ucfirst(strtolower(trim($prestamo['modalidad'])));
        $cuotasPorMes = 1;

        if ($modNorm === 'Diario') {
            $cuotasPorMes = 20;
        } elseif ($modNorm === 'Semanal') {
            $cuotasPorMes = 4;
        } elseif ($modNorm === 'Catorcenal') {
            $cuotasPorMes = 2;
        } elseif ($modNorm === 'Mensual') {
            $cuotasPorMes = 1;
        }

        // Obtener todas las pendientes para procesar
        $stmtPend = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = ? AND estado != 'pagada' ORDER BY numero_cuota ASC");
        $stmtPend->execute([$prestamoId]);
        $pendientes = $stmtPend->fetchAll(PDO::FETCH_ASSOC);

        $hoy = date('Y-m-d');
        $vencidasCount = 0;
        foreach ($pendientes as $p) {
            if ($p['fecha_vencimiento'] <= $hoy)
                $vencidasCount++;
        }

        // Meta de Cobro de Intereses
        $targetCount = max($vencidasCount, $cuotasPorMes);

        // Procesar distribución
        $dineroDisponible = $montoRecibido;
        // $idx = 0; // Removed index based tracking

        foreach ($pendientes as $cuota) {
            // $esMetaInteres = ($idx < $targetCount);
            // CORRECCIÓN: Usar Numero Cuota Lógico para aguantar filas divididas
            $esMetaInteres = (intval($cuota['numero_cuota']) <= $targetCount);

            // Si está dentro de la meta, se cobra full. Si no, solo capital.
            // OJO: Si la cuota ya tenía pagos parciales, hay que respetar el saldo restante.
            // Para simplificar cancelación, asumimos que liquidamos el SALDO de la cuota.

            $saldoCapital = floatval($cuota['capital_cuota']); // Asumimos estructura limpia o recalculamos
            // Si es parcial, es complejo saber cuánto capital queda presisamente sin un ledger detallado.
            // Asumiremos: Si 'parcial' y saldo < monto, liquidamos el saldo.
            // Pero debemos AJUSTAR los rubros si perdonamos intereses.

            if ($esMetaInteres) {
                // COBRAR FULL (Normal) (Incluye Interés)
                $montoCuotaActual = floatval($cuota['monto_cuota']);
                $pagadoPrev = floatval($cuota['monto_pagado'] ?? 0);
                $saldo = $montoCuotaActual - $pagadoPrev;

                $pagoAplicar = min($dineroDisponible, $saldo);
                // En cancelación teoricamente dineroDisponible == Total Requerido.

                $upd = $db->prepare("UPDATE cuotas SET estado='pagada', monto_pagado=monto_cuota, fecha_pago_real=?, usuario_cobro_id=? WHERE id=?");
                $upd->execute([$fecha, $userId, $cuota['id']]);

                $dineroDisponible -= $saldo;

            } else {
                // SOLO CAPITAL (Condonar Interés)
                // 1. Ajustar la cuota para que SOLO tenga capital
                // Pero si ya tiene pagos parciales, cuidado.
                // Asumiremos cuotas 'futuras' limpias (sin pagos parciales).

                $nuevoMonto = floatval($cuota['capital_cuota']);

                $upd = $db->prepare("
                    UPDATE cuotas SET 
                        monto_cuota = capital_cuota,
                        interes_cuota = 0, gastos_cuota = 0, comision_cuota = 0,
                        monto_pagado = capital_cuota,
                        estado = 'pagada',
                        fecha_pago_real = ?,
                        usuario_cobro_id = ?
                    WHERE id = ?
                ");
                $upd->execute([$fecha, $userId, $cuota['id']]);

                $dineroDisponible -= $nuevoMonto;
            }
            $paidIds[] = $cuota['id'];
            // $idx++;
        }

        // Cerrar Préstamo
        $closeLoan = $db->prepare("UPDATE prestamos SET estado = 'Finalizado' WHERE id = ?");
        $closeLoan->execute([$prestamoId]);

        $msg = "Préstamo Cancelado Exitosamente. Se liquidó la deuda total.";

    } elseif ($esCapital) {
        // --- ABONO A CAPITAL DIRECTO ---
        // Regla: Solo clientes al día (sin mora) pueden abonar a capital.
        // Regla: Se registra como una cuota extra pagada, 100% capital, 0% interés.

        // 1. Validar si tiene cuotas en mora
        $stmtMora = $db->prepare("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = ? AND estado = 'en_mora'");
        $stmtMora->execute([$prestamoId]);
        if ($stmtMora->fetchColumn() > 0) {
            throw new Exception("No es posible realizar abonos a capital: El préstamo tiene cuotas en mora. Debe ponerse al día primero.");
        }

        // 2. Registrar el abono como una cuota especial
        // Usaremos numero_cuota = 0 o un identificador especial si fuera string, pero es int.
        // Opción: Usar el siguiente número disponible + 1000 para diferenciarlo, o simplemente agregarlo al historial sin afectar la numeración normal de las pendientes.
        // Vamos a usar numero_cuota = 0 para denotar "Abono Extra".

        $ins = $db->prepare("
            INSERT INTO cuotas (
                prestamo_id, numero_cuota, fecha_vencimiento, 
                monto_cuota, monto_pagado, 
                capital_cuota, interes_cuota, gastos_cuota, comision_cuota,
                estado, fecha_pago_real, usuario_cobro_id
            ) VALUES (
                ?, 0, CURDATE(),
                ?, ?,
                ?, 0, 0, 0,
                'pagada', ?, ?
            )
        ");

        $ins->execute([
            $prestamoId,
            $montoRecibido,
            $montoRecibido, // Pagado
            $montoRecibido, // Todo a Capital
            $fecha,
            $userId
        ]);
        $paidIds[] = $db->lastInsertId();

        $msg = "Abono a Capital registrado exitosamente como cuota extraordinaria (L " . number_format($montoRecibido, 2) . ")";

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
                $paidIds[] = $cuota['id'];

                $detallesPago[] = "Cuota #{$cuota['numero_cuota']} pagada (L " . number_format($saldoCuota, 2) . ")";
            } else {
                // Pago Parcial - DIVISIÓN DE CUOTA
                // El cliente paga una parte ($dineroRestante) de la cuota actual.
                // 1. La cuota actual se convierte en el registro del PAGO realizado (se reduce su monto y se marca pagada).
                // 2. Se crea una NUEVA cuota por el saldo pendiente.

                $montoAPagar = $dineroRestante;

                // Obtener datos completos de la cuota para clonar/dividir
                $stmtDetalle = $db->prepare("SELECT * FROM cuotas WHERE id = ?");
                $stmtDetalle->execute([$cuota['id']]);
                $cuotaFull = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

                // Calcular valores para la parte PAGADA (Proporcional)
                // Nota: Usamos el saldo pendiente como base, no el monto original total si ya tuviera pagos (aunque idealmente no debería tenerlos con este sistema)
                $saldoTotal = floatval($cuotaFull['monto_cuota']) - floatval($cuotaFull['monto_pagado'] ?? 0);

                // Evitamos división por cero (no debería pasar por validaciones anteriores)
                if ($saldoTotal <= 0)
                    continue;

                $porcionPagada = $montoAPagar;
                $porcionPendiente = $saldoTotal - $porcionPagada;

                // Factor para dividir los rubros (capital, interés, etc)
                // Si la cuota era de 500 y saldo 500, y pago 250 -> factor 0.5
                // Si la cuota era de 500 (pagado 0) -> factor = 250 / 500 = 0.5
                $ratio = $porcionPagada / ($saldoTotal + floatval($cuotaFull['monto_pagado'] ?? 0));
                // Corrección: El ratio debe ser sobre el monto ACTUAL de este registro.
                // Si este registro ya es un "remanente" de 300, y pago 100. Ratio = 100/300.
                $ratio = $porcionPagada / floatval($cuotaFull['monto_cuota']);

                $capPagado = round(floatval($cuotaFull['capital_cuota']) * $ratio, 2);
                $intPagado = round(floatval($cuotaFull['interes_cuota']) * $ratio, 2);
                $gastosPagado = round(floatval($cuotaFull['gastos_cuota']) * $ratio, 2);
                $comisionPagado = round(floatval($cuotaFull['comision_cuota']) * $ratio, 2);

                // Ajuste por redondeo para la parte pendiente (Nuevo Registro)
                $capPendiente = floatval($cuotaFull['capital_cuota']) - $capPagado;
                $intPendiente = floatval($cuotaFull['interes_cuota']) - $intPagado;
                $gastosPendiente = floatval($cuotaFull['gastos_cuota']) - $gastosPagado;
                $comisionPendiente = floatval($cuotaFull['comision_cuota']) - $comisionPagado;

                // 1. MODIFICAR CUOTA ACTUAL (Se convierte en el PAĜO)
                $upd = $db->prepare("
                    UPDATE cuotas 
                    SET 
                        monto_cuota = ?,          -- Se reduce al monto que se pagó efectivamente
                        monto_pagado = ?,         -- Se marca como totalmente pagada (por ese monto reducido)
                        capital_cuota = ?,
                        interes_cuota = ?,
                        gastos_cuota = ?,
                        comision_cuota = ?,
                        estado = 'pagada',        -- Se cierra este registro
                        fecha_pago_real = ?,
                        usuario_cobro_id = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $porcionPagada,
                    $porcionPagada,
                    $capPagado,
                    $intPagado,
                    $gastosPagado,
                    $comisionPagado,
                    $fecha,
                    $userId,
                    $cuota['id']
                ]);
                $paidIds[] = $cuota['id'];

                // 2. INSERTAR NUEVA CUOTA (El SALDO pendiente)
                $ins = $db->prepare("
                    INSERT INTO cuotas (
                        prestamo_id, numero_cuota, fecha_vencimiento, 
                        monto_cuota, monto_pagado, estado,
                        capital_cuota, interes_cuota, gastos_cuota, comision_cuota
                    ) VALUES (
                        ?, ?, ?, 
                        ?, 0, 'pendiente',
                        ?, ?, ?, ?
                    )
                ");
                $ins->execute([
                    $cuotaFull['prestamo_id'],
                    $cuotaFull['numero_cuota'],
                    $cuotaFull['fecha_vencimiento'],
                    $porcionPendiente,
                    $capPendiente,
                    $intPendiente,
                    $gastosPendiente,
                    $comisionPendiente
                ]);

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

    // Get User Context for correct filtering
    // Get User Context for correct filtering
    // Use Auth helper to get agency info correctly (since id_agencia is in colaboradores table, not directly in usuarios)
    $uData = Auth::checkSession();

    $sqlTotal = "
        SELECT IFNULL(SUM(c.monto_pagado), 0) 
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        JOIN clientes cl ON p.id_cliente = cl.id
        WHERE DATE(c.fecha_pago_real) = '$hoySql'
        AND c.estado IN ('pagada', 'parcial')
    ";

    if ($uData && !empty($uData['id_agencia']) && stripos($uData['rol_nombre'] ?? '', 'Admin') === false) {
        $sqlTotal .= " AND cl.id_agencia = " . intval($uData['id_agencia']);
    }

    $stmtTotal = $db->query($sqlTotal);
    $nuevoTotal = $stmtTotal->fetchColumn();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'nuevo_total_hoy' => $nuevoTotal,
        'pagos_ids' => $paidIds
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}