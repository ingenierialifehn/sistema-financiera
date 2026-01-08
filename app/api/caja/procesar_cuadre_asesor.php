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
    $stmtUser = $db->prepare("SELECT username, nombre, apellido FROM usuarios WHERE id_usuario = ?");
    $stmtUser->execute([$asesorId]);
    $asesor = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $nombreAsesor = $asesor ? ($asesor['nombre'] . ' ' . $asesor['apellido']) : 'Asesor ID ' . $asesorId;

    // Preparar statements
    $updCaja = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa + ?, ultima_actualizacion = ? WHERE id_agencia = ?");
    $stmtMov = $db->prepare("INSERT INTO movimientos_caja (id_caja, id_usuario, tipo_movimiento, monto, fecha_movimiento, descripcion, categoria)
                   VALUES ((SELECT id FROM cajas_agencias WHERE id_agencia = ? LIMIT 1), ?, 'ingreso', ?, ?, ?, 'Cuadre Asesor')");

    $stmtBan = $db->prepare("INSERT INTO movimientos_bancarios (id_cuenta_bancaria, fecha_movimiento, tipo_transaccion, monto, referencia, descripcion, id_usuario_responsable)
                   VALUES (?, ?, 'deposito', ?, ?, ?, ?)");

    foreach ($items as $item) {
        $tipo = $item['tipo'];
        $monto = floatval($item['monto']);

        if ($monto <= 0)
            continue;

        if ($tipo === 'efectivo') {
            // 1. PROCESAR EFECTIVO
            $updCaja->execute([$monto, $fecha, $agenciaId]);
            $concepto = "Entrega de $nombreAsesor (Recaudo)";
            $stmtMov->execute([$agenciaId, $cajeroId, $monto, $fecha, $concepto]);

        } elseif ($tipo === 'banco') {
            // 2. PROCESAR BANCO
            $bancoId = $item['banco_id'] ?? null;
            $refBanco = $item['referencia'] ?? '';

            if (!$bancoId)
                throw new Exception("Falta banco en un item de depósito.");

            $conceptoBanco = "Depósito Recaudo - $nombreAsesor";
            $stmtBan->execute([$bancoId, $fecha, $monto, $refBanco, $conceptoBanco, $cajeroId]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Cuadre registrado exitosamente.']);

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>