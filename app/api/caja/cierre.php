<?php
/**
 * API: Cierre de caja
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

// Verificar permisos
if (!Auth::hasPermission('caja.crear') && !Auth::hasPermission('caja.editar') && !Auth::hasPermission('caja')) {
    Response::forbidden('No tiene permisos para cerrar caja');
}

$db = getDB();
$user = Auth::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $idControl = $data['id_control'] ?? null;
    $saldoCierreSistema = $data['saldo_cierre_sistema'] ?? null;
    $saldoCierreFisico = $data['saldo_cierre_fisico'] ?? null;
    $diferenciaCierre = $data['diferencia_cierre'] ?? 0;
    $observaciones = $data['observaciones'] ?? '';
    $idUsuario = $user['id_usuario'];

    // Validaciones
    if (!$idControl) {
        Response::error('ID de control es requerido', 400);
    }

    if ($saldoCierreSistema === null || $saldoCierreFisico === null) {
        Response::error('Saldos de cierre son requeridos', 400);
    }

    // Verificar que la caja esté abierta
    $stmt = $db->prepare("
        SELECT id_control, estado 
        FROM control_caja_diaria 
        WHERE id_control = ?
    ");
    $stmt->execute([$idControl]);
    $caja = $stmt->fetch();

    if (!$caja) {
        Response::error('La caja no existe', 400);
    }

    $estadoActual = isset($caja['estado']) ? strtolower($caja['estado']) : '';
    if ($estadoActual !== 'abierto' && $estadoActual !== 'abierta') {
        Response::error('La caja no está abierta (Estado actual: ' . ($caja['estado'] ?? 'Desconocido') . ')', 400);
    }



    // Validar que la caja esté en cero (todo devuelto a bóveda)
    // 1. Obtener agencia del usuario
    // Ya tenemos $user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    // 2. Consultar saldo en cajas_agencias
    $stmtSaldo = $db->prepare("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
    $stmtSaldo->execute([$idAgencia]);
    $saldoAgencia = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if ($saldoAgencia) {
        $saldoActual = floatval($saldoAgencia['saldo_caja_operativa']);
        if ($saldoActual > 0.01) {
            Response::error("No se puede cerrar caja con fondos (L. " . number_format($saldoActual, 2) . "). Debe devolver todo el dinero a Bóveda.", 400);
        }
    }

    // 2.1 Verificar que todos los asesores hayan entregado (Saldo Virtual = 0)
    $stmtAsesoresPendientes = $db->prepare("
        SELECT c.nombre_completo, u.saldo_caja_virtual
        FROM usuarios u
        JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
        WHERE c.id_agencia = ? 
        AND u.saldo_caja_virtual > 0.01
        AND u.estado = 'Activo'
    ");
    $stmtAsesoresPendientes->execute([$idAgencia]);
    $asesoresPendientes = $stmtAsesoresPendientes->fetchAll(PDO::FETCH_ASSOC);

    if (count($asesoresPendientes) > 0) {
        $nombres = array_map(function ($a) {
            return $a['nombre_completo'] . ' (L. ' . number_format($a['saldo_caja_virtual'], 2) . ')';
        }, $asesoresPendientes);

        Response::error(
            "No se puede cerrar la agencia. Los siguientes asesores aún tienen dinero por entregar:\n- " .
            implode("\n- ", $nombres),
            400
        );
    }

    // 3. Verificar que no haya préstamos en ruta de desembolso
    $stmtPrestamosRuta = $db->prepare("
        SELECT COUNT(*) as total 
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        WHERE c.id_agencia = ? AND p.estado = 'Listo para Entrega'
    ");
    $stmtPrestamosRuta->execute([$idAgencia]);
    $prestamosEnRuta = $stmtPrestamosRuta->fetch(PDO::FETCH_ASSOC);

    if ($prestamosEnRuta && $prestamosEnRuta['total'] > 0) {
        Response::error(
            "No se puede cerrar caja. Hay " . $prestamosEnRuta['total'] . " préstamo(s) en ruta de desembolso. " .
            "Debe completar o cancelar los desembolsos pendientes antes de cerrar.",
            400
        );
    }

    // Actualizar cierre
    $stmt = $db->prepare("
        UPDATE control_caja_diaria 
        SET 
            id_usuario_cierre = ?,
            hora_cierre = NOW(),
            saldo_cierre_sistema = ?,
            saldo_cierre_fisico = ?,
            diferencia_cierre = ?,
            observaciones = CONCAT(IFNULL(observaciones, ''), '\n--- CIERRE ---\n', ?),
            estado = 'Cerrado'
        WHERE id_control = ?
    ");

    $stmt->execute([
        $idUsuario,
        $saldoCierreSistema,
        $saldoCierreFisico,
        $diferenciaCierre,
        $observaciones,
        $idControl
    ]);

    // --- DATOS PARA REPORTE DE CIERRE (IMPRESIÓN) ---
    $fechaHoy = date('Y-m-d');

    // 1. Obtener Transacciones del Día (Toda la agencia)
    $stmtTrans = $db->prepare("
        SELECT 
            cl.nombre_completo as cliente,
            c.numero_cuota,
            c.monto_pagado,
            LEAST(c.monto_pagado, c.capital_cuota) as capital_pagado,
            (c.monto_pagado - LEAST(c.monto_pagado, c.capital_cuota)) as interes_pagado,
            DATE_FORMAT(c.fecha_pago_real, '%H:%i') as hora,
            COALESCE(col_cobro.nombre_completo, u_cobro.username) as cobrador
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        JOIN clientes cl ON p.id_cliente = cl.id
        LEFT JOIN usuarios u_cobro ON c.usuario_cobro_id = u_cobro.id_usuario
        LEFT JOIN colaboradores col_cobro ON u_cobro.id_colaborador = col_cobro.id_colaborador
        WHERE cl.id_agencia = ? 
        AND DATE(c.fecha_pago_real) = ?
        AND c.estado = 'pagada'
        ORDER BY c.fecha_pago_real ASC
    ");
    $stmtTrans->execute([$idAgencia, $fechaHoy]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener Saldo en Bóveda
    // Nota: El saldo de bóveda no cambia con el cierre de caja (la caja entrega a bóveda antes).
    $stmtBoveda = $db->prepare("SELECT saldo_efectivo FROM cajas_agencias WHERE id_agencia = ?");
    $stmtBoveda->execute([$idAgencia]);
    $saldoBoveda = $stmtBoveda->fetchColumn();

    // 3. Obtener Supervisor
    // 3. Obtener Supervisor (Simplificado para evitar error de columna inexistente)
    $nombreSupervisor = 'Sin supervisor asignado'; // Asumimos default si no hay columna supervisor_id
    /*
    $stmtSup = $db->prepare("
        SELECT COALESCE(col.nombre_completo, 'Sin supervisor asignado') 
        FROM agencias a
        LEFT JOIN usuarios u ON a.supervisor_id = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        WHERE a.id_agencia = ?
    ");
    $stmtSup->execute([$idAgencia]);
    $nombreSupervisor = $stmtSup->fetchColumn() ?: 'Sin supervisor asignado';
    */

    // 4. Obtener Nombre Oficial (Quien cierra)
    $stmtOficial = $db->prepare("SELECT COALESCE(c.nombre_completo, u.username) FROM usuarios u LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador WHERE u.id_usuario = ?");
    $stmtOficial->execute([$idUsuario]);
    $nombreOficial = $stmtOficial->fetchColumn();

    // 5. Nombre Agencia
    $stmtAg = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
    $stmtAg->execute([$idAgencia]);
    $nombreAgencia = $stmtAg->fetchColumn();

    Response::success([
        'message' => 'Caja cerrada exitosamente',
        'diferencia' => $diferenciaCierre,
        'reporte_data' => [
            'transacciones' => $transacciones,
            'saldo_boveda' => $saldoBoveda,
            'nombre_supervisor' => $nombreSupervisor,
            'nombre_oficial' => $nombreOficial,
            'nombre_agencia' => $nombreAgencia,
            'fecha' => date('d/m/Y H:i A')
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en cierre.php: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
