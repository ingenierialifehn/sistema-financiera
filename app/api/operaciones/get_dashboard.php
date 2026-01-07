<?php
/**
 * API: Obtener datos del dashboard de operaciones por agencia
 * Incluye información detallada de desembolsos pendientes
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('operaciones');

$db = getDB();
$user = Auth::getCurrentUser();

try {
    // Priorizar la agencia de la sesión (permite cambio de agencia para Super Admin)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    // 1. Saldos (Bóveda y Caja Operativa)
    $stmtSaldos = $db->prepare("SELECT saldo_efectivo, saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
    $stmtSaldos->execute([$idAgencia]);
    $saldos = $stmtSaldos->fetch(PDO::FETCH_ASSOC);

    $saldoBoveda = $saldos ? floatval($saldos['saldo_efectivo']) : 0;
    $saldoCajaOperativa = $saldos ? floatval($saldos['saldo_caja_operativa']) : 0;
    $fondosDisponibles = $saldoBoveda + $saldoCajaOperativa;

    // 2. Clientes Totales de la agencia
    $stmtClientes = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE id_agencia = ? AND estado = 'activo'");
    $stmtClientes->execute([$idAgencia]);
    $clientesTotales = $stmtClientes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 3. Créditos Aprobados (estado 'aprobado')
    $stmtAprobados = $db->prepare("SELECT COUNT(*) as total FROM prestamos WHERE id_agencia = ? AND estado = 'aprobado'");
    $stmtAprobados->execute([$idAgencia]);
    $creditosAprobados = $stmtAprobados->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 4. Cartera en Calle (préstamos activos)
    $stmtCartera = $db->prepare("
        SELECT 
            IFNULL(SUM(p.monto_total), 0) as total_prestado,
            IFNULL(SUM(COALESCE(cu.monto_pagado, 0)), 0) as total_pagado
        FROM prestamos p
        LEFT JOIN cuotas cu ON p.id = cu.prestamo_id
        WHERE p.id_agencia = ? AND p.estado = 'activo'
    ");
    $stmtCartera->execute([$idAgencia]);
    $cartera = $stmtCartera->fetch(PDO::FETCH_ASSOC);
    $carteraEnCalle = floatval($cartera['total_prestado'] ?? 0) - floatval($cartera['total_pagado'] ?? 0);

    // 5. DESEMBOLSOS PENDIENTES - Información Detallada

    // Cantidad de créditos aprobados pendientes de desembolsar
    $cantidadDesembolsosPendientes = $creditosAprobados;

    // Monto total de desembolsos pendientes (monto prestado)
    $stmtMontoTotal = $db->prepare("
        SELECT IFNULL(SUM(monto_prestado), 0) as total 
        FROM prestamos 
        WHERE id_agencia = ? AND estado = 'aprobado'
    ");
    $stmtMontoTotal->execute([$idAgencia]);
    $montoTotalDesembolsos = floatval($stmtMontoTotal->fetch(PDO::FETCH_ASSOC)['total']);

    // Para este sistema, el monto neto requerido es igual al monto prestado
    // (no hay deducciones de gastos administrativos ni seguros en el esquema actual)
    $montoNetoRequerido = $montoTotalDesembolsos;

    // Gastos administrativos y seguros no están en el esquema actual
    $totalGastosAdmin = 0;
    $totalSeguros = 0;

    // Desglose por tipo de préstamo
    $stmtPorTipo = $db->prepare("
        SELECT 
            COALESCE(tipo_prestamo, 'General') as tipo,
            COUNT(*) as cantidad,
            IFNULL(SUM(monto_prestado), 0) as monto_total
        FROM prestamos 
        WHERE id_agencia = ? AND estado = 'aprobado'
        GROUP BY tipo_prestamo
    ");
    $stmtPorTipo->execute([$idAgencia]);
    $desembolsosPorTipo = $stmtPorTipo->fetchAll(PDO::FETCH_ASSOC);

    // Validar si hay fondos suficientes
    $fondosSuficientes = $fondosDisponibles >= $montoNetoRequerido;
    $faltante = $fondosSuficientes ? 0 : ($montoNetoRequerido - $fondosDisponibles);

    Response::success([
        // Saldos
        'saldo_boveda' => $saldoBoveda,
        'saldo_caja_operativa' => $saldoCajaOperativa,
        'fondos_disponibles' => $fondosDisponibles,

        // Estadísticas generales
        'clientes_totales' => (int) $clientesTotales,
        'creditos_aprobados' => (int) $creditosAprobados,
        'cartera_en_calle' => $carteraEnCalle,

        // Información detallada de desembolsos
        'desembolsos' => [
            'cantidad_pendientes' => $cantidadDesembolsosPendientes,
            'monto_total_desembolsos' => $montoTotalDesembolsos,
            'monto_neto_requerido' => $montoNetoRequerido,
            'total_gastos_administrativos' => $totalGastosAdmin,
            'total_seguros' => $totalSeguros,
            'fondos_suficientes' => $fondosSuficientes,
            'faltante' => $faltante,
            'por_tipo' => $desembolsosPorTipo
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en get_dashboard_operaciones.php: " . $e->getMessage());
    Response::serverError('Error al obtener datos del dashboard');
}
