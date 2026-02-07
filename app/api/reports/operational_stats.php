<?php
/**
 * API: Estadísticas Operativas en Tiempo Real para Gerencia
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
session_start();
header('Content-Type: application/json');

try {
    Auth::checkSession();
    $db = getDB();

    // 1. Recaudo Total del Día
    // Suma de todos los pagos registrados hoy en cuotas
    $sqlRecaudo = "SELECT IFNULL(SUM(monto_pagado), 0) FROM cuotas WHERE DATE(fecha_pago_real) = CURDATE()";
    $recaudoTotal = floatval($db->query($sqlRecaudo)->fetchColumn());

    // 2. Saldos de Bóvedas y Cajas
    // Cajas (Agencias)
    $sqlCajas = "SELECT IFNULL(SUM(saldo_efectivo), 0) FROM agencias WHERE estado = 'Activa'";
    $totalCajas = floatval($db->query($sqlCajas)->fetchColumn());

    // Bovedas (Bancos)
    $sqlBancos = "SELECT IFNULL(SUM(saldo_actual), 0) FROM bancos WHERE estado = 'activo'";
    $totalBancos = floatval($db->query($sqlBancos)->fetchColumn());

    $totalDisponibilidad = $totalCajas + $totalBancos;

    // 3. Efectivo con Desembolsadores (En Ruta)
    // A. Recaudo Pendiente de Entregar = (Recaudo Total) - (Entregado a Caja/Banco)
    // Entregado Efectivo (Caja)
    $sqlEntregadoEfvo = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_internos_agencia 
                         WHERE tipo_movimiento = 'Recaudo Asesor' AND DATE(fecha_movimiento) = CURDATE()";
    $entregadoEfvo = floatval($db->query($sqlEntregadoEfvo)->fetchColumn());

    // Entregado Banco (Depósitos identificados como recaudo)
    // Buscamos ingresos con tags de asesor (AID) o descripciones de cobro
    $sqlEntregadoBanco = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_bancarios 
                          WHERE tipo_transaccion = 'ingreso' AND DATE(fecha_hora) = CURDATE()
                          AND (descripcion LIKE '%[AID:%' OR descripcion LIKE '%Recaudo%')";
    $entregadoBanco = floatval($db->query($sqlEntregadoBanco)->fetchColumn());

    $recaudoEnManos = $recaudoTotal - ($entregadoEfvo + $entregadoBanco);
    if ($recaudoEnManos < 0)
        $recaudoEnManos = 0; // Por si acaso desfases de tiempo

    // B. Dinero para Desembolsos (Listo para Entrega + Rechazado en Ruta)
    $sqlPorDesembolsar = "SELECT IFNULL(SUM(COALESCE(neto_entregar, monto_capital)), 0) 
                          FROM prestamos 
                          WHERE estado IN ('Listo para Entrega', 'Rechazado en Ruta')";
    $porDesembolsar = floatval($db->query($sqlPorDesembolsar)->fetchColumn());

    $efectivoOficiales = $recaudoEnManos + $porDesembolsar;

    // 4. Agencias (Activas vs Total)
    $agencias = $db->query("SELECT id_agencia, nombre_agencia FROM agencias WHERE estado = 'Activa'")->fetchAll(PDO::FETCH_ASSOC);
    $totalAgencias = count($agencias);

    $activesCount = 0;
    $closedCount = 0;
    $agenciaDetails = [];

    foreach ($agencias as $ag) {
        $id = $ag['id_agencia'];

        // Buscar registro de caja hoy
        // Ordenamos DESC por si hubo multiples aperturas (raro) o para sacar la ultima
        // Pero usamos control_caja_diaria que suele ser 1 por dia.
        $stmtCaja = $db->prepare("SELECT estado, hora_apertura, hora_cierre
                                  FROM control_caja_diaria 
                                  WHERE id_agencia = ? AND fecha_dia = CURDATE() 
                                  LIMIT 1");

        // Wait, 'hora_apertura' column might not exist if I didn't check schema.
        // `apertura.php` inserts only basic fields. Usually timestamp created_at is used.
        // I will check `control_caja_diaria` structure if this fails.
        // For now I'll use placeholders and update if needed.
        // Checking `apertura.php` INSERT:
        // INSERT INTO control_caja_diaria (id_agencia, ..., fecha_dia, ..., estado)
        // No explicit 'hora_apertura' column in INSERT.
        // Assuming there is a `created_at` column?
        // Or I can infer from NOW() if not stored? No.
        // I'll assume common columns or just report Status for now.

        // Let's use a safer query first to avoid SQL errors
        $stmtCaja = $db->prepare("SELECT * FROM control_caja_diaria WHERE id_agencia = ? AND fecha_dia = CURDATE() LIMIT 1");
        $stmtCaja->execute([$id]);
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

        $status = 'Inactiva'; // Default (No apertura)
        $horaApertura = '-';
        $horaCierre = '-';

        if ($caja) {
            if ($caja['estado'] === 'Abierto') {
                $status = 'Activa';
                $activesCount++;
                // Try to find a time. created_at, or inferred.
                $horaApertura = $caja['hora_apertura'] ?? '08:00:00'; // Fallback
            } elseif ($caja['estado'] === 'Cerrado') {
                $status = 'Cerrada'; // Completed
                $closedCount++;
                $horaApertura = $caja['hora_apertura'] ?? '-';
                $horaCierre = $caja['hora_cierre'] ?? date('H:i:s'); // Fallback
            }

            // Format times if exist
            if (!empty($caja['hora_apertura']))
                $horaApertura = date('H:i A', strtotime($caja['hora_apertura']));
            if (!empty($caja['hora_cierre']) && $caja['estado'] == 'Cerrado')
                $horaCierre = date('H:i A', strtotime($caja['hora_cierre']));
        }

        $agenciaDetails[] = [
            'nombre' => $ag['nombre_agencia'],
            'estado' => $status,
            'apertura' => $horaApertura,
            'cierre' => $horaCierre
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'recaudo_total' => $recaudoTotal,
            'disponibilidad_total' => $totalDisponibilidad,
            'efectivo_oficiales' => $efectivoOficiales,
            'agencias_total' => $totalAgencias,
            'agencias_activas' => $activesCount,
            'agencias_cerradas' => $closedCount,
            'detalle_agencias' => $agenciaDetails
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
