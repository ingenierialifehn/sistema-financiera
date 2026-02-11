<?php
/**
 * API: Obtener lista de asesores y su estado de recaudo diario
 */
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $agenciaId = $_SESSION['id_agencia'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    if (!$agenciaId) {
        $agenciaId = $_GET['id_agencia'] ?? null;
    }

    if (!$agenciaId) {
        throw new Exception("Usuario no asignado a una agencia.");
    }

    $db = getDB();

    // 1. Obtener usuarios filtrados por Rol/Puesto (Logica de Operaciones)
    // Keywords: 'asesor', 'cobrador'
    // 1. Obtener usuarios: Asesores/Cobradores O cualquiera con actividad de desembolso/rechazo hoy
    $sql = "SELECT DISTINCT u.id_usuario, u.username, u.saldo_caja_virtual,
                   COALESCE(c.nombre_completo, u.username) as nombre_completo,
                   c.id_agencia
            FROM usuarios u
            LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            WHERE c.id_agencia = ? 
              AND u.estado = 'Activo'
              AND (
                  (r.nombre_rol LIKE '%asesor%' OR r.nombre_rol LIKE '%cobrador%' OR c.puesto_cargo LIKE '%asesor%' OR c.puesto_cargo LIKE '%cobrador%')
                  OR
                  EXISTS (
                      SELECT 1 FROM prestamos p 
                      WHERE p.oficial_desembolsos_id = u.id_usuario 
                      AND (
                          (p.estado = 'Activo' AND DATE(p.fecha_desembolso) = ?)
                          OR 
                          (p.estado = 'Rechazado en Ruta')
                      )
                  )
                  OR 
                  EXISTS (
                      SELECT 1 FROM cuotas qc 
                      WHERE qc.usuario_cobro_id = u.id_usuario 
                      AND DATE(qc.fecha_pago_real) = ?
                  )
                  OR
                  EXISTS (
                      SELECT 1 FROM movimientos_internos_agencia mia
                      WHERE mia.usuario_origen_id = u.id_usuario
                      AND DATE(mia.fecha_movimiento) = ?
                  )
              )
            ORDER BY nombre_completo ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$agenciaId, $fecha, $fecha, $fecha]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);



    $asesores = [];

    foreach ($usuarios as $u) {
        $uid = $u['id_usuario'];
        $nombreCompleto = $u['nombre_completo']; // Ya viene limpio del SQL

        // A. Total Recaudado (Cartera asignada)
        $sqlCobro = "SELECT 
                        IFNULL(SUM(c.monto_pagado), 0) as total,
                        IFNULL(SUM(LEAST(c.monto_pagado, c.capital_cuota)), 0) as capital
                     FROM cuotas c
                     JOIN prestamos p ON c.prestamo_id = p.id
                     WHERE (c.usuario_cobro_id = ?)
                       AND DATE(c.fecha_pago_real) = ?";

        $stmtC = $db->prepare($sqlCobro);
        $stmtC->execute([$uid, $fecha]);
        $rowCobro = $stmtC->fetch(PDO::FETCH_ASSOC);

        $totalCobrado = floatval($rowCobro['total'] ?? 0);
        $capitalCobrado = floatval($rowCobro['capital'] ?? 0);
        $interesCobrado = $totalCobrado - $capitalCobrado; // Simplificado: Todo lo no-capital es interés/mora

        // B. Total Ya Entregado
        // Logica Dual: Efectivo en Internos + Deposito en Bancos
        // Filtro Hibrido: Etiqueta AID (Exacto) O Nombre (Legado hoy)

        $searchTag = "%[AID:$uid]%";
        $searchName = "%" . $nombreCompleto . "%";

        // B.1 Efectivo (movimientos_internos_agencia)
        $sqlEfvo = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_internos_agencia 
                    WHERE id_agencia = ? 
                    AND tipo_movimiento = 'Recaudo Asesor'
                    AND (observaciones LIKE ? OR observaciones LIKE ?)
                    AND DATE(fecha_movimiento) = ?";
        $stmtE = $db->prepare($sqlEfvo);
        $stmtE->execute([$agenciaId, $searchTag, $searchName, $fecha]);
        $entregadoEfvo = floatval($stmtE->fetchColumn());

        // B.2 Banco (movimientos_bancarios)
        // Columna correcta confirmada: fecha_hora
        $sqlBanco = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_bancarios 
                     WHERE tipo_transaccion = 'ingreso' 
                     AND (descripcion LIKE ? OR descripcion LIKE ?)
                     AND DATE(fecha_hora) = ?";
        $stmtB = $db->prepare($sqlBanco);
        $stmtB->execute([$searchTag, $searchName, $fecha]);
        $entregadoBanco = floatval($stmtB->fetchColumn());

        $totalEntregado = $entregadoEfvo + $entregadoBanco;

        // D. Rechazados Hoy + Listos para Entrega (Dinero en poder del asesor que debe devolver)
        $rechazados = $db->query("SELECT p.id, COALESCE(p.neto_entregar, p.monto_capital) as monto, c.nombre_completo, p.estado 
                                            FROM prestamos p 
                                            JOIN clientes c ON p.id_cliente = c.id 
                                            WHERE p.oficial_desembolsos_id = $uid 
                                            AND p.estado IN ('Rechazado en Ruta', 'Listo para Entrega')")->fetchAll(PDO::FETCH_ASSOC);

        $totalRechazado = 0;
        foreach ($rechazados as $r) {
            $totalRechazado += floatval($r['monto']);
        }

        // C. Pendiente del Día (Calculado dinámicamente)
        $pendiente = $totalCobrado + $totalRechazado - $totalEntregado;
        if ($pendiente < 0)
            $pendiente = 0;

        // Sumar rechazados a lo recaudado para que aparezca como monto a responder
        $totalResponsabilidad = $totalCobrado + $totalRechazado;

        $asesores[] = [
            'id_usuario' => $uid,
            'nombre_completo' => $nombreCompleto,
            'recaudado_hoy' => $totalResponsabilidad,
            'capital_hoy' => $capitalCobrado,
            'interes_hoy' => $interesCobrado,
            'entregado_hoy' => $totalEntregado,
            'pendiente' => $pendiente,
            'rechazados_hoy' => $rechazados,
            'desembolsos_hoy' => $db->query("SELECT COUNT(*) FROM prestamos 
                                             WHERE oficial_desembolsos_id = $uid 
                                             AND estado = 'Activo' 
                                             AND DATE(fecha_desembolso) = '$fecha'")->fetchColumn(),
            'ya_cuadrado' => $db->query("SELECT COUNT(*) FROM cuadres_asesores 
                                         WHERE id_asesor = $uid 
                                         AND fecha_cuadre = '$fecha'")->fetchColumn() > 0
        ];
    }


    echo json_encode(['success' => true, 'data' => $asesores]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>