<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $cajeroId = $_SESSION['id_usuario'] ?? 0;
    $agenciaId = $_SESSION['id_agencia'] ?? null;

    if (!$agenciaId)
        throw new Exception("Sesión inválida o sin agencia.");

    $data = json_decode(file_get_contents('php://input'), true);

    $asesorId = $data['asesor_id'];

    // Modificación para aceptar MÚLTIPLES items (Batch)
    // Si viene 'items', procesamos array. Si no, procesamos legacy (single).
    $items = $data['items'] ?? [];

    // Si no hay items pero hay datos legacy, convertimos a items
    if (empty($items)) {
        if (!empty($data['monto_efectivo']) && $data['monto_efectivo'] > 0) {
            $items[] = ['tipo' => 'efectivo', 'monto' => $data['monto_efectivo']];
        }
        if (!empty($data['monto_banco']) && $data['monto_banco'] > 0) {
            $items[] = [
                'tipo' => 'banco',
                'monto' => $data['monto_banco'],
                'banco_id' => $data['banco_id'] ?? null,
                'referencia' => $data['referencia_banco'] ?? ''
            ];
        }
    }

    if (empty($items)) {
        throw new Exception("No hay montos para registrar.");
    }

    $db = getDB();
    $db->beginTransaction();
    $fecha = date('Y-m-d H:i:s');

    // Obtener nombre asesor una vez
    $stmtUser = $db->prepare("SELECT u.username, c.nombre_completo 
                              FROM usuarios u 
                              LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
                              WHERE u.id_usuario = ?");
    $stmtUser->execute([$asesorId]);
    $asesor = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $nombreAsesor = $asesor['nombre_completo'] ?? $asesor['username'] ?? 'Asesor ID ' . $asesorId;

    // Modificación Schema: Usar tablas reales

    // Modificación Flujo: Todo entra a Bóveda/Caja Agencias. NO se tocan bancos aún.

    // Preparar statements
    // Modificación Flujo Final: ROUTING DE FONDOS
    // Efectivo -> Caja Agencia
    // Banco -> Cuentas Bancarias

    // Preparar statements

    // 1. Para Efectivo
    $updCaja = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa + ?, ultima_actualizacion = ? WHERE id_agencia = ?");
    $stmtMov = $db->prepare("INSERT INTO movimientos_internos_agencia 
                             (id_agencia, id_usuario_operador, tipo_movimiento, monto, fecha_movimiento, observaciones)
                             VALUES (?, ?, 'Recaudo Asesor', ?, ?, ?)");

    // 2. Para Banco
    $stmtBan = $db->prepare("INSERT INTO movimientos_bancarios 
                             (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por) 
                             VALUES (?, 'ingreso', ?, ?, ?, ?, ?)");
    $updBanco = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
    $selBanco = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");

    $totalDescontar = 0.0;

    foreach ($items as $item) {
        $tipo = $item['tipo'];
        $monto = floatval($item['monto']);

        if ($monto <= 0)
            continue;

        $totalDescontar += $monto;

        if ($tipo === 'efectivo') {
            // --- FLUJO CAJA ---
            $updCaja->execute([$monto, $fecha, $agenciaId]);
            // Etiqueta estricta [AID:ID] para evitar ambiguedad por nombre
            $obs = "Entrega de $nombreAsesor (Efectivo) [AID:$asesorId]";
            $stmtMov->execute([$agenciaId, $cajeroId, $monto, $fecha, $obs]);

        } elseif ($tipo === 'banco') {
            // --- FLUJO BANCO ---
            $bancoId = $item['banco_id'] ?? null;
            $refBanco = $item['referencia'] ?? '';

            if (!$bancoId)
                throw new Exception("Falta banco en item de depósito.");

            // Leer saldo actual banco
            $selBanco->execute([$bancoId]);
            $saldoAnt = floatval($selBanco->fetchColumn());
            $saldoNuevo = $saldoAnt + $monto;

            // Actualizar Banco
            $updBanco->execute([$saldoNuevo, $bancoId]);

            // Registrar Movimiento Bancario. Etiqueta [AID:ID]
            $descBanco = "Cuadre Asesor $nombreAsesor. Ref: $refBanco [AID:$asesorId]";
            $stmtBan->execute([$bancoId, $monto, $saldoAnt, $saldoNuevo, $descBanco, $cajeroId]);
        }

        // --- PROCESAR CAMBIO DE ESTADO DE PRÉSTAMO (SI APLICA) ---
        if (!empty($item['loan_id']) && !empty($item['loan_estado'])) {
            $loanId = intval($item['loan_id']);
            $estadoActual = $item['loan_estado'];

            $nuevoEstadoLoan = '';
            if ($estadoActual === 'Rechazado en Ruta') {
                $nuevoEstadoLoan = 'Rechazado';
            } elseif ($estadoActual === 'Listo para Entrega') {
                $nuevoEstadoLoan = 'Pendiente de Operaciones'; // Regresa para nueva asignación
            }

            if ($nuevoEstadoLoan !== '') {
                $stmtLoanUpd = $db->prepare("UPDATE prestamos SET estado = ?, updated_at = NOW() WHERE id = ?");
                $stmtLoanUpd->execute([$nuevoEstadoLoan, $loanId]);

                // Opcional: Log o limpieza de campos de ruta si fuera necesario
                // Por ahora el cambio de estado es suficiente para sacarlo del listado de "En Ruta / Deuda"
            }
        }
    }

    // 3. ACTUALIZAR SALDO VIRTUAL ASESOR (LA VERDAD ABSOLUTA)
    if ($totalDescontar > 0) {
        $updUser = $db->prepare("UPDATE usuarios SET saldo_caja_virtual = saldo_caja_virtual - ? WHERE id_usuario = ?");
        $updUser->execute([$totalDescontar, $asesorId]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Cuadre registrado exitosamente.']);

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>