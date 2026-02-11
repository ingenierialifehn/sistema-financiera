<?php
/**
 * API para procesar el cuadre de asesor y bloquear cobros
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();

header('Content-Type: application/json');

$db = getDB();
$user = AuthMiddleware::getCurrentUser();
$idUsuario = $user['id_usuario'];
$idAgencia = $user['id_agencia'] ?? null;

if (!$idAgencia) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no tiene agencia asignada'
    ]);
    exit;
}

try {
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);

    $idAsesor = $data['id_asesor'] ?? null;
    $montoEfectivo = floatval($data['monto_efectivo'] ?? 0);
    $montoBanco = floatval($data['monto_banco'] ?? 0);
    $bancoId = $data['banco_id'] ?? null;
    $referenciaBanco = $data['referencia_banco'] ?? null;
    $observaciones = $data['observaciones'] ?? null;

    if (!$idAsesor) {
        throw new Exception('Debe seleccionar un asesor');
    }

    $fechaHoy = date('Y-m-d'); // Definir fecha al inicio para usarla en consultas previas

    $montoEntregadoActual = $montoEfectivo + $montoBanco;

    // Calcular lo entregado PREVIAMENTE en el día (Histórico)
    // 1. Efectivo (movimientos_internos_agencia)
    $searchTag = "%[AID:$idAsesor]%";
    $searchName = "%" . date('Y-m-d') . "%"; // Placeholder simple, búsqueda mejorada abajo...

    // Obtener nombre asesor para búsqueda like
    $stmtN = $db->prepare("SELECT COALESCE(c.nombre_completo, u.username) FROM usuarios u LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador WHERE u.id_usuario = ?");
    $stmtN->execute([$idAsesor]);
    $nombreAsesor = $stmtN->fetchColumn();

    $searchName = "%" . $nombreAsesor . "%";

    $sqlEfvo = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_internos_agencia 
                WHERE id_agencia = ? 
                AND tipo_movimiento = 'Recaudo Asesor'
                AND (observaciones LIKE ? OR observaciones LIKE ?)
                AND DATE(fecha_movimiento) = ?";
    $stmtE = $db->prepare($sqlEfvo);
    $stmtE->execute([$idAgencia, $searchTag, $searchName, $fechaHoy]);
    $entregadoEfvoPrevio = floatval($stmtE->fetchColumn());

    $sqlBanco = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_bancarios 
                 WHERE tipo_transaccion = 'ingreso' 
                 AND (descripcion LIKE ? OR descripcion LIKE ?)
                 AND DATE(fecha_hora) = ?";
    $stmtB = $db->prepare($sqlBanco);
    $stmtB->execute([$searchTag, $searchName, $fechaHoy]);
    $entregadoBancoPrevio = floatval($stmtB->fetchColumn());

    $totalEntregadoPrevio = $entregadoEfvoPrevio + $entregadoBancoPrevio;
    $montoEntregadoTotal = $totalEntregadoPrevio + $montoEntregadoActual;

    // Verificar si hizo desembolsos hoy
    $sqlDesem = "SELECT COUNT(*) FROM prestamos 
                 WHERE oficial_desembolsos_id = ? 
                 AND DATE(fecha_desembolso) = ? 
                 AND estado = 'Activo'";
    $stmtDesem = $db->prepare($sqlDesem);
    $stmtDesem->execute([$idAsesor, $fechaHoy]);
    $hasDesembolsos = $stmtDesem->fetchColumn() > 0;

    if ($montoEntregadoTotal <= 0 && $montoEntregadoActual <= 0 && !$hasDesembolsos) {
        throw new Exception('No se han registrado entregas ni desembolsos hoy. Debe haber actividad para cuadrar.');
    }

    // Verificar si ya existe un cuadre para este asesor hoy
    $sqlCheck = "SELECT id FROM cuadres_asesores 
                 WHERE id_asesor = ? AND fecha_cuadre = ? AND id_agencia = ?";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->execute([$idAsesor, $fechaHoy, $idAgencia]);

    if ($stmtCheck->fetch()) {
        throw new Exception('Este asesor ya tiene un cuadre registrado hoy');
    }

    // Calcular el monto recaudado del día
    $sqlRecaudado = "SELECT IFNULL(SUM(c.monto_pagado), 0) as total
                     FROM cuotas c
                     JOIN prestamos p ON c.prestamo_id = p.id
                     JOIN clientes cl ON p.id_cliente = cl.id
                     WHERE DATE(c.fecha_pago_real) = ?
                     AND c.usuario_cobro_id = ?
                     AND cl.id_agencia = ?
                     AND c.estado = 'pagada'";
    $stmtRecaudado = $db->prepare($sqlRecaudado);
    $stmtRecaudado->execute([$fechaHoy, $idAsesor, $idAgencia]);
    $montoRecaudado = floatval($stmtRecaudado->fetchColumn());

    // Iniciar transacción
    $db->beginTransaction();

    // Registrar el cuadre con el TOTAL (Previo + Actual) para referencia histórica completa
    // Nota: Guardamos monto_entregado como el TOTAL del día para facilitar reportes
    $sqlInsert = "INSERT INTO cuadres_asesores 
                  (id_asesor, id_agencia, fecha_cuadre, monto_recaudado, monto_entregado, 
                   monto_efectivo, monto_banco, banco_id, referencia_banco, bloqueado, 
                   observaciones, id_usuario_registro)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->execute([
        $idAsesor,
        $idAgencia,
        $fechaHoy,
        $montoRecaudado,
        $montoEntregadoTotal, // Usamos el total acumulado
        $montoEfectivo, // Guardamos solo lo 'extra' de este cierre en columna específica
        $montoBanco,    // Guardamos solo lo 'extra' de este cierre en columna específica
        $bancoId,
        $referenciaBanco,
        $observaciones . " [Previo: $totalEntregadoPrevio | Cierre: $montoEntregadoActual]",
        $idUsuario
    ]);

    $idCuadre = $db->lastInsertId();

    // --- LÓGICA CONTABLE CONSOLIDADA ---

    $totalEntregadoEnEsteCierre = 0.0;

    // 1. PROCESAR EFECTIVO -> CAJA OPERATIVA AGENCIA
    if ($montoEfectivo > 0) {
        $totalEntregadoEnEsteCierre += $montoEfectivo;

        // A. Insertar Movimiento Interno
        $sqlMov = "INSERT INTO movimientos_internos_agencia
            (id_agencia, tipo_movimiento, monto, observaciones, fecha_movimiento, id_usuario_operador)
            VALUES (?, 'Recaudo Asesor', ?, ?, NOW(), ?)";
        $desc = "Recaudo Cierre Asesor " . ($nombreAsesor ?? '') . " [AID:$idAsesor]";

        $stmtMov = $db->prepare($sqlMov);
        $stmtMov->execute([$idAgencia, $montoEfectivo, $desc, $idUsuario]);

        // B. Sumar a Caja Operativa de la Agencia
        $stmtUpdCaja = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa + ?, ultima_actualizacion = NOW() WHERE id_agencia = ?");
        $stmtUpdCaja->execute([$montoEfectivo, $idAgencia]);
    }

    // 2. PROCESAR BANCO -> CUENTAS BANCARIAS
    if ($montoBanco > 0 && $bancoId) {
        $totalEntregadoEnEsteCierre += $montoBanco;

        // A. Consultar Saldo Anterior
        $stmtSelBanco = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");
        $stmtSelBanco->execute([$bancoId]);
        $saldoAnt = floatval($stmtSelBanco->fetchColumn());
        $saldoNuevo = $saldoAnt + $montoBanco;

        // B. Actualizar Banco
        $stmtUpdBanco = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
        $stmtUpdBanco->execute([$saldoNuevo, $bancoId]);

        // C. Registrar Movimiento Bancario
        $stmtMovBan = $db->prepare("INSERT INTO movimientos_bancarios 
                             (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por) 
                             VALUES (?, 'ingreso', ?, ?, ?, ?, ?)");

        $descBanco = "Cuadre Asesor " . ($nombreAsesor ?? '') . ". Ref: $referenciaBanco [AID:$idAsesor]";
        $stmtMovBan->execute([$bancoId, $montoBanco, $saldoAnt, $saldoNuevo, $descBanco, $idUsuario]);
    }

    // Obtener detalles de transacciones para el recibo
    $sqlTransacciones = "SELECT c.id, c.monto_pagado, IFNULL(c.capital_cuota, 0) as capital_pagado, (c.monto_pagado - IFNULL(c.capital_cuota, 0)) as interes_pagado, cl.nombre_completo, DATE_FORMAT(c.fecha_pago_real, '%H:%i') as hora
                         FROM cuotas c
                         JOIN prestamos p ON c.prestamo_id = p.id
                         JOIN clientes cl ON p.id_cliente = cl.id
                         WHERE DATE(c.fecha_pago_real) = ?
                         AND c.usuario_cobro_id = ?
                         AND cl.id_agencia = ?
                         AND c.estado = 'pagada'
                         ORDER BY c.fecha_pago_real ASC";
    $stmtTrans = $db->prepare($sqlTransacciones);
    $stmtTrans->execute([$fechaHoy, $idAsesor, $idAgencia]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // 3. ACTUALIZAR SALDO VIRTUAL DEL ASESOR (LA VERDAD ABSOLUTA)
    // Descontamos lo que acaba de entregar para que su deuda baje
    if ($totalEntregadoEnEsteCierre > 0) {
        $updUser = $db->prepare("UPDATE usuarios SET saldo_caja_virtual = saldo_caja_virtual - ? WHERE id_usuario = ?");
        $updUser->execute([$totalEntregadoEnEsteCierre, $idAsesor]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Cuadre registrado exitosamente. El asesor ha sido bloqueado para cobros hoy.',
        'data' => [
            'id_cuadre' => $idCuadre,
            'monto_recaudado' => $montoRecaudado,
            'monto_entregado' => $montoEntregadoTotal,
            'diferencia' => $montoRecaudado - $montoEntregadoTotal,
            'transacciones' => $transacciones,
            'asesor_nombre' => $nombreAsesor ?? 'Asesor',
            'fecha' => date('d/m/Y H:i:s'),
            'total_efectivo_dia' => $entregadoEfvoPrevio + $montoEfectivo,
            'total_banco_dia' => $entregadoBancoPrevio + $montoBanco,
            'detalle_bancos' => (function () use ($db, $searchTag, $searchName, $fechaHoy) {
                $stmt = $db->prepare("
                    SELECT b.nombre_banco, SUM(mb.monto) as total
                    FROM movimientos_bancarios mb
                    JOIN bancos b ON mb.banco_id = b.id
                    WHERE mb.tipo_transaccion = 'ingreso'
                    AND (mb.descripcion LIKE ? OR mb.descripcion LIKE ?)
                    AND DATE(mb.fecha_hora) = ?
                    GROUP BY b.nombre_banco
                ");
                $stmt->execute([$searchTag, $searchName, $fechaHoy]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            })(),
            'detectar_desembolsos' => true,
            'desembolsos_entregados' => (function () use ($db, $idAsesor, $fechaHoy) {
                $stmt = $db->prepare("
                    SELECT cl.nombre_completo, p.monto_capital
                    FROM prestamos p
                    JOIN clientes cl ON p.id_cliente = cl.id
                    WHERE p.oficial_desembolsos_id = ?
                    AND DATE(p.fecha_desembolso) = ?
                    AND p.estado = 'Activo'
                ");
                $stmt->execute([$idAsesor, $fechaHoy]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            })()
        ]
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Error en cuadre de asesor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
