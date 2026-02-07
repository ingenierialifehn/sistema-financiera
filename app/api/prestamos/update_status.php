<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/PrestamoHelper.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data)
        $data = $_POST;

    if (empty($data['prestamo_id']) || empty($data['nuevo_estado'])) {
        throw new Exception("ID de préstamo y nuevo estado son obligatorios");
    }

    $prestamoId = intval($data['prestamo_id']);
    $nuevoEstado = $data['nuevo_estado'];
    $validStates = ['Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado', 'Rechazado', 'Rechazado en Ruta', 'Listo para Entrega', 'Activo', 'verificado'];

    if (!in_array($nuevoEstado, $validStates)) {
        throw new Exception("Estado inválido");
    }

    $db = getDB();

    // Start transaction
    $db->beginTransaction();

    try {


        // If changing to 'Listo para Entrega', deduct from cash
        if ($nuevoEstado === 'Listo para Entrega') {
            // Get loan details
            $stmtLoan = $db->prepare("SELECT p.*, c.id_agencia 
                                      FROM prestamos p
                                      JOIN clientes c ON p.id_cliente = c.id
                                      WHERE p.id = ?");
            $stmtLoan->execute([$prestamoId]);
            $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                throw new Exception("Préstamo no encontrado");
            }

            $montoCapital = floatval($loan['monto_capital']);
            $netoEntregar = $montoCapital; // Default for new loans
            $agenciaId = $loan['id_agencia'];

            // --- LOGICA DE REFINANCIAMIENTO (Cálculo de Neto) ---
            if ($loan['tipo_prestamo'] === 'Refinanciamiento' || $loan['tipo_prestamo'] === 'Readecuacion') {
                // 1. Buscar Préstamo Anterior Activo
                // Buscamos el último préstamo activo (o vencido pero no pagado) del cliente, diferente al actual
                $stmtPrev = $db->prepare("SELECT id, monto_capital, 
                                            (SELECT IFNULL(SUM(monto_pagado * (capital_cuota/monto_cuota)), 0) 
                                             FROM cuotas WHERE prestamo_id = p_prev.id AND estado IN ('pagada', 'parcial') AND monto_cuota > 0) as amortizado
                                          FROM prestamos p_prev 
                                          WHERE id_cliente = ? 
                                          AND id != ? 
                                          AND estado = 'Activo' 
                                          ORDER BY id DESC LIMIT 1");
                $stmtPrev->execute([$loan['id_cliente'], $prestamoId]);
                $prevLoan = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                if ($prevLoan) {
                    $saldoAnterior = max(0, floatval($prevLoan['monto_capital']) - floatval($prevLoan['amortizado']));

                    // Cálculo de Neto a Entregar (Lo que sale de caja)
                    // Neto = Nuevo Capital - Saldo a Cancelar
                    $netoEntregar = max(0, $montoCapital - $saldoAnterior);

                    // Actualizamos el campo neto_entregar en la DB para registro
                    $stmtUpdateNeto = $db->prepare("UPDATE prestamos SET neto_entregar = ?, observaciones = CONCAT(IFNULL(observaciones,''), ' [Refinanciamiento: Saldo Anterior L ', ?, ' deducido]') WHERE id = ?");
                    $stmtUpdateNeto->execute([$netoEntregar, number_format($saldoAnterior, 2), $prestamoId]);
                }
            } else {
                // Asegurar que neto_entregar esté actualizado para préstamos normales
                $stmtUpdateNeto = $db->prepare("UPDATE prestamos SET neto_entregar = ? WHERE id = ?");
                $stmtUpdateNeto->execute([$netoEntregar, $prestamoId]);
            }
            // -----------------------------------------------------

            // Get agency cash box balance
            $stmtCaja = $db->prepare("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
            $stmtCaja->execute([$agenciaId]);
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

            if (!$caja) {
                throw new Exception("Caja de agencia no encontrada");
            }

            $saldoActual = floatval($caja['saldo_caja_operativa']);

            if ($saldoActual < $netoEntregar) {
                throw new Exception("Fondos insuficientes en caja. Disponible: L " . number_format($saldoActual, 2) . ", Requerido: L " . number_format($netoEntregar, 2));
            }

            // Deduct from cash
            // SOLO si hay monto a entregar (puede ser 0 si es readecuación total sin desembolso)
            if ($netoEntregar > 0) {
                $stmtUpdate = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa - ? WHERE id_agencia = ?");
                $stmtUpdate->execute([$netoEntregar, $agenciaId]);

                // Log the movement
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $userId = $_SESSION['id_usuario'] ?? 1;

                $stmtLog = $db->prepare("INSERT INTO movimientos_internos_agencia 
                                         (id_agencia, id_usuario_operador, tipo_movimiento, monto, observaciones, fecha_movimiento) 
                                         VALUES (?, ?, 'Caja a Ruta', ?, ?, NOW())");
                $stmtLog->execute([
                    $agenciaId,
                    $userId,
                    $netoEntregar,
                    "Desembolso ($nuevoEstado) préstamo #$prestamoId" . ($loan['tipo_prestamo'] === 'Refinanciamiento' ? ' (Refinanciamiento)' : '')
                ]);
            }

            $assignedOfficerId = $loan['oficial_desembolsos_id'];

            if (!$assignedOfficerId) {
                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $userId = $_SESSION['id_usuario'] ?? 1;
                $assignedOfficerId = $userId;
                $stmtFix = $db->prepare("UPDATE prestamos SET oficial_desembolsos_id = ? WHERE id = ?");
                $stmtFix->execute([$assignedOfficerId, $prestamoId]);
            }

            $stmtRoute = $db->prepare("UPDATE prestamos SET ruta_usuario_id = ?, ruta_fecha_salida = NOW() WHERE id = ?");
            $stmtRoute->execute([$assignedOfficerId, $prestamoId]);
        }

        // If changing to 'Activo', record disbursement date and GENERATE SCHEDULE
        if ($nuevoEstado === 'Activo') {
            // 1. Update Disbursement Date
            $stmtDate = $db->prepare("UPDATE prestamos SET fecha_desembolso = NOW() WHERE id = ?");
            $stmtDate->execute([$prestamoId]);

            // --- LOGICA DE ACTIVACION DE REFINANCIAMIENTO ---
            // Si se activa el nuevo préstamo, el anterior debe cancelarse automágicamente
            $stmtCheckType = $db->prepare("SELECT tipo_prestamo, id_cliente FROM prestamos WHERE id = ?");
            $stmtCheckType->execute([$prestamoId]);
            $currentLoan = $stmtCheckType->fetch(PDO::FETCH_ASSOC);

            if ($currentLoan && ($currentLoan['tipo_prestamo'] === 'Refinanciamiento' || $currentLoan['tipo_prestamo'] === 'Readecuacion')) {
                // Cancelar préstamo anterior activo
                $stmtCancel = $db->prepare("UPDATE prestamos SET estado = 'Refinanciado', observaciones = CONCAT(IFNULL(observaciones, ''), ' [Refinanciado por Préstamo #$prestamoId]') 
                                            WHERE id_cliente = ? AND id != ? AND estado = 'Activo'");
                $stmtCancel->execute([$currentLoan['id_cliente'], $prestamoId]);
            }
            // ----------------------------------------------------

            // 2. Generate Payment Schedule (Cuotas) Starting TODAY
            $stmtLoan = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
            $stmtLoan->execute([$prestamoId]);
            $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

            if ($loan) {
                // Clear any previous schedule
                $stmtDelete = $db->prepare("DELETE FROM cuotas WHERE prestamo_id = ?");
                $stmtDelete->execute([$prestamoId]);

                // Generate new schedule starting today
                $montoCuota = floatval($loan['valor_cuota']);
                $periodoMeses = intval($loan['plazo_meses']);
                $fechaInicio = date('Y-m-d');
                $diaPago = intval(date('d'));
                $modalidad = strtolower($loan['modalidad']);

                PrestamoHelper::generateCuotasModalidad(
                    $db,
                    $prestamoId,
                    $montoCuota,
                    $periodoMeses,
                    $fechaInicio,
                    $diaPago,
                    $modalidad
                );
            }
        }

        // LOGICA DE RECHAZO (BLINDAJE DE FONDOS)
        if ($nuevoEstado === 'Rechazado' || $nuevoEstado === 'Rechazado en Ruta') {
            // Obtener estado anterior para saber si el dinero ya salió
            $stmtPrev = $db->prepare("SELECT estado, neto_entregar, monto_capital, id_agencia, oficial_desembolsos_id FROM prestamos p JOIN clientes c ON p.id_cliente = c.id WHERE p.id = ?");
            $stmtPrev->execute([$prestamoId]);
            $prevLoan = $stmtPrev->fetch(PDO::FETCH_ASSOC);

            // Si venía de un estado donde el dinero ya estaba fuera ('Listo para Entrega' o 'Rechazado en Ruta')
            // IMPORTANTE: Evitar duplicar el reintegro si ya fue 'Rechazado en Ruta' y pasa a 'Rechazado' final sin haber reingresado dinero.
            // MEJORA: Solo si el dinero salió ('Listo para Entrega') y ahora se rechaza.
            // Si estaba en 'Rechazado en Ruta', se supone que el dinero lo tiene el asesor.
            // Si pasamos a 'Rechazado' FINAL, asumimos que el dinero volvió.

            if ($prevLoan && ($prevLoan['estado'] === 'Listo para Entrega' || $prevLoan['estado'] === 'Rechazado en Ruta')) {
                $montoReintegro = $prevLoan['neto_entregar'] ?? $prevLoan['monto_capital'];
                $agenciaIdReintegro = $prevLoan['id_agencia'];
                $oficialResponsable = $prevLoan['oficial_desembolsos_id'];

                // Registrar Reintegro a Caja
                $stmtUpdateCaja = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa + ? WHERE id_agencia = ?");
                $stmtUpdateCaja->execute([$montoReintegro, $agenciaIdReintegro]);

                // Registrar Movimiento de Reintegro
                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $usuarioOperador = $_SESSION['id_usuario'] ?? 1;

                $stmtLogReintegro = $db->prepare("INSERT INTO movimientos_internos_agencia 
                                        (id_agencia, id_usuario_operador, tipo_movimiento, monto, observaciones, fecha_movimiento) 
                                        VALUES (?, ?, 'Ingreso por Rechazo', ?, ?, NOW())");
                $stmtLogReintegro->execute([
                    $agenciaIdReintegro,
                    $usuarioOperador,
                    $montoReintegro,
                    "Devolución Automática por Préstamo Rechazado #$prestamoId (Responsable: $oficialResponsable)"
                ]);
            }
        }

        // Update loan status
        $stmt = $db->prepare("UPDATE prestamos SET estado = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevoEstado, $prestamoId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Estado actualizado a '$nuevoEstado'" . ($nuevoEstado === 'Listo para Entrega' ? '. Fondos reservados en caja.' : '')
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>