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
    $sql = "SELECT u.id_usuario, u.username, u.saldo_caja_virtual,
                   COALESCE(c.nombre_completo, u.username) as nombre_completo,
                   c.id_agencia
            FROM usuarios u
            LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            WHERE c.id_agencia = ? 
              AND u.estado = 'Activo'
              AND (
                  r.nombre_rol LIKE '%asesor%' OR r.nombre_rol LIKE '%cobrador%' OR
                  c.puesto_cargo LIKE '%asesor%' OR c.puesto_cargo LIKE '%cobrador%'
              )
            ORDER BY nombre_completo ASC";

    // logMsg("SQL Users Filtered: Asesor/Cobrador");

    $stmt = $db->prepare($sql);
    $stmt->execute([$agenciaId]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);



    $asesores = [];

    foreach ($usuarios as $u) {
        $uid = $u['id_usuario'];
        $nombreCompleto = $u['nombre_completo']; // Ya viene limpio del SQL

        // A. Total Recaudado (Cartera asignada)
        $sqlCobro = "SELECT IFNULL(SUM(c.monto_pagado), 0) 
                     FROM cuotas c
                     JOIN prestamos p ON c.prestamo_id = p.id
                     WHERE (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)
                       AND DATE(c.fecha_pago_real) = ?";

        $stmtC = $db->prepare($sqlCobro);
        $stmtC->execute([$uid, $uid, $fecha]);
        $totalCobrado = floatval($stmtC->fetchColumn());

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
        // C. Pendiente REAL = Saldo Caja Virtual (La verdad absoluta)
        $pendiente = floatval($u['saldo_caja_virtual'] ?? 0);

        $asesores[] = [
            'id_usuario' => $uid,
            'nombre_completo' => $nombreCompleto,
            'recaudado_hoy' => $totalCobrado,
            'entregado_hoy' => $totalEntregado,
            'pendiente' => $pendiente
        ];
    }


    echo json_encode(['success' => true, 'data' => $asesores]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>