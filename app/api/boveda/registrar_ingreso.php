<?php
/**
 * API: Registrar ingreso de efectivo desde banco a bóveda de agencia
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

$db = getDB();
$user = Auth::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }

    // Log para debug
    error_log("=== REGISTRAR INGRESO DEBUG ===");
    error_log("Usuario: " . json_encode($user));

    // Verificar permisos con mejor manejo de errores
    try {
        // Nuevo sistema de permisos granulares
        $tienePermisoGranular = Auth::hasPermission('boveda.pull_funds');

        // Compatibilidad con sistema antiguo
        $tienePermisoBoveda = Auth::hasPermission('boveda.crear') || Auth::hasPermission('boveda.editar') || Auth::hasPermission('boveda');
        $tienePermisoOperaciones = Auth::hasPermission('operaciones.crear') || Auth::hasPermission('operaciones.editar') || Auth::hasPermission('operaciones');

        error_log("Permiso bóveda: " . ($tienePermisoBoveda ? 'SI' : 'NO'));
        error_log("Permiso operaciones: " . ($tienePermisoOperaciones ? 'SI' : 'NO'));
    } catch (Exception $e) {
        error_log("Error verificando permisos: " . $e->getMessage());
        Response::error('Error al verificar permisos: ' . $e->getMessage(), 500);
    }

    // Permitir si tiene el permiso granular O los permisos legacy
    if (!$tienePermisoGranular && !$tienePermisoBoveda && !$tienePermisoOperaciones) {
        error_log("Usuario sin permisos suficientes");
        Response::forbidden('No tiene permisos para jalar fondos desde banco. Requiere: boveda.pull_funds');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $bancoId = $data['banco_id'] ?? null;
    $monto = $data['monto'] ?? null;
    $referencia = $data['referencia'] ?? '';
    $observaciones = $data['observaciones'] ?? '';
    $userId = $user['id_usuario'];
    $agenciaId = $user['id_agencia'];

    // Validaciones
    if (!$bancoId || !$monto) {
        Response::error('Banco y monto son requeridos', 400);
    }

    if ($monto <= 0) {
        Response::error('El monto debe ser mayor a 0', 400);
    }

    if (!$agenciaId) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    // Iniciar transacción
    $db->beginTransaction();

    // 1. Obtener y bloquear saldo del banco
    $stmtBanco = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");
    $stmtBanco->execute([$bancoId]);
    $banco = $stmtBanco->fetch(PDO::FETCH_ASSOC);

    if (!$banco) {
        $db->rollBack();
        Response::error('Banco no encontrado', 404);
    }

    if ($banco['saldo_actual'] < $monto) {
        $db->rollBack();
        Response::error('Saldo insuficiente en la cuenta bancaria', 400);
    }

    $saldoAnteriorBanco = $banco['saldo_actual'];
    $saldoNuevoBanco = $saldoAnteriorBanco - $monto;

    // 2. Obtener y bloquear saldo de la caja de agencia (cajas_agencias)
    $stmtCaja = $db->prepare("SELECT saldo_efectivo FROM cajas_agencias WHERE id_agencia = ? FOR UPDATE");
    $stmtCaja->execute([$agenciaId]);
    $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

    // Si no existe registro en cajas_agencias, crearlo
    if (!$caja) {
        $stmtCreate = $db->prepare("INSERT INTO cajas_agencias (id_agencia, saldo_efectivo, saldo_caja_operativa) VALUES (?, 0, 0)");
        $stmtCreate->execute([$agenciaId]);

        // Volver a seleccionar bloqueando
        $stmtCaja->execute([$agenciaId]);
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
    }

    $saldoAnteriorAgencia = floatval($caja['saldo_efectivo']);
    $saldoNuevoAgencia = $saldoAnteriorAgencia + $monto;

    // 3. Actualizar saldo del banco
    $stmtUpdateBanco = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
    $stmtUpdateBanco->execute([$saldoNuevoBanco, $bancoId]);

    // 4. Actualizar saldo de la caja de agencia
    $stmtUpdateAgencia = $db->prepare("UPDATE cajas_agencias SET saldo_efectivo = ?, ultima_actualizacion = NOW() WHERE id_agencia = ?");
    $stmtUpdateAgencia->execute([$saldoNuevoAgencia, $agenciaId]);

    // 5. Registrar el movimiento en ingresos_bancos_agencia (Registro específico)
    $stmtIngreso = $db->prepare("
        INSERT INTO ingresos_bancos_agencia (
            banco_id, 
            agencia_id, 
            monto, 
            referencia, 
            saldo_anterior_banco, 
            saldo_nuevo_banco, 
            saldo_anterior_agencia, 
            saldo_nuevo_agencia, 
            realizado_por,
            observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIngreso->execute([
        $bancoId,
        $agenciaId,
        $monto,
        $referencia,
        $saldoAnteriorBanco,
        $saldoNuevoBanco,
        $saldoAnteriorAgencia,
        $saldoNuevoAgencia,
        $userId,
        $observaciones
    ]);

    // 6. Registrar en Movimientos Bancarios (Auditoria General)
    $stmtMovBanco = $db->prepare("
        INSERT INTO movimientos_bancarios (
            banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, 
            descripcion, referencia, realizado_por, entidad_destino_tipo, entidad_destino_id, fecha_hora
        ) VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?, 'agencia', ?, NOW())
    ");
    $stmtMovBanco->execute([
        $bancoId,
        $monto,
        $saldoAnteriorBanco,
        $saldoNuevoBanco,
        "Traslado de Fondos a Agencia (Jalar Fondos)",
        $referencia,
        $userId,
        $agenciaId
    ]);

    // --- 7. DETECCIÓN DE ALERTAS SILENCIOSAS (AUDITORÍA) ---
    // Recalcular sugerencia del sistema para comparar con lo solicitado
    // Nota: Calculamos basado en el estado POST-Update, por lo que ajustamos el disponible restando el monto actual
    $stmtSug = $db->prepare("
        SELECT 
            IFNULL(SUM(CASE WHEN estado = 'Listo para Entrega' THEN COALESCE(neto_entregar, monto_capital) ELSE 0 END), 0) as por_entregar,
            (SELECT IFNULL(saldo_efectivo,0) + IFNULL(saldo_caja_operativa,0) FROM cajas_agencias WHERE id_agencia = ?) as disponible_total_actual
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        WHERE c.id_agencia = ?
    ");
    $stmtSug->execute([$agenciaId, $agenciaId]);
    $sugData = $stmtSug->fetch(PDO::FETCH_ASSOC);

    $porEntregar = floatval($sugData['por_entregar']);
    $disponiblePost = floatval($sugData['disponible_total_actual']);

    // El disponible visto por el usuario era el actual MENOS lo que acaba de ingresar
    $disponibleUsuario = $disponiblePost - $monto;

    // La sugerencia es: Lo que necesito - Lo que tengo. (Mínimo 0)
    $sugeridoSistema = max(0, $porEntregar - $disponibleUsuario);

    // Comparar (Si hay diferencia mayor a 1 Lempira)
    if (abs($monto - $sugeridoSistema) > 1.00) {
        $agencyNameStmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
        $agencyNameStmt->execute([$agenciaId]);
        $nomAgencia = $agencyNameStmt->fetchColumn();

        $msg = "En agencia $nomAgencia el dia " . date('d/m/Y') . " a la hora " . date('H:i') .
            " se hizo una modificacion de monto. Saldo que debia solicitar el sistema: L. " . number_format($sugeridoSistema, 2) .
            ", se solicitaron L. " . number_format($monto, 2) . ". Revisar caso.";

        $stmtAlert = $db->prepare("INSERT INTO alertas_sistema (tipo, mensaje, agencia_id, usuario_id) VALUES ('modificacion_monto', ?, ?, ?)");
        $stmtAlert->execute([$msg, $agenciaId, $userId]);
    }

    // Confirmar transacción
    $db->commit();

    Response::success([
        'message' => 'Ingreso registrado exitosamente',
        'nuevo_saldo_boveda' => $saldoNuevoAgencia,
        'nuevo_saldo_banco' => $saldoNuevoBanco
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en registrar_ingreso.php: " . $e->getMessage());
    // Retornamos el mensaje técnico para facilitar debugging
    Response::serverError('Error al registrar el ingreso: ' . $e->getMessage());
}
